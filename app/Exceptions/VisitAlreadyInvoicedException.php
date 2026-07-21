<?php

namespace App\Exceptions;

use Exception;

class VisitAlreadyInvoicedException extends Exception implements ApiExceptionInterface
{
    private int $visitId;

    public static function forVisit(int $visitId): self
    {
        $instance = new self("Visit #{$visitId} is already invoiced. At most one invoice per visit is allowed.");
        $instance->visitId = $visitId;
        return $instance;
    }

    public function errorCode(): string
    {
        return 'VISIT_ALREADY_INVOICED';
    }

    public function httpStatus(): int
    {
        return 409;
    }

    public function translationKey(): string
    {
        return 'exceptions.visit_already_invoiced';
    }

    public function translationParams(): array
    {
        return [
            'visit_id' => $this->visitId,
        ];
    }
}
