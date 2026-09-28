<?php

namespace App\Exceptions\Exchange;

use Exception;

/**
 * The reference exchange reached us but rejected the withdrawal. The message is
 * the exchange's reason and is shown to admins.
 */
class RefExchangeWithdrawalException extends Exception {}
