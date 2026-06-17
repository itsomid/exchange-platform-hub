<?php

namespace App\Http\Controllers\Withdrawal;

use App\Enums\WithdrawalStatusEnum;
use App\Enums\CurrencyChainEnum;
use App\Exports\WithdrawalExport;
use App\Functions\FlashMessages\Toast;
use App\Helpers\DateFormatter;
use App\Http\Controllers\Controller;
use App\Jobs\SendAdminWithdrawalToHDWallet;
use App\Models\Withdrawal;
use App\Models\Currency;

use App\Infrastructure\HDWallet\Exceptions\NotFoundException;
use App\Services\Withdrawal\WithdrawalService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;

class WithdrawalController extends Controller
{
    private WithdrawalService $withdrawalService;

    public function __construct(WithdrawalService $withdrawalService)
    {
        $this->withdrawalService = $withdrawalService;
    }

    public function index()
    {
        if (request()->filled('currency') && ctype_digit((string) request('currency'))) {
            $selectedCurrency = Currency::query()->find((int) request('currency'));
            if ($selectedCurrency) {
                request()->merge(['currency' => $selectedCurrency->symbol]);
            }
        }

        $bitexroomUserId = (int) config('bitexroom.user_id', 1);
        $showExchangeUserWithdrawals = request()->boolean('show_exchange_user_withdrawals');
        $onlyRealNetworkWithdrawals = request()->boolean('only_real_network_withdrawals');

        $applyVisibilityFilters = function ($query) use ($bitexroomUserId, $showExchangeUserWithdrawals, $onlyRealNetworkWithdrawals) {
            if (! $showExchangeUserWithdrawals) {
                $query->where('user_id', '!=', $bitexroomUserId);
            }

            if ($onlyRealNetworkWithdrawals) {
                $query->whereNotNull('transaction_hash')
                    ->where('transaction_hash', '!=', '');
            }

            return $query;
        };

        $today = now()->toDateString(); // Get today's date

        // Count of today's deposits
        $todayWithdrawalsCount = $applyVisibilityFilters(
            Withdrawal::query()->whereDate('created_at', $today)
        )->count();

        $totalWithdrawalsValue = $applyVisibilityFilters(
            Withdrawal::query()->with('currency')->whereDate('created_at', $today)
        )
            ->get()
            ->sum(function ($withdraw) {
                return $withdraw->amount * $withdraw->currency->exchange_price; // Multiply amount by coin price
            });

        // First 5 users with the most deposits (considering currency prices)

        $topUsers = $applyVisibilityFilters(
            Withdrawal::query()
                ->with(['currency', 'user'])
                ->where('status', WithdrawalStatusEnum::COMPLETED)
                ->whereDate('created_at', $today)
        )
            ->get()
            ->groupBy('user_id')
            ->map(function ($withdraws, $userId) {
                $totalWithdraws = $withdraws->sum(function ($withdraw) {
                    return $withdraw->amount * $withdraw->currency->exchange_price;
                });

                return [
                    'user' => $withdraws->first()->user,
                    'totalWithdraw' => $totalWithdraws,
                ];
            })
            ->sortByDesc('totalDeposit')
            ->take(5);
        $totalTopUsersWithdrawals = $topUsers->sum('totalWithdraw');

        $withdraws = $applyVisibilityFilters(Withdrawal::query())
            ->filterBy(request()->all())
            ->with(['user', 'currency', 'currencyChain', 'transaction'])
            ->orderBy('id', request()->input('sortById', 'desc'))
            ->paginate(20);

        $currencies = Currency::query()->where('is_active', true)->get();
        $chains = CurrencyChainEnum::cases();

        return view('dashboard.withdraw.index', [
            'withdraws' => $withdraws,
            'todayWithdrawalsCount' => $todayWithdrawalsCount,
            'totalWithdrawalsValue' => $totalWithdrawalsValue,
            'topUsers' => $topUsers,
            'totalTopUsersWithdrawals' => $totalTopUsersWithdrawals,
            'currencies' => $currencies,
            'chains' => $chains,
        ]);
    }

    /**
     * @throws \Exception
     */
    public function confirmWithdrawal(Withdrawal $withdraw)
    {
        try {
            $admin_id = \Auth::user()->id;
            $this->withdrawalService->adminApproveWithdrawal($withdraw->id, $admin_id);

            Toast::message('.تایید برداشت با موفقیت انجام شد')->success()->notify();

            return redirect()->back();
        } catch (\Throwable $exception) {
            report($exception);

            return redirect()->back()->withErrors(['general' => $exception->getMessage()]);
        }
    }

    public function cancelWithdrawal(Withdrawal $withdraw)
    {
        try {
            $admin_id = \Auth::user()->id;
            $this->withdrawalService->adminCancelWithdrawal($withdraw->id, $admin_id);

            Toast::message('.وضعیت برداشت به مورد تایید نیست تغییر کرد')->success()->notify();

            return redirect()->back();
        } catch (\Throwable $exception) {
            report($exception);

            return redirect()->back()->withErrors(['general' => $exception->getMessage()]);
        }
    }

    public function checkWithdrawal(Withdrawal $withdrawal)
    {
        try {
            $withdrawalService = resolve(\App\Services\Withdrawal\WithdrawalService::class);

            // Check if withdrawal is in a checkable status (pending, processing, or failed)
            $checkableStatuses = [
                \App\Enums\WithdrawalStatusEnum::PENDING,
                \App\Enums\WithdrawalStatusEnum::PROCESSING,
                \App\Enums\WithdrawalStatusEnum::FAILED,
            ];

            if (!in_array($withdrawal->status, $checkableStatuses)) {
                Toast::message('این برداشت قابل بررسی نیست.')->warning()->notify();
                return redirect()->back();
            }

            // Check the specific withdrawal
            $withdrawalCollection = new \Illuminate\Database\Eloquent\Collection([$withdrawal]);
            $response = $withdrawalService->checkWithdrawal($withdrawalCollection);


            if ($response->getStatus() === null) {
                Toast::message('هیچ تغییری در وضعیت برداشت یافت نشد.')->info()->notify();
            } elseif ($response->getStatus() === \App\Enums\WithdrawalStatusEnum::COMPLETED) {
                Toast::message('برداشت با موفقیت تکمیل شد.')->success()->notify();
            } elseif ($response->getStatus() === \App\Enums\WithdrawalStatusEnum::FAILED) {
                Toast::message('برداشت با خطا مواجه شد.')->danger()->notify();
            } else {
                Toast::message('فرآیند چک برداشت آغاز شد.')->success()->notify();
            }

            return redirect()->back();
        } catch (NotFoundException $exception) {
            Toast::message('این برداشت در سرویس HD Wallet یافت نشد.')->warning()->notify();
            return redirect()->back();
        } catch (\Throwable $exception) {
            report($exception);
            Toast::message('خطا در بررسی برداشت: ' . $exception->getMessage())->danger()->notify();
            return redirect()->back();
        }
    }

    public function redispatchWithdrawalJob(Withdrawal $withdrawal)
    {
        if ($withdrawal->status !== WithdrawalStatusEnum::QUEUED) {
            Toast::message('این عملیات فقط برای برداشت‌های با وضعیت «در صف ارسال» مجاز است.')->warning()->notify();
            return redirect()->back();
        }

        // Check if a job already exists for this withdrawal in the database queue
        $jobExists = DB::table('jobs')
            ->where('queue', 'admin-withdrawal')
            ->where('payload', 'LIKE', '%SendAdminWithdrawalToHDWallet%')
            ->get()
            ->contains(function ($job) use ($withdrawal) {
                try {
                    $payload = json_decode($job->payload, true);
                    $command = unserialize($payload['data']['command']);
                    $reflection = new \ReflectionProperty($command, 'withdrawalId');
                    $reflection->setAccessible(true);
                    return $reflection->getValue($command) === $withdrawal->id;
                } catch (\Throwable) {
                    return false;
                }
            });

        if ($jobExists) {
            Toast::message('جاب برداشت برای این تراکنش از قبل در صف موجود است.')->warning()->notify();
            return redirect()->back();
        }

        SendAdminWithdrawalToHDWallet::dispatch($withdrawal->id);

        // Reset job_failed_at since we're retrying
        $withdrawal->update([
            'job_failed_at' => null,
            'description' => 'Redispatched by admin (#' . \Auth::user()->id . ')',
        ]);

        Toast::message('جاب برداشت با موفقیت مجدداً در صف قرار گرفت.')->success()->notify();
        return redirect()->back();
    }

    public function cancelQueuedWithdrawal(Request $request, Withdrawal $withdrawal)
    {
        $request->validate([
            'cancel_reason' => 'required|string|max:500',
        ]);

        try {
            $admin_id = \Auth::user()->id;
            $this->withdrawalService->adminCancelQueuedWithdrawal(
                $withdrawal->id,
                $admin_id,
                $request->input('cancel_reason')
            );

            Toast::message('برداشت لغو شد و وجه به کیف پول کاربر بازگردانده شد.')->success()->notify();

            return redirect()->back();
        } catch (\Throwable $exception) {
            report($exception);

            Toast::message('خطا در لغو برداشت: ' . $exception->getMessage())->danger()->notify();
            return redirect()->back();
        }
    }

    public function excelExport(Request $request)
    {
        $filename = 'withdrawals_' . now()->format('Y-m-d_H-i-s');

        $withdrawalQuery = Withdrawal::orderBy('id')->filterBy(request()->all());
        $withdrawals = $withdrawalQuery->get();

        $withdrawals = $withdrawals->map(function (Withdrawal $withdrawal) {

            return [
                $withdrawal->id,
                $withdrawal->user->email,
                $withdrawal->currency_symbol,
                $withdrawal->currencyChain->chain_name,
                formatNumberTrimZeros($withdrawal->amount),
                formatNumberTrimZeros($withdrawal->usdt_value),
                formatNumberTrimZeros($withdrawal->amount - $withdrawal->total_fee),
                formatNumberTrimZeros($withdrawal->exchange_fee),
                formatNumberTrimZeros($withdrawal->network_fee),
                $withdrawal->address,
                $withdrawal->transaction_hash,
                DateFormatter::convertToPersianDate($withdrawal->created_at, '%Y/%m/%d H:i:s'),
                DateFormatter::convertToPersianDate($withdrawal->confirmed_at, '%Y/%m/%d H:i:s'),
                $withdrawal->status->label(),
                $withdrawal->description,
            ];
        });

        return Excel::download(new WithdrawalExport($withdrawals), $filename . '.xlsx');
    }
}
