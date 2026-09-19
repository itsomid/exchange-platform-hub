<?php

namespace App\Services\Bot\ReferenceExchange;

enum ExchangeOrderStatus: string
{
    case OPEN      = 'OPEN';      // accepted, sitting in orderbook
    case FILLED    = 'FILLED';    // fully filled
    case PARTIAL   = 'PARTIAL';   // partially filled (still active)
    case CANCELED  = 'CANCELED';  // canceled (by us or by the exchange)
    case NOT_FOUND = 'NOT_FOUND'; // exchange does not know about this order id
    case FAILED    = 'FAILED';    // request failed (network / auth / validation)
}
