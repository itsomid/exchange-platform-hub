<?php

namespace Tests\Support;

use App\Services\Bot\ReferenceExchange\ExchangeContract;
use App\Services\Bot\ReferenceExchange\ExchangeOrderResult;
use App\Services\Bot\ReferenceExchange\ExchangeOrderStatus;

/**
 * In-memory fake of the reference exchange used by tests. Returns canned
 * results and records every call so assertions can inspect them.
 */
class FakeExchange implements ExchangeContract
{
    /** @var array<int,array<string,mixed>> */
    public array $marketBuys = [];

    /** @var array<int,array<string,mixed>> */
    public array $limitSells = [];

    /** @var array<int,array<string,mixed>> */
    public array $cancels = [];

    /** @var array<int,array<string,mixed>> */
    public array $statusQueries = [];

    private int $nextOrderId = 1000;

    /** @var array<string,ExchangeOrderResult> indexed by order id */
    private array $statusOverrides = [];

    public string $marketBuyFilledAmount = '1';
    public string $marketBuyAvgPrice     = '100';
    public string $marketBuyExchangeFee  = '0.1';
    public ?string $marketBuyFeeCurrency = 'USDT';
    public bool   $failNextMarketBuy     = false;
    public bool   $failNextLimitSell     = false;
    public bool   $failNextMarketSell    = false;

    /** @var array<int,array<string,mixed>> */
    public array $marketSells = [];

    public function placeMarketBuy(string $market, string $quoteAmount): ExchangeOrderResult
    {
        $this->marketBuys[] = compact('market', 'quoteAmount');
        if ($this->failNextMarketBuy) {
            $this->failNextMarketBuy = false;
            return new ExchangeOrderResult(null, ExchangeOrderStatus::FAILED, errorCode: 'TEST', errorMessage: 'forced failure');
        }
        return new ExchangeOrderResult(
            exchangeOrderId: (string) $this->nextOrderId++,
            status:          ExchangeOrderStatus::FILLED,
            filledAmount:    $this->marketBuyFilledAmount,
            avgPrice:        $this->marketBuyAvgPrice,
            exchangeFee:     $this->marketBuyExchangeFee,
            feeCurrency:     $this->marketBuyFeeCurrency,
        );
    }

    public function placeLimitSell(string $market, string $baseAmount, string $price): ExchangeOrderResult
    {
        $this->limitSells[] = compact('market', 'baseAmount', 'price');
        if ($this->failNextLimitSell) {
            $this->failNextLimitSell = false;
            return new ExchangeOrderResult(null, ExchangeOrderStatus::FAILED, errorCode: 'TEST', errorMessage: 'forced failure');
        }
        return new ExchangeOrderResult(
            exchangeOrderId: (string) $this->nextOrderId++,
            status:          ExchangeOrderStatus::OPEN,
        );
    }

    public function placeMarketSell(string $market, string $baseAmount): ExchangeOrderResult
    {
        $this->marketSells[] = compact('market', 'baseAmount');
        if ($this->failNextMarketSell) {
            $this->failNextMarketSell = false;
            return new ExchangeOrderResult(null, ExchangeOrderStatus::FAILED, errorCode: 'TEST', errorMessage: 'forced failure');
        }
        return new ExchangeOrderResult(
            exchangeOrderId: (string) $this->nextOrderId++,
            status:          ExchangeOrderStatus::FILLED,
            filledAmount:    $baseAmount,
            avgPrice:        '1',
            exchangeFee:     '0',
            feeCurrency:     'USDT',
        );
    }

    public function getOrder(string $market, string $exchangeOrderId): ExchangeOrderResult
    {
        $this->statusQueries[] = compact('market', 'exchangeOrderId');
        return $this->statusOverrides[$exchangeOrderId]
            ?? new ExchangeOrderResult($exchangeOrderId, ExchangeOrderStatus::OPEN);
    }

    public function cancelOrder(string $market, string $exchangeOrderId): void
    {
        $this->cancels[] = compact('market', 'exchangeOrderId');
    }

    public function setStatus(string $exchangeOrderId, ExchangeOrderResult $result): void
    {
        $this->statusOverrides[$exchangeOrderId] = $result;
    }
}
