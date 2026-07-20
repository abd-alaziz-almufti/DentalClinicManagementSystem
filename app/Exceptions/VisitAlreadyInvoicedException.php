<?php

namespace App\Exceptions;

use Exception;

class VisitAlreadyInvoicedException extends Exception
{
    public static function forVisit(int $visitId): self
    {
        return new self("Visit #{$visitId} is already invoiced. At most one invoice per visit is allowed.");
    }
}
