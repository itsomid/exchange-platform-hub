<?php

namespace App\Http\Controllers\User;

use App\Enums\FinancialBlockReasonsEnum;
use App\Enums\FinancialBlockActionEnum;
use App\Functions\FlashMessages\Toast;
use App\Helpers\DateFormatter;
use App\Http\Controllers\Controller;
use App\Http\Requests\FinancialBlock\StoreUserFinancialBlockRequest;
use App\Models\User;
use App\Models\UserFinancialBlock;
use Carbon\Carbon;
use Illuminate\Http\Request;

class UserFinancialBlockController extends Controller
{
    public function index()
    {
        $blockedUsers = User::has('activeFinancialBlocks')->filterBy(request()->all())->paginate(50);
        $withdrawBlockedUsers = User::whereHas('financialBlocksFrom', function ($query) {
            $query->where('action', 'withdraw');
        })->count();

        $depositBlockedUsers = User::whereHas('financialBlocksFrom', function ($query) {
            $query->where('action', 'deposit');
        })->count();

        $tradeBlockedUsers = User::whereHas('financialBlocksFrom', function ($query) {
            $query->where('action', 'trade');
        })->count();
        return view('dashboard.user.financial_blocks.index', [
            'blockedUsers' => $blockedUsers,
            'withdrawBlockedUsers' => $withdrawBlockedUsers,
            'depositBlockedUsers' => $depositBlockedUsers,
            'tradeBlockedUsers' => $tradeBlockedUsers,
        ]);
    }

    public function getBlocks(User $user)
    {

        $userFinancialBlockActions = FinancialBlockActionEnum::cases();

        $user->load('financialBlocks');
        $userFinancialBlockHistory = UserFinancialBlock::query()->whereUserId($user->id)->withTrashed()->orderBy('created_at','desc')->paginate(20);
        return view('dashboard.user.financial_blocks.user-financial-block', [
            'user'=>$user,
            'userFinancialBlockHistory' => $userFinancialBlockHistory,
            'userFinancialBlockActions' => $userFinancialBlockActions
        ]);
    }

    public function addBlock(StoreUserFinancialBlockRequest $request, User $user)
    {
        try {
            UserFinancialBlock::query()->create([
                'user_id' => $user->id,
                'action' => $request->action,
                'reason' => FinancialBlockReasonsEnum::ADMIN->value,
                'admin_id' => \Auth::guard('admin')->user()->id,
                'description' => $request->description,
                'restricted_until' => $request->restricted_until,
            ]);

            return redirect()->back();
        }catch (\InvalidArgumentException $e) {
            // Add the exception message to the validation errors
            return redirect()->back()->withErrors(['reason' => $e->getMessage()])->withInput();
        }

    }

    public function removeBlock(User $user, UserFinancialBlock $financialBlock)
    {

        $financialBlock->delete();
        Toast::message('رفع محدودیت با موفقیت اعمال شد.')->success()->notify();

        return redirect()->back();
    }
    public function createMassBlock()
    {

        $userFinancialBlockActions = FinancialBlockActionEnum::cases();
        return view('dashboard.user.financial_blocks.create-mass-block', [
            'userFinancialBlockActions' => $userFinancialBlockActions
        ]);
    }

    public function storeMassBlock(StoreUserFinancialBlockRequest $request)
    {

        $emails = array_filter(array_map('trim', explode("\n", $request->users))); // Get emails as an array
        $errors = [];
        $successfulBlocks = 0;

        foreach ($emails as $email) {
            try {
                // Find the user by email
                $user = User::where('email', $email)->first();

                if (!$user) {
                    $errors[] = "کاربری با ایمیل '{$email}' پیدا نشد";
                    continue;
                }

                // Create the financial block
                UserFinancialBlock::query()->create([
                    'user_id' => $user->id,
                    'action' => $request->action,
                    'reason' =>  FinancialBlockReasonsEnum::GROUP->value,
                    'admin_id' => \Auth::guard('admin')->user()->id,
                    'description' => $request->description,
                    'restricted_until' => Carbon::parse($request->restricted_until),
                ]);

                $successfulBlocks++;
            } catch (\Exception $e) {
                $errors[] = "Error for '{$email}': " . $e->getMessage();
            }
        }

        // Redirect with success and error messages
        if (count($errors)) {
            return redirect()->back()
                ->with('success', "تعداد {$successfulBlocks} محدودیت با موفقیت ایجاد شد")
                ->withErrors($errors); // Use withErrors for errors
        }

        return redirect()->back()
            ->with('success', "تعداد {$successfulBlocks} محدودیت با موفقیت ایجاد شد");
    }
}
