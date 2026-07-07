<?php

namespace App\Services\Bot;

use App\Models\Bot\BotSignal;
use Illuminate\Support\Collection;

/**
 * Classifies active signals by their live USDT price into three outcomes:
 *
 *   - eligible: a price is available AND falls within [floor, ceiling]. These
 *     signals are decorated with the price under the dynamic `live_price`
 *     attribute so downstream services don't refetch it.
 *   - unpriced: the price could NOT be determined at all (missing from both
 *     the socket cache and the exchange_prices table, or the feed errored).
 *     This is a data/infrastructure problem — NOT a "no opportunity" outcome —
 *     so the caller can surface it as a buy failure instead of silently
 *     pretending there was simply nothing to buy.
 *   - (dropped): a price exists but sits outside the window. This is a normal
 *     no-opportunity case and is silently excluded from both lists.
 */
class SignalFilterService
{
    public function __construct(private readonly PriceFeed $priceFeed) {}

    /**
     * @return array{eligible: Collection<int, BotSignal>, unpriced: Collection<int, BotSignal>}
     */
    public function classify(): array
    {
        $eligible = collect();
        $unpriced = collect();

        foreach (BotSignal::active()->get() as $signal) {
            try {
                $price = $this->priceFeed->getLive((int) $signal->currency_id);
            } catch (\Throwable) {
                $unpriced->push($signal);
                continue;
            }

            $floor = (float) $signal->floor_price;
            $ceil  = (float) $signal->ceiling_price;

            if ($price < $floor || $price > $ceil) {
                continue;
            }

            $signal->setAttribute('live_price', $price);
            $eligible->push($signal);
        }

        return [
            'eligible' => $eligible->values(),
            'unpriced' => $unpriced->values(),
        ];
    }
}
