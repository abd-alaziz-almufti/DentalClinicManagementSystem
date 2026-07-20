<?php

namespace App\Exceptions;

use Exception;

class InvalidPurchaseStatusException extends Exception
{
    public static function cannotTransition(string $current, string $target): self
    {
        return new self("Cannot transition purchase status from '{$current}' to '{$target}'.");
    }
}
