<?php

namespace App\Exceptions;

use Exception;

class PaymentExceedsBalanceException extends Exception
{
    public static function forAmount(float $amount, float $balance): self
    {
        return new self("Payment amount {$amount} exceeds the invoice remaining balance {$balance}.");
    }
}
