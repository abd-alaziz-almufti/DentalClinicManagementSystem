<?php

namespace App\Exceptions;

use Exception;

class InvalidInvoiceStatusException extends Exception
{
    public static function cannotTransition(string $current, string $target): self
    {
        return new self("Cannot transition invoice status from '{$current}' to '{$target}'.");
    }
}
