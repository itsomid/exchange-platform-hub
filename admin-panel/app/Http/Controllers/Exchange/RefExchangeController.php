<?php

namespace App\Http\Controllers\Exchange;

use App\Http\Controllers\Controller;
use App\Models\Exchange;
use App\Models\ExchangeAssetsWithdrawal;
use App\Models\ExchangeTransaction;
use App\Services\Exchanges\Asset\AssetFactory;
use App\Models\Currency;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class RefExchangeController extends Controller
{
    public function index()
    {
        $exchanges = Exchange::all();
        $activeExchange = Exchange::active()->first();

        return view('dashboard.exchange.ref_exchange.index', [
            'exchanges' => $exchanges,
            'activeExchange' => $activeExchange
        ]);
    }


    public function edit($id)
    {
        $exchange = Exchange::find($id);
        return view('dashboard.exchange.ref_exchange.edit', ['exchange' => $exchange]);
    }

    public function boughtHistory()
    {
        $boughtHistoryByCurrency = ExchangeTransaction::selectRaw('market, SUM(amount) as total_amount, SUM(fee) as total_fee')
            ->groupBy('market')
            ->get();

        $totalExchangeBoughtFee = ExchangeTransaction::sum('fee');
        $totalExchangeBoughtValue = ExchangeTransaction::all()->sum(function ($transaction) {
            return (float) data_get($transaction, 'response.data.filled_value', 0);
        });


        $transaction = ExchangeTransaction::with(['exchangeMarket','currency'])->orderBy('created_at', 'desc')->get();


        return view('dashboard.exchange.ref_exchange.bought-history', [
            'transactions' => $transaction,
            'boughtHistoryByCurrency' => $boughtHistoryByCurrency,
            'totalExchangeBoughtFee' => $totalExchangeBoughtFee,
            'totalExchangeBoughtValue' => $totalExchangeBoughtValue,
        ]);

    }

    public function update(Request $request, $id)
    {
        $exchange = Exchange::findOrFail($id);
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'required|string|max:255|unique:exchanges,slug,' . $exchange->id,
            'priority' => 'required|integer',
        ]);
        $exchange->name = $validated['name'];
        $exchange->slug = $validated['slug'];
        $exchange->priority = $validated['priority'];
        if ($request->has('is_active')) {
            Exchange::setActiveExchange($exchange);
        } else {
            $exchange->is_active = false;
            $exchange->save();
        }
        return redirect()->route('admin.exchange.index')->with('success', 'صرافی با موفقیت ویرایش شد.');
    }

    public function exchangeWallets(string $exchange)
    {
        try {
            $assetService = AssetFactory::make($exchange);
            $balances = $assetService->getBalance();
        } catch (\InvalidArgumentException $e) {
            return 'صرافی مورد نظر پشتیبانی نمی‌شود: ' . $exchange;
        } catch (\Exception $e) {
            Log::error('Exchange API Error:', ['exchange' => $exchange, 'error' => $e->getMessage()]);
            return 'امکان ارتباط با صرافی مرجع نیست: ' . $e->getMessage();
        }

        // Convert DTO objects to array format for processing
        $data = collect($balances)->map(function ($balanceDTO) {
            return [
                'ccy' => $balanceDTO->getCcy(),
                'available' => $balanceDTO->getAvailable(),
                'frozen' => $balanceDTO->getFrozen(),
            ];
        })->toArray();

        // Filter the data first to include only assets present in the Currency model
        $filteredData = collect($data)->filter(function ($item) {
            return Currency::where('symbol', $item['ccy'])->exists();
        });

        // Convert the filtered array to a collection of objects and add coinLogo
        $supportedAssets = $filteredData->map(function ($item) {
            $asset = (object)$item;
            // Fetch the currency logo using the Currency model (we know it exists from the filter)
            $currency = Currency::where('symbol', $asset->ccy)->first();

            // Add the coinLogo property to the asset object
            $asset->coinLogo = $currency->coinLogo(); // No need for default as we filtered

            return $asset;
        });


        // Filter the data for assets *not* present in the Currency model
        $unsupportedData = collect($data)->filter(function ($item) {
            return !Currency::where('symbol', $item['ccy'])->exists();
        });

        // Convert the unsupported array to a collection of objects
        $unsupportedAssets = $unsupportedData->map(function ($item) {
            $asset = (object)$item;
            // Assign a default logo for unsupported assets
            $asset->coinLogo = asset('images/logo/logo.svg');
            return $asset;
        });

        return view('dashboard.exchange.ref_exchange.exchange-wallets', [
            'supportedAssets' => $supportedAssets,
            'unsupportedAssets' => $unsupportedAssets,
            'exchange' => $exchange,
            'exchangeName' => ucfirst($exchange),
        ]);
    }
}
