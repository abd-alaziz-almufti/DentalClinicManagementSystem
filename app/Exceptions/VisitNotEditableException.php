<?php

namespace App\Exceptions;

use Exception;

class VisitNotEditableException extends Exception implements ApiExceptionInterface
{
    private string $statusVal = '';
    private string $key = 'exceptions.visit_not_editable';

    public static function forStatus(string $status): self
    {
        $instance = new self(
            "Cannot modify services on a visit with status '{$status}'. " .
            "Completed visits are immutable — use a reversal/adjustment workflow instead."
        );
        $instance->statusVal = $status;
        $instance->key = 'exceptions.visit_not_editable';
        return $instance;
    }

    public static function forActiveInvoice(): self
    {
        $instance = new self(
            "Cannot modify services on a visit that already has an active invoice. " .
            "The invoice must be cancelled first to reopen the clinical record for editing."
        );
        $instance->statusVal = 'invoiced';
        $instance->key = 'exceptions.visit_already_invoiced';
        return $instance;
    }

    public function errorCode(): string
    {
        return 'VISIT_NOT_EDITABLE';
    }

    public function httpStatus(): int
    {
        return 409;
    }

    public function translationKey(): string
    {
        return $this->key;
    }

    public function translationParams(): array
    {
        return [
            'status' => $this->statusVal,
        ];
    }
}