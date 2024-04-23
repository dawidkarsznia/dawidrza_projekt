<?php

namespace App\ResponseFormat;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Create an API response based on the JSend.
 * See https://github.com/omniti-labs/jsend for more information.
 */
class ApiResponseFormat implements ResponseFormatInterface
{
    public const STATUS_SUCCESS = 'success';
    public const STATUS_FAIL = 'fail';
    public const STATUS_ERROR = 'error';

    public function create(?string $responseData, string $responseStatus = ApiResponseFormat::STATUS_SUCCESS, int $responseCode = Response::HTTP_OK): ?Response
    {
        $jsonResponse = array();

        $jsonResponse['status'] = $responseStatus;
        $jsonResponse['data'] = json_decode($responseData);

        return new JsonResponse($jsonResponse, $responseCode);
    }

    public function createSuccess(?string $responseData, int $responseCode = Response::HTTP_OK): ?Response
    {
        return $this->create($responseData, ApiResponseFormat::STATUS_SUCCESS, $responseCode);
    }

    public function createFail(?string $responseData, int $responseCode = Response::BAD_REQUEST): ?Response
    {
        return $this->create($responseData, ApiResponseFormat::STATUS_FAIL, $responseCode);
    }

    public function createError(?string $responseData, int $responseCode = Response::INTERNAL_SERVER_ERROR): ?Response
    {
        return $this->create($responseData, ApiResponseFormat::STATUS_ERROR, $responseCode);
    }
}