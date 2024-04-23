<?php

namespace App\User\Application\Controller\API;

use App\User\Domain\Repository\UserRepositoryInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;

final class LoginUserController extends AbstractController
{
    private UserRepositoryInterface $userRepository;

    public function __construct(
        UserRepositoryInterface $userRepository
    )
    {
        $this->userRepository = $userRepository;
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

        return JsonResponse::fromJsonString($jsonData);
    }
}