<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;
use App\Entity\User;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\String\ByteString;

use App\Repository\UserRepository;
use App\ResponseFormat\ResponseFormatInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class ApiController extends AbstractController
{
    private Serializer $userSerializer;
    private UserRepository $userRepository;

    public function __construct(
        private EntityManagerInterface $entityManager,
        private UserPasswordHasherInterface $passwordHasher,
        private ResponseFormatInterface $responseFormat
        )
    {
        $this->userRepository = $entityManager->getRepository(User::class);

        $encoders = [new JsonEncoder()];
        $normalizers = [new ObjectNormalizer()];
        $this->userSerializer = new Serializer($normalizers, $encoders);
    }

    public function loginUser(Request $request): JsonResponse
    {
        $userEmail = $request->headers->get('PHP-AUTH-USER');
        $user = $this->userRepository->findOneBy(['email' => $userEmail]);
        if (null === $user)
        {
            throw new NotFoundHttpException('The desired resource could not be found.');
        }

        // Get the user attributes.
        $jsonData = json_encode([
            'id' => $user->getId(),
            'email' => $userEmail,
            'apiKey' => $user->getApiKey()
        ]);

        return $this->responseFormat->createSuccess($jsonData);
    }

    public function getUserProfile(Request $request): JsonResponse
    {
        $apiKey = $request->headers->get('Authorization');

        $user = $this->userRepository->findOneBy(['apiKey' => $apiKey]);
        if (null === $user)
        {
            throw new NotFoundHttpException('The desired resource could not be found.');
        }

        $jsonData = $this->userSerializer->serialize($user, 'json', [AbstractNormalizer::IGNORED_ATTRIBUTES => ['userIdentifier', 'password', 'apiKey']]);

        return $this->responseFormat->createSuccess($jsonData);
    }
    
    public function generateApiKey(Request $request): JsonResponse
    {
        $apiKey = $request->headers->get(UserController::API_KEY_HEADER_FIELD_NAME);
        $user = $this->userRepository->findOneBy(['apiKey' => $apiKey]);
        if (null === $user)
        {
            throw new NotFoundHttpException('The desired resource could not be found.');
        }

        $generatedApiKey = ByteString::fromRandom(32)->toString();
        $user->setApiKey($generatedApiKey);

        $this->userRepository->update($user);

        $jsonData = json_encode([
            'oldApiKey' => $apiKey,
            'newApiKey' => $generatedApiKey
        ]);

        return $this->responseFormat->createSuccess($jsonData);
    }

    public function getUsersInformation(Request $request): JsonResponse
    {
        // Deny access to this function, if the user is not an administrator.
        $this->denyAccessUnlessGranted(User::ROLE_ADMIN);

        $users = $this->userRepository->findAll();

        $jsonData = $this->userSerializer->serialize($users, 'json', [AbstractNormalizer::IGNORED_ATTRIBUTES => ['userIdentifier', 'password', 'apiKey']]);

        return $this->responseFormat->createSuccess($jsonData);
    }

    public function addUser(Request $request): JsonResponse
    {
        // Deny access to this function, if the user is not an administrator.
        $this->denyAccessUnlessGranted(User::ROLE_ADMIN);

        $jsonRequest = $request->toArray();

        $user = new User();

        $user->setFirstName($jsonRequest['firstName']);
        $user->setLastName($jsonRequest['lastName']);
        $user->setEmail($jsonRequest['email']);
        $user->setRoles($jsonRequest['roles']);

        $user->setActive(true);

        $generatedApiKey = ByteString::fromRandom(32)->toString();
        $user->setApiKey($generatedApiKey);

        $generatedPassword = ByteString::fromRandom(32)->toString();
        $hashedPassword = $this->passwordHasher->hashPassword($user, $generatedPassword);
        $user->setPassword($hashedPassword);

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        return $this->responseFormat->createSuccess(null);
    }

    public function getUserInformation(Request $request, int $id): JsonResponse
    {
        $user = $this->userRepository->find($id);
        if (null === $user)
        {
            throw new NotFoundHttpException('The desired resource could not be found.');
        }

        $jsonData = $this->userSerializer->serialize($user, 'json', [AbstractNormalizer::IGNORED_ATTRIBUTES => ['userIdentifier', 'password', 'apiKey']]);

        return $this->responseFormat->createSuccess($jsonData);
    }

    public function deleteUser(Request $request, int $id): JsonResponse
    {
        // Deny access to this function, if the user is not an administrator.
        $this->denyAccessUnlessGranted(User::ROLE_ADMIN);

        $user = $this->userRepository->findOneBy(['id' => $id]);
        if (null === $user)
        {
            throw new NotFoundHttpException('The desired resource could not be found.');
        }

        $this->userRepository->remove($user);

        return $this->responseFormat->createSuccess(null);
    }

    public function updateUser(Request $request, int $id): JsonResponse
    {
        // Deny access to this function, if the user is not an administrator.
        $this->denyAccessUnlessGranted(User::ROLE_ADMIN);

        $user = $this->userRepository->findOneBy(['id' => $id]);
        if (null === $user)
        {
            throw new NotFoundHttpException('The desired resource could not be found.');
        }

        $jsonRequest = $request->toArray();

        // Check whether a new first name has actually been given.
        // Then, validate and store the result.
        if (true === array_key_exists('firstName', $jsonRequest))
        {
            $user->setFirstName($jsonRequest['firstName']);
        }

        // Check whether a new last name has actually been given.
        // Then, validate and store the result.
        if (true === array_key_exists('lastName', $jsonRequest))
        {
            $user->setLastName($jsonRequest['lastName']);
        }

        // Check whether a new e-mail has actually been given.
        // Then, validate and store the result.
        if (true === array_key_exists('email', $jsonRequest))
        {
            $user->setEmail($jsonRequest['lastName']);
        }

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        return $this->responseFormat->createSuccess(null);
    }

    public function blockUser(Request $request, int $id): JsonResponse
    {
        // Check whether the given user is an administrator.
        if (!$this->isGranted(User::ROLE_ADMIN, null))
        {
            $jsonData = json_encode(['message' => 'You are not authorized to access this resource.']);
            return $this->responseFormat->createFail($jsonData, Response::HTTP_UNAUTHORIZED);
        }

        // Find the user by the 'id' provided.
        $user = $this->userRepository->findOneBy(['id' => $id]);
        if (null === $user)
        {
            throw new NotFoundHttpException('The desired resource could not be found.');
        }

        $user->setActive(false);

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        return $this->responseFormat->createSuccess(null);
    }

    public function unblockUser(Request $request, int $id): JsonResponse
    {
        // Find the user by the 'id' provided.
        $user = $this->userRepository->findOneBy(['id' => $id]);
        if (null === $user)
        {
            throw new NotFoundHttpException('The desired resource could not be found.');
        }

        $user->setActive(true);

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        return $this->responseFormat->createSuccess(null);
    }
}
