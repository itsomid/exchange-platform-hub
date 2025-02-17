<?php

namespace App\Console\Commands;

use App\Helpers\Math;
use App\Models\Currency;
use App\Models\CurrencyChain;
use App\Repositories\Interfaces\OTCRefExchangeWithdrawalInterface;
use App\Services\Exchanges\Asset\Enum\WithdrawStatusEnum;
use App\Services\Exchanges\DTO\ChargeUSDTRequestDTO;
use App\Services\Exchanges\ExchangeService;
use Illuminate\Console\Command;
use Throwable;

class ChargeUSDT extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'bitexroom:charge-usdt';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $currency = Currency::query()->where('symbol', 'USDT')->first();
        $chain = CurrencyChain::query()
            ->where('chain', 'BSC')
            ->where('currency_id', $currency->id)
            ->first();

        $otcRefExchangeWithdrawalRepository = resolve(OTCRefExchangeWithdrawalInterface::class);
        $pendingLists = $otcRefExchangeWithdrawalRepository->getPending();
        $usdtNeeded = 0;
        foreach ($pendingLists as $data) {
            $usdtNeeded = Math::add($usdtNeeded, $data->transaction->amount);
        }
        $usdtNeeded = abs($usdtNeeded);
        try {
            $exchangeService = resolve(ExchangeService::class);
            $chargeFromRefExchange = $exchangeService->chargeUSDT(
                resolve(ChargeUSDTRequestDTO::class)
                    ->setCurrencyChain($chain->chain->value)
                    ->setQuantity($usdtNeeded)
            );

            if ($chargeFromRefExchange->getWithdrawStatus() !== WithdrawStatusEnum::FAILED) {
                $otcRefExchangeWithdrawalRepository->completeLists(
                    $pendingLists->pluck('id')->toArray()
                );
                $this->info('USDT Withdrawal successful. status : '.$chargeFromRefExchange->getWithdrawStatus()->value);
            } else {
                $this->error('USDT Withdrawal failed. status : '.$chargeFromRefExchange->getWithdrawStatus()->value);
            }
        } catch (Throwable $exception) {
            report($exception);
            $this->error($exception->getMessage());
        }

    }
}
