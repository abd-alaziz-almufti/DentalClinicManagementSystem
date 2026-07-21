<?php

namespace App\Exceptions;

use Exception;

class AppointmentConflictException extends Exception implements ApiExceptionInterface
{
    private string $date;
    private string $start;
    private string $end;

    public static function forSlot(string $date, string $start, string $end): self
    {
        $instance = new self(
            "The doctor already has an appointment overlapping {$date} {$start}-{$end}."
        );
        $instance->date = $date;
        $instance->start = $start;
        $instance->end = $end;
        return $instance;
    }

    public function errorCode(): string
    {
        return 'APPOINTMENT_CONFLICT';
    }

    public function httpStatus(): int
    {
        return 409;
    }

    public function translationKey(): string
    {
        return 'exceptions.appointment_conflict';
    }

    public function translationParams(): array
    {
        return [
            'date' => $this->date,
            'start' => $this->start,
            'end' => $this->end,
        ];
    }
}
