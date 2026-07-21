<?php

namespace App\Exceptions;

use Exception;

class InvalidPurchaseStatusException extends Exception implements ApiExceptionInterface
{
    private string $current;
    private string $target;

    public static function cannotTransition(string $current, string $target): self
    {
        $instance = new self("Cannot transition purchase status from '{$current}' to '{$target}'.");
        $instance->current = $current;
        $instance->target = $target;
        return $instance;
    }

    public function errorCode(): string
    {
        return 'INVALID_PURCHASE_STATUS';
    }

    public function httpStatus(): int
    {
        return 409;
    }

    public function translationKey(): string
    {
        return 'exceptions.invalid_purchase_status';
    }

    public function translationParams(): array
    {
        return [
            'current' => $this->current,
            'target' => $this->target,
        ];
    }
}
