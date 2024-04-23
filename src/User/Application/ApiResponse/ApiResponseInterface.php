<?php

namespace App\User\Application\ApiResponse;

use Symfony\Component\HttpFoundation\JsonResponse;

interface ApiResponseInterface
{
    public function createResponse(string $responseData, string $status, int $code): JsonResponse;
}