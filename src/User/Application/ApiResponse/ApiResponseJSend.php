<?php

namespace App\User\Application\ApiResponse;

use App\User\Application\ApiResponse\ApiResponseInterface;
use Symfony\Component\HttpFoundation\JsonResponse;

final class ApiResponseJSend implements ApiResponseInterface
{
    public function createResponse(string $responseData, string $status, int $code): JsonResponse
    {
        $response = array();

        $response['status'] = $status;
        $response['data'] = json_decode($responseData);

        return new JsonResponse($response, $code);
    }
}