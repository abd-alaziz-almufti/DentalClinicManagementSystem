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

    public static function forActiveInvoice(): self
    {
        return new self(
            "Cannot modify services on a visit that already has an active invoice. " .
            "The invoice must be cancelled first to reopen the clinical record for editing."
        );
    }
}