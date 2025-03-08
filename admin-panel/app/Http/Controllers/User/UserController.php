<?php

namespace App\Http\Controllers\User;

use App\Enums\UserStatusEnum;
use App\Exports\UserExport;
use App\Functions\FlashMessages\Toast;
use App\Http\Controllers\Controller;
use App\Http\Requests\User\StoreUserRequest;
use App\Http\Requests\User\UpdateUserReqest;
use App\Imports\UsersImport;
use App\Models\Admin;
use App\Models\ReferralCode;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;

class UserController extends Controller
{
    public function index()
    {
        $users = User::with('introducerReferral.user', 'activeFinancialBlocks')
            ->filterBy(request()->all())
            ->paginate(20);
        $referral_codes = ReferralCode::all();
        $onlineUserCount = User::online()->count();
        $activeUsersCount = User::active()->count();
        $inActiveUsersCount = User::inActive()->count();

        $usersHasTransactionCount = User::has('transactions')->count();

        $supportDescriptions = User::select('support_description')
            ->whereNotNull('support_description')
            ->groupBy('support_description')
            ->get();

        return view('dashboard.user.index', [
            'users' => $users,
            'referral_codes' => $referral_codes,
            'onlineUserCount' => $onlineUserCount,
            'supportDescriptions' => $supportDescriptions,
            'usersHasTransactionCount' => $usersHasTransactionCount,
            'activeUsersCount' => $activeUsersCount,
            'inActiveUsersCount' => $inActiveUsersCount,
        ]);
    }

    public function create()
    {
        $randomPassword = Str::random(8);

        return view('dashboard.user.create', [
            'randomPassword' => $randomPassword,
        ]);
    }

    public function store(StoreUserRequest $request)
    {

        $user = User::query()->create([
            'first_name' => $request->first_name,
            'last_name' => $request->last_name,
            'email' => $request->email,
            'username' => User::generateUsername($request->email),
            'mobile' => $request->mobile,
            'password' => Hash::make($request->password),
            'status' => $request->status,
            'kyc_status' => $request->kyc_status,
            'description' => $request->description,
        ]);

        if ($request->filled('introducer_code')) {
            $introducer = ReferralCode::query()->where('code', $request->introducer_code)->first();
            if ($introducer) {
                $user->introducer_code = $introducer->id;
            } else {
                return back()->withErrors([
                    'introducer_code' => 'کد معرف اشتباه است.',
                ]);
            }
        }
        $user->save();

        Toast::message('.افزودن کاربر با موفقیت انجام شد')->success()->notify();

        return redirect()->route('admin.user.index');
    }

    public function edit(User $user)
    {

        return view('dashboard.user.edit')
            ->with(['user' => $user]);
    }

    public function update(UpdateUserReqest $request, User $user)
    {
        $introducer_code = null;
        if ($request->filled('introducer_code')) {
            $introducer = ReferralCode::query()->where('code', $request->introducer_code)->first();
            if ($introducer) {
                $introducer_code = $introducer->id;
            } else {
                return back()->withErrors([
                    'introducer_code' => 'کد معرف اشتباه است.',
                ]);
            }
        }
        $user->update([
            'first_name' => $request->first_name,
            'last_name' => $request->last_name,
            'mobile' => $request->mobile,
            'status' => $request->status,
            'kyc_status' => $request->kyc_status,
            'introducer_code' => $introducer_code,
            'description' => $request->description,
        ]);

        Toast::message('ویرایش کاربر با موفقیت انجام شد')->success()->notify();

        return redirect()->route('admin.user.index');
    }

    public function exportExcel(Request $request)
    {

        $from = $request->get('from_id');
        $to = $request->get('to_id');
        $filename = 'users_'.$from.'_'.$to;

        $userQuery = User::orderBy('id')->filterBy(request()->all());
        if ($request->get('from_id') && $request->get('to_id')) {
            $userQuery->where('id', '>=', $request->from_id)
                ->where('id', '<=', $request->to_id);
        }
        $users = $userQuery->get();

        $users = $users->map(function ($user) {
            $support = $user->saleSupport ? $user->saleSupport->fullname() : 'unknown';

            $gender = 'نامشخص';
            if ($user->sex === 0) {
                $gender = 'دختر';
            }
            if ($user->sex === 1) {
                $gender = 'پسر';
            }

            $sales_description = $user->support_description;

            return [
                $user->id,
                $user->mobile,
                $gender,
                str_replace(['(', ')'], ' ', $user->name),
                str_replace(['(', ')'], ' ', $user->name_english),
                $support,
                $sales_description,
                $user->created_at,
            ];
        });

        return Excel::download(new UserExport($users), $filename.'.xlsx');
    }

    public function groupRegisterForm()
    {
        $admin = Auth::guard('admin')->user();
        $sales_support = Admin::checkPermissionToGetSalesSupportList($admin)
            ->select('id', 'first_name', 'last_name', 'mobile')
            ->get();

        return view('dashboard.user.group_register.index', ['sales_support' => $sales_support]);
    }

    public function groupRegister(Request $request)
    {

        $request->validate([
            'users-excel-file' => 'required|file',
            'sale_support_id' => ['required', 'integer', 'exists:admins,id', new ValidSaleSupportId], // Add validation for sale_support_id
        ]);

        try {
            $import = new UsersImport($request->sale_support_id);
            Excel::import($import, $request->file('users-excel-file'));

        } catch (InvalidExcelException $e) {
            return redirect()->back()->with('error-message', $e->getMessage());
        } catch (\Exception $e) {
            return redirect()->back()->with('error-message', $e->getMessage());
        }
        $createdUsers = $import->getCreatedUsers();
        $rowCount = $import->getRowCount();

        if (! count($createdUsers)) {
            return redirect()->back()
                ->with(['warning' => 'تمام لیست شما قبلا ثبت نام شده است.']);
        }

        return redirect()->back()
            ->with(['rowCount' => $rowCount])
            ->with(['createdUsers' => $createdUsers])
            ->with(['success' => 'کاربران شما با موفقیت ایجاد شد.']);
    }

    public function loginAsUser(User $user)
    {

        $token = $user->generateAccessToken(10);

        return redirect(
            sprintf(config('frontend.base_url'), $token)
        );
    }

    public function suspendUser(User $user)
    {
        $user = User::find($user->id);

        if (! $user) {
            Toast::message('کاربر یافت نشد.')->danger();

            return redirect()->back();
        }

        // Toggle status
        $newStatus = $user->status === UserStatusEnum::SUSPEND ? UserStatusEnum::ACTIVE : UserStatusEnum::SUSPEND;
        $user->status = $newStatus;
        $user->save();

        Toast::message('وضعیت کاربر با موفقیت تغییر کرد')->success();

        return redirect()->back();
    }

    public function activeUser(User $user)
    {
        $user->status = UserStatusEnum::ACTIVE;

        $user->email_verified_at = now();


        $user->save();

        Toast::message('وضعیت کاربر با موفقیت تغییر کرد')->success();

        return redirect()->back();
    }
}
