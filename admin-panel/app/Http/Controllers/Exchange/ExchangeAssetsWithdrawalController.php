<?php

namespace App\Http\Controllers\Exchange;

use App\Http\Controllers\Controller;
use App\Models\ExchangeAssetsWithdrawal;
use App\Services\Exchanges\Asset\AssetFactory;
use App\Services\Exchanges\Asset\DTO\WithdrawRequestDTO;
use App\Services\Exchanges\Asset\Enum\WithdrawMethodEnum;
use Illuminate\Support\Facades\Request;

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
    }

}
