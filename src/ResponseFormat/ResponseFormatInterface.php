<?php

namespace App\ResponseFormat;

use Symfony\Component\HttpFoundation\Response;

interface ResponseFormatInterface
{
    public function createSuccess(?string $responseData, int $responseCode): ?Response;
    public function createFail(?string $responseData, int $responseCode): ?Response;
    public function createError(?string $responseData, int $responseCode): ?Response;
}