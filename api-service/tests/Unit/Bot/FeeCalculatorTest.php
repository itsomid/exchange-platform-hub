<?php

use App\Exceptions\Bot\BotTransferAmountTooLowException;
use App\Services\Bot\FeeCalculator;

it('throws exception for amount below 20', function () {
    $calc = new FeeCalculator();
    expect(fn () => $calc->transferFee('19'))->toThrow(BotTransferAmountTooLowException::class);
    expect(fn () => $calc->transferFee('0'))->toThrow(BotTransferAmountTooLowException::class);
    expect(fn () => $calc->transferFee('19.99999999'))->toThrow(BotTransferAmountTooLowException::class);
});

it('charges 1 USDT flat for amounts 20 to 100', function () {
    $calc = new FeeCalculator();
    expect($calc->transferFee('20'))->toBe('1.00000000');
    expect($calc->transferFee('50'))->toBe('1.00000000');
    expect($calc->transferFee('100'))->toBe('1.00000000');
});

it('charges 1 percent for amounts above 100 to 1000', function () {
    $calc = new FeeCalculator();
    // 101 * 1% = 1.01
    expect($calc->transferFee('101'))->toBe('1.01000000');
    // 500 * 1% = 5
    expect($calc->transferFee('500'))->toBe('5.00000000');
    // 1000 * 1% = 10
    expect($calc->transferFee('1000'))->toBe('10.00000000');
});

it('charges 12 USDT flat for amounts above 1000', function () {
    $calc = new FeeCalculator();
    expect($calc->transferFee('1001'))->toBe('12.00000000');
    expect($calc->transferFee('5000'))->toBe('12.00000000');
});

it('applies same fee schedule to withdrawFee', function () {
    $calc = new FeeCalculator();
    expect($calc->withdrawFee('20'))->toBe('1.00000000');
    expect($calc->withdrawFee('500'))->toBe('5.00000000');
    expect($calc->withdrawFee('1001'))->toBe('12.00000000');
});
