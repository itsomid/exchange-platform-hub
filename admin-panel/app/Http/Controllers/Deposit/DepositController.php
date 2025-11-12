<?php

namespace App\Http\Controllers\Deposit;

use App\Exports\DepositExport;
use App\Helpers\DateFormatter;
use App\Http\Controllers\Controller;
use App\Models\Deposit;
use App\Enums\DepositStatusEnum;
use App\Enums\TransactionStatusEnum;
use App\Enums\TransactionSubTypeEnum;
use App\Enums\TransactionTypeEnum;
use App\Enums\CurrencyChainEnum;
use App\Models\Transaction;
use App\Models\Currency;
use App\Models\CurrencyChain;
use App\Services\Wallet\WalletService;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class DepositController extends Controller
{
    protected $walletService;

    public function __construct(WalletService $walletService)
    {
        $this->walletService = $walletService;
    }

    public function index()
    {
        $today = now()->toDateString(); // Get today's date

        // Count of today's deposits
        $todayDepositsCount = Deposit::whereDate('created_at', $today)->count();

        $todayDepositsValue = Deposit::with('currency')
            ->whereDate('created_at', $today)// Assuming `currency` has the price
            ->get()
            ->sum(function ($deposit) {
                return $deposit->amount * ($deposit->currency?->exchange_price ?? 0); // Multiply amount by coin price
            });

        $totalDepositsValue = Deposit::with('currency')
            ->get()
            ->sum('usdt_value');
        // First 5 users with the most deposits (considering currency prices)


        $topUsers = Deposit::with(['currency', 'user'])
            ->get()
            ->groupBy('user_id')
            ->map(function ($deposits, $userId) {
                $totalDeposit = $deposits->sum('usdt_value');
                return [
                    'user' => $deposits->first()->user,
                    'totalDeposit' => $totalDeposit,
                ];
            })
            ->sortByDesc('totalDeposit')
            ->take(5);


        $totalTopUsersDeposit = $topUsers->sum('totalDeposit');
//        return $topUsers;

        $deposits = Deposit::filterBy(request()->all())->with(['user','currency','currencyChain', 'transaction'])
            ->orderBy('id', request()->input('sortById', 'desc'))
            ->paginate(20);

        $currencies = Currency::all();
        $chains = CurrencyChainEnum::cases();

        return view('dashboard.deposits.index', [
            'deposits' => $deposits,
            'todayDepositsValue' => $todayDepositsValue,
            'totalDepositsValue' => $totalDepositsValue,
            'todayDepositsCount' => $todayDepositsCount,
            'totalTopUsersDeposit' => $totalTopUsersDeposit,
            'topUsers' => $topUsers,
            'currencies' => $currencies,
            'chains' => $chains,
        ]);
    }

    public function excelExport(Request $request)
    {
        $from = $request->get('from_id');
        $to = $request->get('to_id');
        $filename = 'deposits_' . $from . '_' . $to;

        $depositQuery = Deposit::orderBy('id')->filterBy(request()->all());
        if ($request->get('from_id') && $request->get('to_id')) {
            $depositQuery->where('id', '>=', $request->from_id)
                ->where('id', '<=', $request->to_id);
        }
        $deposits = $depositQuery->get();

        $deposits = $deposits->map(function (Deposit $deposit) {

            return [
                $deposit->id,
                $deposit->user->email,
                $deposit->currency_symbol,
                $deposit->currencyChain->chain_name,
                formatNumberTrimZeros($deposit->amount),
                formatNumberTrimZeros($deposit->usdt_value),
                $deposit->address,
                $deposit->transaction_hash,
                DateFormatter::convertToPersianDate($deposit->created_at,'%Y/%m/%d H:i:s'),
                DateFormatter::convertToPersianDate($deposit->confirmed_at,'%Y/%m/%d H:i:s'),
                $deposit->status->label(),
                $deposit->description ,
            ];
        });

        return Excel::download(new DepositExport($deposits), $filename . '.xlsx');
    }

    /**
     * Approve a too_small deposit and add it to user's balance
     */
    public function approve(Deposit $deposit)
    {
        // Check if deposit status is TOO_SMALL
        if ($deposit->status !== DepositStatusEnum::TOO_SMALL) {
            return redirect()->back()->with('error', 'فقط واریزی‌های کمتر از حد مجاز قابل تایید هستند.');
        }

        DB::beginTransaction();
        try {
            $adminName = auth()->user()->fullname() ?? auth()->user()->username ?? 'ادمین';
            
            // Update deposit status to CONFIRMED
            $deposit->update([
                'status' => DepositStatusEnum::CONFIRMED,
                'confirmed_at' => now(),
                'description' => ($deposit->description ? $deposit->description . ' | ' : '') . 'تایید شده توسط ادمین ' . $adminName . ' به علت پایین بودن از حد مجاز',
            ]);

            // Get wallet before increasing balance
            $wallet = $deposit->wallet;
            if (!$wallet) {
                throw new \Exception('کیف پول کاربر یافت نشد');
            }

            $balanceBeforeIncrease = $wallet->balance;

            // Increase user wallet balance
            $increased = $this->walletService->increaseBalance(
                $deposit->user_id,
                $deposit->currency_symbol,
                $deposit->amount
            );

            if (!$increased) {
                throw new \Exception('خطا در افزایش موجودی کیف پول');
            }

            // Refresh wallet to get updated balance
            $wallet->refresh();

            // Create transaction record
            Transaction::create([
                'user_id' => $deposit->user_id,
                'wallet_id' => $wallet->id,
                'deposit_id' => $deposit->id,
                'amount' => $deposit->amount,
                'balance' => $balanceBeforeIncrease,
                'type' => TransactionTypeEnum::DEPOSIT,
                'subtype' => TransactionSubTypeEnum::MANUAL_ADMIN,
                'status' => TransactionStatusEnum::SUCCESS,
                'description' => 'واریز تایید شده توسط ادمین ' . $adminName . ' - آدرس: ' . $deposit->address . ($deposit->transaction_hash ? ' | هش: ' . $deposit->transaction_hash : ''),
                'admin_description' => 'تایید واریزی کمتر از حد مجاز توسط ادمین ' . $adminName
            ]);

            DB::commit();

            return redirect()->back()->with('success', 'واریزی با موفقیت تایید و به حساب کاربر اضافه شد.');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'خطا در تایید واریزی: ' . $e->getMessage());
        }
    }
}
