<?php

namespace App\Exceptions;

interface ApiExceptionInterface
{
    public function errorCode(): string;

    public function httpStatus(): int;

    public function translationKey(): string;

    public function translationParams(): array;
}
