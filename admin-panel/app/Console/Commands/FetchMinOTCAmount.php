<?php

namespace App\Console\Commands;

use App\Models\Market;
use App\Repositories\ExchangeRepository;
use App\Services\Exchanges\ExchangeData\ExchangeDataFactory;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Throwable;

class FetchMinOTCAmount extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'exchange:fetch-min-otc-amount';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fetch the minimum trade amount from the active exchange';

    public function __construct(
        protected ExchangeRepository $exchangeRepository
    ) {
        parent::__construct();
    }

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        $activeExchange = $this->exchangeRepository->getActiveExchange();
        if (!$activeExchange) {
            $this->warn('No active exchange found.');
            return;
        }

        $service = ExchangeDataFactory::make($activeExchange->slug);
        try {
            $fetchMarkets = collect($service->fetchMinTrade())->keyBy('market');
        } catch (Throwable $e) {
            $this->skip("Min trade sync aborted: {$e->getMessage()}");
            return;
        }

        $markets = Market::where('is_active', true)->get();

        foreach ($markets as $market) {
            $symbol = $market->base_currency . $market->quote_currency;
            $minAmount = $fetchMarkets[$symbol]['min_amount'] ?? null;
            if ($minAmount === null) {
                $this->skip("Skipped {$symbol}: not an active trading pair on {$activeExchange->name}");
                continue;
            }

            try {
                $market->update([
                    'min_trade_amount' => $minAmount,
                    'min_otc_amount' => $minAmount,
                ]);
                $this->info("Update min_otc_amount {$symbol} : {$minAmount}");
            } catch (Throwable $e) {
                $this->skip("Skipped {$symbol}: {$e->getMessage()}");
            }
        }
    }

    private function skip(string $message): void
    {
        $this->warn($message);
        Log::warning("Min trade: {$message}");
    }
}
