<?php

namespace App\User\Application\Controller\API;

use App\User\Domain\Entity\User;
use App\User\Application\ApiResponse\ApiResponseInterface;
use App\User\Domain\Repository\UserRepositoryInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

final class BlockUserController extends AbstractController
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

    public function blockUser(Request $request, int $id): JsonResponse
    {
        // Deny access to this function, if the user is not an administrator.
        $this->denyAccessUnlessGranted(User::ROLE_ADMIN);

        // Find the user by the 'id' provided.
        $user = $this->userRepository->findOneUserBy(['id' => $id]);
        if (null === $user)
        {
            throw new NotFoundHttpException('The desired resource could not be found.');
        }

        $user->setActive(false);

        $this->userRepository->saveUser($user);

        return $this->apiResponseInterface->createResponse('', 'success', Response::HTTP_OK);
    }

    public function unblockUser(Request $request, int $id): JsonResponse
    {
        $this->denyAccessUnlessGranted(User::ROLE_ADMIN);

        $user = $this->userRepository->findOneBy(['id' => $id]);
        if (null === $user)
        {
            throw new NotFoundHttpException('The desired resource could not be found.');
        }

        $user->setActive(true);

        $this->userRepository->saveUser($user);

        return $this->apiResponseInterface->createResponse('', 'success', Response::HTTP_OK);
    }
}