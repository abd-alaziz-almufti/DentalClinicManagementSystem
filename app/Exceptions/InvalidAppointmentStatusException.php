<?php

namespace App\Exceptions;

use Exception;

class InvalidAppointmentStatusException extends Exception implements ApiExceptionInterface
{
    private string $currentStatus;

    public static function cannotCheckIn(string $currentStatus): self
    {
        $instance = new self(
            "Only appointments with status 'scheduled' can be checked in (current status: {$currentStatus})."
        );
        $instance->currentStatus = $currentStatus;
        return $instance;
    }

    public function errorCode(): string
    {
        return 'INVALID_APPOINTMENT_STATUS';
    }

    public function httpStatus(): int
    {
        return 409;
    }

    public function translationKey(): string
    {
        return 'exceptions.invalid_appointment_status';
    }

    public function translationParams(): array
    {
        return [
            'current' => $this->currentStatus,
            'target' => 'checked_in',
        ];
    }
}
