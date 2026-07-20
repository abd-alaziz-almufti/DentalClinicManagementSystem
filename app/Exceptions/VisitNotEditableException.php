<?php

namespace App\Exceptions;

use Exception;

class VisitNotEditableException extends Exception
{
    public static function forStatus(string $status): self
    {
        return new self(
            "Cannot modify services on a visit with status '{$status}'. " .
            "Completed visits are immutable — use a reversal/adjustment workflow instead."
        );
    }
}