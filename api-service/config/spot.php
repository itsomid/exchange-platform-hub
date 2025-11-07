<?php

return [
    'order_book_limit_count' => env('SPOT_ORDER_BOOK_LIMIT_COUNT', 6),
    // Maximum allowed deviation (%) for a user LIMIT order price from best opposite price
    // If exceeded, order will be rejected with an error. Default 10.
    'spot_limit_max_deviation_percent' => env('SPOT_LIMIT_MAX_DEVIATION_PERCENT', 10),
];
