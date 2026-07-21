<?php

namespace App\Exceptions;

use Exception;

class InsufficientStockException extends Exception implements ApiExceptionInterface
{
    private string $itemName;
    private string $branchName;
    private float $requested;
    private float $available;

    public static function forItem(string $itemName, string $branchName, float $requested, float $available): self
    {
        $instance = new self(
            "Warning: Low or insufficient stock for item '{$itemName}' at branch '{$branchName}'. " .
            "Requested: {$requested}, Available: {$available}."
        );
        $instance->itemName = $itemName;
        $instance->branchName = $branchName;
        $instance->requested = $requested;
        $instance->available = $available;
        return $instance;
    }

    public function errorCode(): string
    {
        return 'INSUFFICIENT_STOCK';
    }

    public function httpStatus(): int
    {
        return 409;
    }

    public function translationKey(): string
    {
        return 'exceptions.insufficient_stock';
    }

    public function translationParams(): array
    {
        return [
            'item' => $this->itemName,
            'branch' => $this->branchName,
            'requested' => $this->requested,
            'available' => $this->available,
        ];
    }
}
