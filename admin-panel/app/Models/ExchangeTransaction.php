<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class ExchangeTransaction extends Model
{

    protected $fillable = [
        'order_id',
        'exchange_id',
        'market',
        'currency_symbol',
        'amount',
        'fee',
        'filled_amount',
        'side',
        'response',
        'orderable_id',
        'orderable_type',
    ];

    public function getResponseAttribute($value)
    {
        if (blank($value)) {
            return null;
        }

        return json_decode(json_decode($value, true)); // Decode twice
    }

    /**
     * Normalised order payload of the exchange response.
     *
     * Coinex nests the order under "data" while Binance/MEXC return it flat, so
     * both shapes are mapped onto the same keys used by the report views.
     */
    public function getResponseDataAttribute(): array
    {
        if (! $this->response) {
            return [];
        }

        $response = json_decode(json_encode($this->response), true) ?? [];

        $data = is_array($response['data'] ?? null) ? $response['data'] : $response;

        return [
            'filled_amount' => $data['filled_amount'] ?? $data['executedQty'] ?? null,
            'filled_value' => $data['filled_value'] ?? $data['cummulativeQuoteQty'] ?? null,
            'last_fill_price' => $data['last_fill_price'] ?? $this->averageFillPrice($data),
            'unfilled_amount' => $data['unfilled_amount'] ?? $this->remainingAmount($data),
            'quote_fee' => $data['quote_fee'] ?? $this->quoteFee($data),
            'taker_fee_rate' => $data['taker_fee_rate'] ?? null,
            'created_at' => $data['created_at'] ?? $data['transactTime'] ?? null,
        ];
    }

    private function averageFillPrice(array $data): ?string
    {
        $filledAmount = $data['filled_amount'] ?? $data['executedQty'] ?? null;
        $filledValue = $data['filled_value'] ?? $data['cummulativeQuoteQty'] ?? null;

        if ($filledAmount && $filledValue && (float) $filledAmount != 0.0) {
            return bcdiv((string) $filledValue, (string) $filledAmount, 8);
        }

        $price = $data['price'] ?? null;

        return ($price !== null && (float) $price > 0) ? (string) $price : null;
    }

    private function remainingAmount(array $data): ?string
    {
        if (! isset($data['origQty'])) {
            return null;
        }

        return bcsub((string) $data['origQty'], (string) ($data['executedQty'] ?? '0'), 8);
    }

    /**
     * Commission charged in the quote currency, summed from the order fills.
     */
    private function quoteFee(array $data): ?string
    {
        $quote = $this->quoteAsset();
        $fee = null;

        foreach ($data['fills'] ?? [] as $fill) {
            $asset = strtoupper((string) ($fill['commissionAsset'] ?? ''));

            if ($quote !== null && $asset !== $quote) {
                continue;
            }

            $fee = bcadd($fee ?? '0', (string) ($fill['commission'] ?? '0'), 8);
        }

        return $fee;
    }

    private function quoteAsset(): ?string
    {
        if (! $this->market || ! $this->currency_symbol) {
            return null;
        }

        $market = strtoupper($this->market);
        $base = strtoupper($this->currency_symbol);

        if (str_starts_with($market, $base)) {
            return substr($market, strlen($base)) ?: null;
        }

        return null;
    }

    public function currency()
    {
        return $this->belongsTo(Currency::class, 'currency_symbol', 'symbol');
    }

    public function exchangeMarket()
    {
        return $this->belongsTo(Market::class, 'currency_symbol', 'base_currency');
    }

    public function exchange()
    {
        return $this->belongsTo(Exchange::class, 'exchange_id');
    }

    public function getFormattedCreatedAtAttribute()
    {
        $createdAt = $this->response_data['created_at'] ?? null;

        $date = $createdAt ? Carbon::createFromTimestampMs($createdAt) : $this->created_at;

        return $date ? $date->timezone('Asia/Tehran')->format('H:i:s Y-m-d') : null;
    }
}
