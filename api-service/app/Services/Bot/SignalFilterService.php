<?php

namespace App\Services\Bot;

use App\Models\Bot\BotSignal;
use Illuminate\Support\Collection;

/**
 * Returns all active signals whose [floor_price, ceiling_price] window contains
 * the current live USDT price for the signal's currency.
 *
 * The returned collection is decorated with each signal's live price under
 * the dynamic `live_price` attribute so downstream services don't refetch it.
 */
class SignalFilterService
{
    public function __construct(private readonly PriceFeed $priceFeed) {}

    /**
     * @return Collection<int, BotSignal>
     */
    public function eligibleSignals(): Collection
    {
        $signals = BotSignal::active()->get();

        return $signals->filter(function (BotSignal $signal): bool {
            try {
                $price = $this->priceFeed->getLive((int) $signal->currency_id);
            } catch (\Throwable) {
                return false;
            }

            $floor = (float) $signal->floor_price;
            $ceil  = (float) $signal->ceiling_price;

            if ($price < $floor || $price > $ceil) {
                return false;
            }

            $signal->setAttribute('live_price', $price);

            return true;
        })->values();
    }
}
