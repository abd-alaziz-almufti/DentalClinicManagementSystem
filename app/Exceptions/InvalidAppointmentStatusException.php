<?php

namespace App\Exceptions;

use Exception;

class InvalidAppointmentStatusException extends Exception
{
    public static function cannotCheckIn(string $currentStatus): self
    {
        return new self(
            "Only appointments with status 'scheduled' can be checked in (current status: {$currentStatus})."
        );
    }
}
