<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;
use App\Entity\User;

#[Route('/api', name: 'api_')]
class UserController extends AbstractController
{
    #[Route('/user', name: 'app_user', methods: ['get'])]
    public function index(ManagerRegistry $registry): JsonResponse
    {
        $products = $registry->getRepository(User::class)->findAll();

        $encoders = [new JsonEncoder()];
        $normalizers = [new ObjectNormalizer()];
        $serializer = new Serializer($normalizers, $encoders);

        $jsonData = $serializer->serialize($products, 'json');

        return JsonResponse::fromJsonString($jsonData);
    }
}
