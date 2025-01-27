<?php

namespace App\Http\Controllers\Exchange;

use App\Functions\FlashMessages\Toast;
use App\Http\Controllers\Controller;
use App\Models\Currency;
use App\Models\CurrencyChain;
use App\Models\ExchangeAssetsWithdrawal;
use App\Services\Exchanges\Asset\AssetFactory;
use App\Services\Exchanges\Asset\DTO\WithdrawRequestDTO;
use App\Services\Exchanges\Asset\Enum\WithdrawMethodEnum;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ExchangeAssetsWithdrawalController extends Controller
{
    public function index()
    {
        $withdraws = ExchangeAssetsWithdrawal::all();

        return view('dashboard.exchange.wallet.exchange-assets-withdrawal',[
            'withdraws' => $withdraws
        ]);
    }


    public function create()
    {

    }

    public function store(Request $request)
    {
        $request->validate([
            'currency' => ['required', Rule::exists(Currency::class, 'symbol')],
            'currency_chain' => ['required', Rule::exists(CurrencyChain::class, 'chain')],
            'amount' => ['required', 'numeric'],
            'address' => 'required',
        ]);

        $asset = AssetFactory::make('coinex');
        $res = $asset->withdraw(
            resolve(WithdrawRequestDTO::class)
                ->setCurrency($request->input('currency'))
                ->setChain($request->input('currency_chain'))
                ->setAmount($request->input('amount'))
                ->setWithdrawMethod(WithdrawMethodEnum::ON_CHAIN)
                ->setAddress($request->input('address'))
        );

        ExchangeAssetsWithdrawal::query()
            ->create([
                'admin_id' => auth()->id(),
                'withdrawal_id' => $res->getWithdrawId(),
                'currency_fee' => $res->getCurrencyFee(),
                'fee' => $res->getFee(),
                'amount' => $res->getAmount(),
                'actual_amount' => $res->getActualAmount(),
                'hd_wallet_address' => $res->getAddress(),
                'withdrawal_date' => $res->getCreatedAt(),
                'explore_address_url' => $res->getExploreAddress(),
            ]);

        Toast::message('درخواست برداشت ثبت شد و تا دقایقی دیگر منتقل می گردد.')->success()->notify();

        return redirect()->route('exchange-assets-withdrawal.index');
    }

}
