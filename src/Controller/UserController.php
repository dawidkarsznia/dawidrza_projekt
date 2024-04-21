<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;
use App\Entity\User;

#[Route('/api', name: 'api_')]
class UserController extends AbstractController
{
    private Serializer $userSerializer;

    public function __construct()
    {
        $encoders = [new JsonEncoder()];
        $normalizers = [new ObjectNormalizer()];
        $this->userSerializer = new Serializer($normalizers, $encoders);
    }

    #[Route('/user/{id}', name: 'app_user', methods: ['get'])]
    public function getUserInformation(ManagerRegistry $registry, int $id): JsonResponse
    {
        $user = $registry->getRepository(User::class)->find($id);

        $jsonData = $this->userSerializer->serialize($user, 'json');

        return JsonResponse::fromJsonString($jsonData);
    }

    #[Route('/users', name: 'app_users', methods: ['get'])]
    public function getUsersInformation(ManagerRegistry $registry): JsonResponse
    {
        $users = $registry->getRepository(User::class)->findAll();

        $jsonData = $this->userSerializer->serialize($users, 'json');

        return JsonResponse::fromJsonString($jsonData);
    }
}
