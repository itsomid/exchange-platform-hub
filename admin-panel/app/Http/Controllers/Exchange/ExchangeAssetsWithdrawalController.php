<?php

namespace App\Http\Controllers\Exchange;

use App\Enums\WithdrawalStatusEnum;
use App\Functions\FlashMessages\Toast;
use App\Http\Controllers\Controller;
use App\Models\Currency;
use App\Models\CurrencyChain;
use App\Models\ExchangeAssetsWithdrawal;
use App\Models\Setting;
use App\Models\Wallet;
use App\Models\WalletChain;
use App\Models\Withdrawal;
use App\Services\Exchanges\Asset\AssetFactory;
use App\Services\Exchanges\Asset\DTO\WithdrawRequestDTO;
use App\Services\Exchanges\Asset\Enum\WithdrawMethodEnum;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ExchangeAssetsWithdrawalController extends Controller
{
    public function index()
    {
        $withdraws = ExchangeAssetsWithdrawal::with('currency')->get();

        $withdrawalFeeSum = ExchangeAssetsWithdrawal::sum('fee');

        return view('dashboard.exchange.wallet.exchange-assets-withdrawal', [
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

        return view('dashboard.exchange.wallet.exchange-assets-request-form', [
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
            $asset = AssetFactory::make('coinex');
            $res = $asset->withdraw(
                resolve(WithdrawRequestDTO::class)
                    ->setCurrency($request->input('currency_symbol'))
                    ->setChain($request->input('currency_chain'))
                    ->setAmount($request->input('amount'))
                    ->setWithdrawMethod(WithdrawMethodEnum::ON_CHAIN)
                    ->setAddress($request->input('withdrawal_address'))
            );

            ExchangeAssetsWithdrawal::query()->create([
                'admin_id'           => auth()->id(),
                'withdrawal_id'      => $res->getWithdrawId(),
                'exchange'           => 'coinex',
                'currency_symbol'    => $request->input('currency_symbol'),
                'currency_chain'     => $request->input('currency_chain'),
                'fee_currency'       => $res->getCurrencyFee(),
                'fee'                => $res->getFee(),
                'amount'             => $res->getAmount(),
                'actual_amount'      => $res->getActualAmount(),
                'hd_wallet_address'  => $res->getAddress(),
                'withdrawal_date'    => $res->getCreatedAt(),
                'explore_address_url'=> $res->getExploreAddress(),
                'description'        => $request->input('description'),
            ]);

            Toast::message('درخواست برداشت ثبت شد و تا دقایقی دیگر منتقل می گردد.')
                ->success()
                ->notify();

            return redirect()->route('admin.wallets.assets-gathering-to-hd-wallet.index');
        } catch (\Throwable $e) {
            report($e);
            Toast::message('فرآیند برداشت با شکست مواجه شد. لطفا دوباره تلاش کنید.')
                ->danger()
                ->notify();
            return redirect()->back()->withInput();
        }
    }

}
