<?php

use App\Services\Bot\ReferenceExchange\BinanceBotAdapter;
use App\Services\Bot\ReferenceExchange\ExchangeOrderStatus;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Monolog\Handler\NullHandler;

uses(Tests\TestCase::class);

beforeEach(function () {
    config([
        'cache.default' => 'array',
        'exchanges.binance.base_url' => 'https://binance.test',
        'logging.channels.smart-bot' => ['driver' => 'monolog', 'handler' => NullHandler::class],
    ]);
    Cache::flush();
});

function binanceFake(array $routes): void
{
    Http::fake($routes + [
        'binance.test/api/v3/time' => Http::response(['serverTime' => 1_700_000_000_000]),
        'binance.test/api/v3/exchangeInfo*' => Http::response(['symbols' => [[
            'filters' => [
                ['filterType' => 'PRICE_FILTER', 'tickSize' => '0.01000000'],
                ['filterType' => 'LOT_SIZE', 'stepSize' => '0.00100000', 'minQty' => '0.00100000'],
                ['filterType' => 'MARKET_LOT_SIZE', 'stepSize' => '0.00000000', 'minQty' => '0.00000000'],
            ],
        ]]]),
    ]);
}

function sentOrder(): array
{
    $order = collect(Http::recorded())
        ->map(fn ($pair) => $pair[0])
        ->first(fn (Request $request) => str_contains($request->url(), '/api/v3/order'));

    parse_str((string) parse_url($order->url(), PHP_URL_QUERY), $query);

    return $query;
}

it('market-buys with quoteOrderQty and nets a base-coin fee out of the filled amount', function () {
    binanceFake(['binance.test/api/v3/order*' => Http::response([
        'symbol' => 'NEOUSDT', 'orderId' => 42, 'status' => 'FILLED', 'type' => 'MARKET', 'side' => 'BUY',
        'executedQty' => '10.00000000', 'cummulativeQuoteQty' => '100.00000000',
        'fills' => [['commission' => '0.01000000', 'commissionAsset' => 'NEO']],
    ])]);

    $result = (new BinanceBotAdapter())->placeMarketBuy('NEOUSDT', '100.00000000');

    expect(sentOrder())->toMatchArray(['side' => 'BUY', 'type' => 'MARKET', 'quoteOrderQty' => '100'])
        ->and($result->status)->toBe(ExchangeOrderStatus::FILLED)
        ->and($result->exchangeOrderId)->toBe('42')
        ->and($result->filledAmount)->toBe('9.99000000')
        ->and($result->avgPrice)->toBe('10.00000000')
        ->and($result->exchangeFee)->toBe('0.10000000')
        ->and($result->feeCurrency)->toBe('NEO');
});

it('places a GTC limit sell floored to the lot step and tick size', function () {
    binanceFake(['binance.test/api/v3/order*' => Http::response([
        'symbol' => 'NEOUSDT', 'orderId' => 7, 'status' => 'NEW', 'type' => 'LIMIT', 'side' => 'SELL',
        'executedQty' => '0', 'cummulativeQuoteQty' => '0', 'price' => '12.34', 'fills' => [],
    ])]);

    $result = (new BinanceBotAdapter())->placeLimitSell('NEOUSDT', '3.33333333', '12.34567890');

    expect(sentOrder())->toMatchArray([
        'side' => 'SELL', 'type' => 'LIMIT', 'timeInForce' => 'GTC', 'quantity' => '3.333', 'price' => '12.34',
    ])
        ->and($result->status)->toBe(ExchangeOrderStatus::OPEN)
        ->and($result->exchangeOrderId)->toBe('7');
});

it('rejects a sell below the minimum quantity without calling Binance', function () {
    binanceFake([]);

    $result = (new BinanceBotAdapter())->placeMarketSell('NEOUSDT', '0.0004');

    expect($result->status)->toBe(ExchangeOrderStatus::FAILED)
        ->and($result->errorCode)->toBe('AMOUNT_TOO_SMALL');
    Http::assertNotSent(fn (Request $request) => str_contains($request->url(), '/api/v3/order'));
});

it('reads the fill fee from myTrades when a limit sell is found filled', function () {
    binanceFake([
        'binance.test/api/v3/order*' => Http::response([
            'symbol' => 'NEOUSDT', 'orderId' => 7, 'status' => 'FILLED', 'type' => 'LIMIT', 'side' => 'SELL',
            'executedQty' => '2.00000000', 'cummulativeQuoteQty' => '25.00000000',
        ]),
        'binance.test/api/v3/myTrades*' => Http::response([
            ['commission' => '0.01500000', 'commissionAsset' => 'USDT'],
            ['commission' => '0.01000000', 'commissionAsset' => 'USDT'],
        ]),
    ]);

    $result = (new BinanceBotAdapter())->getOrder('NEOUSDT', '7');

    expect($result->status)->toBe(ExchangeOrderStatus::FILLED)
        ->and($result->filledAmount)->toBe('2')
        ->and($result->avgPrice)->toBe('12.50000000')
        ->and($result->exchangeFee)->toBe('0.02500000')
        ->and($result->feeCurrency)->toBe('USDT');
});

it('maps an expired market order with a partial fill to PARTIAL and a missing order to NOT_FOUND', function () {
    binanceFake(['binance.test/api/v3/order*' => Http::sequence()
        ->push([
            'symbol' => 'NEOUSDT', 'orderId' => 9, 'status' => 'EXPIRED', 'type' => 'MARKET', 'side' => 'BUY',
            'executedQty' => '1.00000000', 'cummulativeQuoteQty' => '10.00000000',
            'fills' => [['commission' => '0.01000000', 'commissionAsset' => 'USDT']],
        ])
        ->push(['code' => -2013, 'msg' => 'Order does not exist.'], 400),
    ]);

    $adapter = new BinanceBotAdapter();

    expect($adapter->placeMarketBuy('NEOUSDT', '20')->status)->toBe(ExchangeOrderStatus::PARTIAL)
        ->and($adapter->getOrder('NEOUSDT', '123')->status)->toBe(ExchangeOrderStatus::NOT_FOUND);
});

it('treats an unknown order on cancel as already gone and throws on other errors', function () {
    binanceFake(['binance.test/api/v3/order*' => Http::sequence()
        ->push(['code' => -2011, 'msg' => 'Unknown order sent.'], 400)
        ->push(['code' => -2015, 'msg' => 'Invalid API-key, IP, or permissions for action.'], 401),
    ]);

    $adapter = new BinanceBotAdapter();
    $adapter->cancelOrder('NEOUSDT', '7');

    expect(fn () => $adapter->cancelOrder('NEOUSDT', '8'))->toThrow(RuntimeException::class, 'binance.cancel.api_error');
});

it('reports connection failures as TRANSPORT so the buy job can retry safely', function () {
    binanceFake(['binance.test/api/v3/order*' => fn () => throw new ConnectionException('Could not resolve host')]);

    $result = (new BinanceBotAdapter())->placeMarketBuy('NEOUSDT', '20');

    expect($result->status)->toBe(ExchangeOrderStatus::FAILED)
        ->and($result->errorCode)->toBe('TRANSPORT');
});
