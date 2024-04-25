<?php

namespace App\User\Application\Controller\API;

use App\User\Domain\Entity\User;
use App\User\Application\ApiResponse\ApiResponseInterface;
use App\User\Domain\Repository\UserRepositoryInterface;
use App\User\Application\Service\GenerateApiKeyService;
use App\User\Application\Service\GeneratePasswordService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

final class ResetUserController extends AbstractController
{
    private UserRepositoryInterface $userRepository;
    private ApiResponseInterface $apiResponseInterface;
    private GenerateApiKeyService $generateApiKeyService;
    private GeneratePasswordService $generatePasswordService;

    public function __construct(
        UserRepositoryInterface $userRepository,
        ApiResponseInterface $apiResponseInterface,
        GenerateApiKeyService $generateApiKeyService,
        GeneratePasswordService $generatePasswordService
    )
    {
        $this->userRepository = $userRepository;
        $this->apiResponseInterface = $apiResponseInterface;
        $this->generateApiKeyService = $generateApiKeyService;
        $this->generatePasswordService = $generatePasswordService;
    }

    public function resetUserApiKey(Request $request): JsonResponse
    {
        $apiKey = $request->headers->get('Authorization');
        $user = $this->userRepository->findOneUserBy(['apiKey' => $apiKey]);
        if (null === $user)
        {
            throw new NotFoundHttpException('The desired resource could not be found.');
        }

        $generatedApiKey = $this->generateApiKeyService->handle($user);
        $user->setApiKey($generatedApiKey);

        $this->userRepository->saveUser($user);

        $jsonData = json_encode([
            'oldApiKey' => $apiKey,
            'newApiKey' => $generatedApiKey
        ]);

        return $this->apiResponseInterface->createResponse($jsonData, 'success', Response::HTTP_OK);
    }

    public function resetUserPassword(Request $request): JsonResponse
    {
        $apiKey = $request->headers->get('Authorization');
        $user = $this->userRepository->findOneUserBy(['apiKey' => $apiKey]);
        if (null === $user)
        {
            throw new NotFoundHttpException('The desired resource could not be found.');
        }

        $plainPassword = $this->generatePasswordService->handle($user);

        return $this->apiResponseInterface->createResponse('', 'success', Response::HTTP_OK);
    }
}