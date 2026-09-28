<?php

namespace App\Exceptions\Exchange;

use Exception;

/**
 * A request to the reference exchange failed: unreachable, rejected by its API,
 * or the requested order/market was not found. The message is safe to show to admins.
 */
class RefExchangeRequestException extends Exception {}
