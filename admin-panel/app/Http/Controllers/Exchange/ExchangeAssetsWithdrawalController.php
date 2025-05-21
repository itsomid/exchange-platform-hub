<?php

namespace App\Http\Controllers\Exchange;

use App\Enums\OTCRefExchangeWithdrawalStatusEnum;
use App\Enums\WithdrawalStatusEnum;
use App\Exceptions\Exchange\CoinexWithdrawalException;
use App\Functions\FlashMessages\Toast;
use App\Http\Controllers\Controller;
use App\Models\Currency;
use App\Models\CurrencyChain;
use App\Models\ExchangeAssetsWithdrawal;
use App\Models\OTCRefExchangeWithdrawal;
use App\Models\Setting;
use App\Models\Wallet;
use App\Models\WalletChain;
use App\Models\Withdrawal;
use App\Services\Exchanges\Asset\AssetFactory;
use App\Services\Exchanges\Asset\DTO\WithdrawRequestDTO;
use App\Services\Exchanges\Asset\Enum\WithdrawMethodEnum;
use App\Services\Exchanges\DTO\ChargeUSDTRequestDTO;
use App\Services\Exchanges\ExchangeService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ExchangeAssetsWithdrawalController extends Controller
{
    public function index()
    {
        $withdraws = ExchangeAssetsWithdrawal::with('currency')->get();

        $withdrawalFeeSum = ExchangeAssetsWithdrawal::sum('fee');

        return view('dashboard.exchange.ref_exchange.assets-withdrawal-history', [
            'withdraws' => $withdraws,
            'withdrawalFeeSum' => $withdrawalFeeSum,
        ]);
    }


    public function create(Request $request)
    {
        if ($request->has('currency_symbol')) {
            $currency_symbol = $request->currency_symbol;
        } else {
            $currency_symbol = 'USDT';
        }
        $currency = Currency::whereSymbol($currency_symbol)->first();

        $currencyChains = $currency->chains;
        $wallet = Wallet::where('currency_symbol', $currency->symbol)->first();


        $walletChains = $wallet->walletChains;


        ///TODO: complete Withdrawal

        return view('dashboard.exchange.ref_exchange.assets-withdrawal-request-form', [
            'currency' => $currency,
            'currencyChains' => $currencyChains,
            'walletChains' => $walletChains,
        ]);
    }

    public function store(Request $request)
    {

        $request->validate([
            'currency_symbol' => ['required', Rule::exists(Currency::class, 'symbol')],
            'currency_chain' => ['required', Rule::exists(CurrencyChain::class, 'chain')],
            'amount' => ['required', 'numeric'],
            'withdrawal_address' => 'required',
        ]);

        $currency = Currency::where('symbol', $request->currency_symbol)->first();

        if (!$currency) {
            return redirect()->back()->withErrors(['currency' => 'ارز انتخاب شده معتبر نیست.']);
        }

        // Retrieve valid chains for this currency
        $validChains = $currency->chains()->pluck('chain')->map(fn($chain) => $chain->value)->toArray();
        // Check if the selected chain is valid
        if (!in_array($request->currency_chain, $validChains)) {
            return redirect()->back()->withErrors(['chain' => 'شبکه انتخاب شده با ارز مطابقت ندارد.']);
        }
        try {

            $exchangeService = resolve(ExchangeService::class);
            $exchangeService->chargeCurrency(
                resolve(ChargeUSDTRequestDTO::class)
                    ->setCurrency($currency->symbol)
                    ->setCurrencyChain($request->input('currency_chain'))
                    ->setQuantity($request->input('amount'))
            );

            Toast::message('درخواست برداشت ثبت شد و تا دقایقی دیگر منتقل می گردد.')
                ->success()
                ->notify();

            return redirect()->route('admin.ref-exchange.assets-gathering-to-hd-wallet.index');
        }catch (CoinexWithdrawalException $e) {
            report($e);
            Toast::message('خطا در برداشت از صرافی: ' . $e->getMessage())
                ->danger()
                ->notify();
            return redirect()->back()->withInput();
        } catch (\Throwable $e) {
            report($e);
            Toast::message('فرآیند برداشت با شکست مواجه شد. لطفا دوباره تلاش کنید.')
                ->danger()
                ->notify();
            return redirect()->back()->withInput();
        }
    }

    public function getPendingRefExchangeWithdrawal()
    {
        $withdrawals = OTCRefExchangeWithdrawal::with(['currency', 'transaction'])
            ->orderByRaw("status = ? DESC", [OTCRefExchangeWithdrawalStatusEnum::PENDING->value])
            ->get();



        $pendingWithdrawals = OTCRefExchangeWithdrawal::select(
            'otc_ref_exchange_withdrawals.currency_id',
            \DB::raw('SUM(transactions.amount) as total_withdraw_amount')
        )
            ->join('transactions', 'transactions.id', '=', 'otc_ref_exchange_withdrawals.transaction_id')
            ->where('otc_ref_exchange_withdrawals.status', OTCRefExchangeWithdrawalStatusEnum::PENDING->value)
            ->groupBy('otc_ref_exchange_withdrawals.currency_id')
            ->with('currency') // To get currency details
            ->get();


        return view('dashboard.exchange.ref_exchange.pending-assets-withdrawal-history', [
            'withdrawals' => $withdrawals,
            'pendingWithdrawals' => $pendingWithdrawals,
//            '$pendingWithdrawalsCount' =>
        ]);
    }

}
