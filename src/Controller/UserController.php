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

#[Route('/api', name: 'api_')]
class UserController extends AbstractController
{
    public const API_KEY_HEADER_FIELD_NAME = 'Authorization';

    public const API_RESPONSE_STATUS_SUCCESS = 'success';
    public const API_RESPONSE_STATUS_FAIL = 'fail';
    public const API_RESPONSE_STATUS_ERROR = 'error';

    private Serializer $userSerializer;

    public function __construct(private UserPasswordHasherInterface $passwordHasher)
    {
        $encoders = [new JsonEncoder()];
        $normalizers = [new ObjectNormalizer()];
        $this->userSerializer = new Serializer($normalizers, $encoders);
    }

    /**
     * Create an API response based on the JSend.
     * See https://github.com/omniti-labs/jsend for more information.
     */
    public function createApiResponse(?string $responseData, string $responseStatus = UserController::API_RESPONSE_STATUS_SUCCESS, int $responseCode = Response::HTTP_OK): JsonResponse
    {
        $jsonResponse = array();

        $jsonResponse['status'] = $responseStatus;
        $jsonResponse['data'] = json_decode($responseData);

        return new JsonResponse($jsonResponse, $responseCode);
    }

    #[Route('/login', name: 'app_login', methods: ['get'])]
    public function loginUser(ManagerRegistry $registry, Request $request): JsonResponse
    {
        $userEmail = $request->headers->get('PHP-AUTH-USER');
        $user = $registry->getRepository(User::class)->findOneBy(['email' => $userEmail]);

        // Get the user attributes.
        $userAttributes = [
            'id' => $user->getId(),
            'email' => $userEmail,
            'apiKey' => $user->getApiKey()
        ];

        return $this->createApiResponse($userAttributes);
    }

    #[Route('/profile', name: 'app_get_profile', methods: ['get'])]
    public function getUserProfile(ManagerRegistry $registry, Request $request): JsonResponse
    {
        $apiKey = $request->headers->get(UserController::API_KEY_HEADER_FIELD_NAME);
        $user = $registry->getRepository(User::class)->findOneBy(['apiKey' => $apiKey]);

        $jsonData = $this->userSerializer->serialize($user, 'json', [AbstractNormalizer::IGNORED_ATTRIBUTES => ['userIdentifier', 'password', 'apiKey']]);

        return $this->createApiResponse($jsonData);
    }
    
    #[Route('/api-key', name: 'app_get_new_key', methods: ['get'])]
    public function generateApiKey(ManagerRegistry $registry, Request $request): JsonResponse
    {
        $apiKey = $request->headers->get(UserController::API_KEY_HEADER_FIELD_NAME);
        $user = $registry->getRepository(User::class)->findOneBy(['apiKey' => $apiKey]);

        $generatedApiKey = ByteString::fromRandom(32)->toString();
        $user->setApiKey($generatedApiKey);

        $registry->getRepository(User::class)->update($user);

        $jsonData['oldApiKey'] = $apiKey;
        $jsonData['newApiKey'] = $generatedApiKey;
        $jsonData = json_encode($jsonData);

        return $this->createApiResponse($jsonData);
    }

    #[Route('/users', name: 'app_get_users', methods: ['get'])]
    public function getUsersInformation(Request $request, ManagerRegistry $registry): JsonResponse
    {
        // Deny access to this function, if the user is not an administrator.
        $this->denyAccessUnlessGranted(User::ROLE_ADMIN);

        $users = $registry->getRepository(User::class)->findAll();

        $jsonData = $this->userSerializer->serialize($users, 'json', [AbstractNormalizer::IGNORED_ATTRIBUTES => ['userIdentifier', 'password', 'apiKey']]);

        return $this->createApiResponse($jsonData);
    }

    #[Route('/users', name: 'app_add_user', methods: ['post'])]
    public function addUser(Request $request, ManagerRegistry $registry): JsonResponse
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

        $registry->getRepository(User::class)->update($user);

        return $this->createApiResponse(null);
    }

    #[Route('/users/{id}', name: 'app_get_user', methods: ['get'])]
    public function getUserInformation(Request $request, ManagerRegistry $registry, int $id): JsonResponse
    {
        $user = $registry->getRepository(User::class)->find($id);

        $jsonData = $this->userSerializer->serialize($user, 'json', [AbstractNormalizer::IGNORED_ATTRIBUTES => ['userIdentifier', 'password', 'apiKey']]);

        return $this->createApiResponse($jsonData);
    }

    #[Route('/users/{id}', name: 'app_user_delete', methods: ['delete'])]
    public function deleteUser(Request $request, ManagerRegistry $registry, int $id): JsonResponse
    {
        // Deny access to this function, if the user is not an administrator.
        $this->denyAccessUnlessGranted(User::ROLE_ADMIN);

        $user = $registry->getRepository(User::class)->findOneBy(['id' => $id]);

        $registry->getRepository(User::class)->remove($user);

        return $this->createApiResponse(null);
    }

    #[Route('/users/{id}', name: 'app_user_update', methods: ['patch'])]
    public function updateUser(Request $request, ManagerRegistry $registry, int $id): JsonResponse
    {
        // Deny access to this function, if the user is not an administrator.
        $this->denyAccessUnlessGranted(User::ROLE_ADMIN);

        $user = $registry->getRepository(User::class)->findOneBy(['id' => $id]);
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

        $registry->getRepository(User::class)->update($user);

        return $this->createApiResponse(null);
    }

    #[Route('/users/{id}/block', name: 'app_user_block', methods: ['post'])]
    public function blockUser(Request $request, ManagerRegistry $registry, int $id): JsonResponse
    {
        // Deny access to this function, if the user is not an administrator.
        $this->denyAccessUnlessGranted(User::ROLE_ADMIN);

        $user = $registry->getRepository(User::class)->findOneBy(['id' => $id]);
        $user->setActive(false);

        $registry->getRepository(User::class)->update($user);

        return $this->createApiResponse(null);
    }

    #[Route('/users/{id}/unblock', name: 'app_user_unblock', methods: ['post'])]
    public function unblockUser(Request $request, ManagerRegistry $registry, int $id): JsonResponse
    {
        // Deny access to this function, if the user is not an administrator.
        $this->denyAccessUnlessGranted(User::ROLE_ADMIN);

        $user = $registry->getRepository(User::class)->findOneBy(['id' => $id]);
        $user->setActive(true);

        $registry->getRepository(User::class)->update($user);

        return $this->createApiResponse(null);
    }
}
