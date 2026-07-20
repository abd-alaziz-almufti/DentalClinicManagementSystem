<?php

namespace App\Exceptions;

use Exception;

class InsufficientStockException extends Exception
{
    public static function forItem(string $itemName, string $branchName, float $requested, float $available): self
    {
        return new self(
            "Warning: Low or insufficient stock for item '{$itemName}' at branch '{$branchName}'. " .
            "Requested: {$requested}, Available: {$available}."
        );
    }
}
