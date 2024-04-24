<?php

namespace App\User\Application\Controller\API;

use App\User\Domain\Repository\UserRepositoryInterface;
use App\User\Application\ApiResponse\ApiResponseInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;

final class LoginUserController extends AbstractController
{
    private UserRepositoryInterface $userRepository;
    private ApiResponseInterface $apiResponseInterface;

    public function __construct(
        UserRepositoryInterface $userRepository,
        ApiResponseInterface $apiResponseInterface
    )
    {
        $this->userRepository = $userRepository;
        $this->apiResponseInterface = $apiResponseInterface;
    }

    public function loginUser(Request $request): JsonResponse
    {
        $userEmail = $request->headers->get('PHP-AUTH-USER');
        $user = $this->userRepository->findOneUserBy(['email' => $userEmail]);
        if (null === $user)
        {
            throw new NotFoundHttpException('The desired resource could not be found.');
        }

        $jsonData = json_encode([
            'id' => $user->getId(),
            'email' => $userEmail,
            'apiKey' => $user->getApiKey()
        ]);

        return $this->apiResponseInterface->createResponse($jsonData, 'success', Response::HTTP_OK);
    }
}