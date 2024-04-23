<?php

namespace App\User\Application\Controller\API;

use App\User\Domain\Repository\UserRepositoryInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;

final class GetUserController extends AbstractController
{
    private UserRepositoryInterface $userRepository;
    private SerializerInterface $serializer;

    public function __construct(
        UserRepositoryInterface $userRepository,
        SerializerInterface $serializer
    )
    {
        $this->userRepository = $userRepository;
        $this->serializer = $serializer;
    }

    public function getUsersInformation(Request $request): JsonResponse
    {
        // Deny access to this function, if the user is not an administrator.
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $paginationPage = $request->query->get('page') ?? 1;
        $paginationLimit = $request->query->get('pageLimit') ?? 10;

        $users = $this->userRepository->findAllUsers($paginationPage, $paginationLimit);

        $jsonData = $this->serializer->serialize($users, 'json', [AbstractNormalizer::IGNORED_ATTRIBUTES => ['userIdentifier', 'password', 'apiKey']]);

        return JsonResponse::fromJsonString($jsonData);
    }

    public function getUserInformation(Request $request, int $id): JsonResponse
    {
        // Deny access to this function, if the user is not an administrator.
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $user = $this->userRepository->findUser($id);
        if (null === $user)
        {
            throw new NotFoundHttpException('The desired resource could not be found.');
        }

        $jsonData = $this->serializer->serialize($user, 'json', [AbstractNormalizer::IGNORED_ATTRIBUTES => ['userIdentifier', 'password', 'apiKey']]);

        return JsonResponse::fromJsonString($jsonData);
    }

    public function getUserProfile(Request $request): JsonResponse
    {
        $apiKey = $request->headers->get('Authorization');

        $user = $this->userRepository->findOneBy(['apiKey' => $apiKey]);
        if (null === $user)
        {
            throw new NotFoundHttpException('The desired resource could not be found.');
        }

        $jsonData = $this->serializer->serialize($user, 'json', [AbstractNormalizer::IGNORED_ATTRIBUTES => ['userIdentifier', 'password', 'apiKey']]);

        return JsonResponse::fromJsonString($jsonData);
    }
}