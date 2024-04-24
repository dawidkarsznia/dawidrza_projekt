<?php

namespace App\User\Application\Controller\API;

use App\User\Domain\Entity\User;
use App\User\Domain\Repository\UserRepositoryInterface;
use App\User\Application\ApiResponse\ApiResponseInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;

final class DeleteUserController extends AbstractController
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

    public function deleteUser(Request $request, int $id): JsonResponse
    {
        // Deny access to this function, if the user is not an administrator.
        if (!$this->isGranted(User::ROLE_ADMIN, null)) {
            throw $this->createAccessDeniedException('Access denied.');
        }

        $user = $this->userRepository->findOneBy(['id' => $id]);
        if (null === $user)
        {
            throw new NotFoundHttpException('The desired resource could not be found.');
        }

        $this->userRepository->removeUser($user);

        return $this->apiResponseInterface->createResponse('', 'success', Response::HTTP_OK);
    }
}