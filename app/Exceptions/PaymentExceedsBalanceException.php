<?php

namespace App\Exceptions;

use Exception;

class PaymentExceedsBalanceException extends Exception implements ApiExceptionInterface
{
    private float $amount;
    private float $balance;

    public static function forAmount(float $amount, float $balance): self
    {
        $instance = new self("Payment amount {$amount} exceeds the invoice remaining balance {$balance}.");
        $instance->amount = $amount;
        $instance->balance = $balance;
        return $instance;
    }

    public function errorCode(): string
    {
        return 'PAYMENT_EXCEEDS_BALANCE';
    }

    public function httpStatus(): int
    {
        return 422;
    }

    public function translationKey(): string
    {
        return 'exceptions.payment_exceeds_balance';
    }

    public function translationParams(): array
    {
        return [
            'amount' => $this->amount,
            'balance' => $this->balance,
        ];
    }
}
