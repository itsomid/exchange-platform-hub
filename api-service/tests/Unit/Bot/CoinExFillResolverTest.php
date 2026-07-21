<?php

use App\Services\Bot\ReferenceExchange\CoinExFillResolver;

it('nets base_fee out of buy filled_amount and stores base symbol as fee currency (NEO case)', function () {
    $result = CoinExFillResolver::resolve([
        'filled_amount' => '4.91474423',
        'base_fee'      => '0.01474423',
        'quote_fee'     => '0',
    ], '1.99400000', 'buy', 'NEO', 'USDT');

    expect($result['filled_amount'])->toBe('4.90000000');
    expect($result['exchange_fee'])->toBe('0.02939999');
    expect($result['fee_currency'])->toBe('NEO');
    expect($result['gross_filled'])->toBe('4.91474423');
});

it('keeps gross filled_amount on buy when fee is in quote and stores USDT', function () {
    $result = CoinExFillResolver::resolve([
        'filled_amount' => '0.00100000',
        'base_fee'      => '0',
        'quote_fee'     => '0.10000000',
    ], '100000.00000000', 'buy', 'BTC', 'USDT');

    expect($result['filled_amount'])->toBe('0.00100000');
    expect($result['exchange_fee'])->toBe('0.10000000');
    expect($result['fee_currency'])->toBe('USDT');
});

it('does not net base_fee on sell fills but still records base symbol', function () {
    $result = CoinExFillResolver::resolve([
        'filled_amount' => '2.45737211',
        'base_fee'      => '0.00700000',
        'quote_fee'     => '0',
    ], '2.39280000', 'sell', 'NEO', 'USDT');

    expect($result['filled_amount'])->toBe('2.45737211');
    expect($result['fee_currency'])->toBe('NEO');
    expect($result['exchange_fee'])->toBe(bcmul('0.00700000', '2.39280000', 8));
});

it('prefers quote_fee over base_fee when both are present', function () {
    $result = CoinExFillResolver::resolve([
        'filled_amount' => '1.00000000',
        'base_fee'      => '0.01000000',
        'quote_fee'     => '0.05000000',
    ], '10.00000000', 'buy', 'ETH', 'USDT');

    // Quote fee wins for accounting; do not also subtract base_fee.
    expect($result['fee_currency'])->toBe('USDT');
    expect($result['exchange_fee'])->toBe('0.05000000');
    expect($result['filled_amount'])->toBe('1.00000000');
});
