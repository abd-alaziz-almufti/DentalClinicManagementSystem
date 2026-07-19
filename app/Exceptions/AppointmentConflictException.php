<?php

namespace App\Exceptions;

use Exception;

class AppointmentConflictException extends Exception
{
    public static function forSlot(string $date, string $start, string $end): self
    {
        return new self(
            "The doctor already has an appointment overlapping {$date} {$start}-{$end}."
        );
    }
}
