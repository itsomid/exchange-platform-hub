<?php

namespace Tests\Unit\Bot;

use App\Http\Requests\Bot\StoreBotSignalRequest;
use Tests\TestCase;

class StoreBotSignalRequestTest extends TestCase
{
    public function test_prepare_for_validation_strips_commas_from_numeric_fields(): void
    {
        $request = StoreBotSignalRequest::create('/test', 'POST', [
            'floor_price'                  => '50,000',
            'ceiling_price'                => '70,000',
            'min_buy_amount_usdt'          => '1,000',
            'p2p_min_order_value_override' => '5,000',
        ]);

        $method = new \ReflectionMethod($request, 'prepareForValidation');
        $method->invoke($request);

        $this->assertSame('50000', $request->input('floor_price'));
        $this->assertSame('70000', $request->input('ceiling_price'));
        $this->assertSame('1000', $request->input('min_buy_amount_usdt'));
        $this->assertSame('5000', $request->input('p2p_min_order_value_override'));
    }
}
