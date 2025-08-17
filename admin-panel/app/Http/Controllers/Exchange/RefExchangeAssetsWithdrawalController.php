<?php

namespace App\Http\Controllers\Exchange;

use App\Enums\OTCRefExchangeWithdrawalStatusEnum;
use App\Exceptions\Exchange\CoinexWithdrawalException;
use App\Functions\FlashMessages\Toast;
use App\Http\Controllers\Controller;
use App\Models\Currency;
use App\Models\CurrencyChain;
use App\Models\ExchangeAssetsWithdrawal;
use App\Models\OTCRefExchangeWithdrawal;
use App\Models\Exchange;
use App\Services\Exchanges\Asset\AssetFactory;
use App\Services\Exchanges\DTO\ChargeCurrencyRequestDTO;
use App\Repositories\ExchangeRepository;
use App\Services\Exchanges\ExchangeService;
use App\Services\Wallet\WalletService;
use App\Http\Requests\Exchange\RefExchangeAssetsWithdrawalRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RefExchangeAssetsWithdrawalController extends Controller
{
    public function __construct(
        private readonly WalletService $walletService,
        private readonly ExchangeRepository $exchangeRepository,
    ) {}
    public function index()
    {
        $withdraws = ExchangeAssetsWithdrawal::with('currency')->orderBy('id', 'desc')->get();

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
        if ($request->has('exchange')) {
            $exchange = Exchange::where('slug', $request->exchange)->first();
            $exchangeName = $exchange->name;
        } else {
            $exchangeName = 'coinex';
        }
        $currency = Currency::whereSymbol($currency_symbol)->first();

        $currencyChains = $currency->chains;


        $wallet = $this->walletService->getExchangeWallet($currency->symbol);

        $walletChains = $wallet?->walletChains;

        // Get balance from exchange using AssetFactory
        $exchangeBalance = null;
        try {
            $exchangeSlug = $request->exchange ?? 'coinex';
            $assetService = AssetFactory::make($exchangeSlug);
            $balances = $assetService->getBalance();

            // Find balance for the specific currency
            foreach ($balances as $balance) {
                if ($balance->getCcy() === $currency->symbol) {
                    $exchangeBalance = $balance;
                    break;
                }
            }
        } catch (\Throwable $e) {
            // Log error but continue - balance will be null
            report($e);
        }

        // Get all exchanges for selection
        $exchanges = Exchange::all();
        $selectedExchange = $request->exchange ?? 'coinex';

        return view('dashboard.exchange.ref_exchange.assets-withdrawal-request-form', [
            'currency' => $currency,
            'currencyChains' => $currencyChains,
            'walletChains' => $walletChains,
            'exchangeName' => $exchangeName,
            'exchangeBalance' => $exchangeBalance,
            'exchanges' => $exchanges,
            'selectedExchange' => $selectedExchange,
        ]);
    }

    public function store(RefExchangeAssetsWithdrawalRequest $request)
    {

        $currency = Currency::where('symbol', $request->input('currency_symbol'))->first();

        if (!$currency) {
            return redirect()->back()->withErrors(['currency' => 'ارز انتخاب شده معتبر نیست.']);
        }

        // Retrieve valid chains for this currency
        $validChains = $currency->chains()->pluck('chain')->map(fn($chain) => $chain->value)->toArray();
        // Check if the selected chain is valid
        if (!in_array($request->input('currency_chain'), $validChains)) {
            return redirect()->back()->withErrors(['chain' => 'شبکه انتخاب شده با ارز مطابقت ندارد.']);
        }
        try {

            $exchangeService = resolve(ExchangeService::class);
            $selectedExchange = $this->exchangeRepository->getExchangeBySlug($request->input('exchange_slug'));

            if (!$selectedExchange) {
                return redirect()->back()->withErrors(['exchange_slug' => 'صرافی انتخاب شده معتبر نیست.']);
            }

            $exchangeService->chargeCurrency(
                resolve(ChargeCurrencyRequestDTO::class)
                    ->setCurrency($currency->symbol)
                    ->setCurrencyChain($request->input('currency_chain'))
                    ->setQuantity($request->input('amount'))
                    ->setExchangeSlug($selectedExchange->slug)
            );

            Toast::message('درخواست برداشت ثبت شد و تا دقایقی دیگر منتقل می گردد.')
                ->success()
                ->notify();

            return redirect()->route('admin.ref-exchange.assets-gathering-to-hd-wallet.index');
        } catch (CoinexWithdrawalException $e) {
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
            DB::raw('SUM(transactions.amount) as total_withdraw_amount')
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
