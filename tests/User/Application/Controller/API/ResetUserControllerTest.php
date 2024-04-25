<?php

namespace App\Tests\User\Application\Controller\API;

use App\User\Domain\Entity\User;
use App\User\Domain\Repository\UserRepositoryInterface;
use App\User\Application\Service\GeneratePasswordService;
use App\User\Application\Service\GenerateApiKeyService;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;

class ResetUserControllerTest extends WebTestCase
{
    private const AUTHORIZED_USER_FIRST_NAME = 'Jan';
    private const AUTHORIZED_USER_LAST_NAME = 'Kowalski';
    private const AUTHORIZED_USER_EMAIL = 'jankowalski@gmail.com';

    public function testResetUserApiKeyWrongKey(): void
    {
        $client = static::createClient();
        $userRepository = static::getContainer()->get(UserRepositoryInterface::class);

        $authorizedUser = new User();
        User::registerUser($authorizedUser, $this::AUTHORIZED_USER_FIRST_NAME, $this::AUTHORIZED_USER_LAST_NAME, $this::AUTHORIZED_USER_EMAIL, [User::ROLE_ADMIN], 'testPasswordHash', '');
        $userRepository->saveUser($authorizedUser);

        $generateApiKeyService = static::getContainer()->get(GenerateApiKeyService::class);
        $apiKey = $generateApiKeyService->handle($authorizedUser);
        $userRepository->saveUser($authorizedUser);

        $client->setServerParameter('HTTP_AUTHORIZATION', 'incorrectApiKey');

        $crawler = $client->request('POST', '/api/reset-key');

        $response = $client->getResponse();
        $responseCode = $response->getStatusCode();
        $responseContent = json_decode($response->getContent(), true);

        $this->assertEquals($responseCode, Response::HTTP_NOT_FOUND);
        $this->assertEquals($responseContent['status'], 'fail');

        $commitedUser = $userRepository->findOneUserBy(['id' => $authorizedUser->getId()]);
        $this->assertEquals($commitedUser->getApiKey(), $apiKey);

        $userRepository->removeUser($authorizedUser);
    }

    public function testResetUserApiKey(): void
    {
        $client = static::createClient();
        $userRepository = static::getContainer()->get(UserRepositoryInterface::class);

        $authorizedUser = new User();
        User::registerUser($authorizedUser, $this::AUTHORIZED_USER_FIRST_NAME, $this::AUTHORIZED_USER_LAST_NAME, $this::AUTHORIZED_USER_EMAIL, [User::ROLE_ADMIN], 'testPasswordHash', '');
        $userRepository->saveUser($authorizedUser);

        $generateApiKeyService = static::getContainer()->get(GenerateApiKeyService::class);
        $apiKey = $generateApiKeyService->handle($authorizedUser);
        $userRepository->saveUser($authorizedUser);

        $client->setServerParameter('HTTP_AUTHORIZATION', $apiKey);

        $crawler = $client->request('POST', '/api/reset-key');

        $response = $client->getResponse();
        $responseCode = $response->getStatusCode();
        $responseContent = json_decode($response->getContent(), true);

        $this->assertEquals($responseCode, Response::HTTP_OK);
        $this->assertEquals($responseContent['status'], 'success');

        $commitedUser = $userRepository->findOneUserBy(['id' => $authorizedUser->getId()]);
        $this->assertEquals($responseContent['data']['oldApiKey'], $apiKey);
        $this->assertEquals($responseContent['data']['newApiKey'], $commitedUser->getApiKey());

        $userRepository->removeUser($authorizedUser);
    }

    public function testResetUserPasswordWrongKey(): void
    {
        $client = static::createClient();
        $userRepository = static::getContainer()->get(UserRepositoryInterface::class);

        $authorizedUser = new User();
        User::registerUser($authorizedUser, $this::AUTHORIZED_USER_FIRST_NAME, $this::AUTHORIZED_USER_LAST_NAME, $this::AUTHORIZED_USER_EMAIL, [User::ROLE_ADMIN], 'testPasswordHash', '');
        $userRepository->saveUser($authorizedUser);

        $generateApiKeyService = static::getContainer()->get(GenerateApiKeyService::class);
        $apiKey = $generateApiKeyService->handle($authorizedUser);

        $passwordHasher = static::getContainer()->get(UserPasswordHasherInterface::class);
        $generatePasswordService = static::getContainer()->get(GeneratePasswordService::class);
        $plainPassword = $generatePasswordService->handle($authorizedUser);

        $userRepository->saveUser($authorizedUser);

        $client->setServerParameter('HTTP_AUTHORIZATION', 'incorrectApiKey');

        $crawler = $client->request('POST', '/api/reset-password');

        $response = $client->getResponse();
        $responseCode = $response->getStatusCode();
        $responseContent = json_decode($response->getContent(), true);

        $this->assertEquals($responseCode, Response::HTTP_NOT_FOUND);
        $this->assertEquals($responseContent['status'], 'fail');

        $commitedUser = $userRepository->findOneUserBy(['id' => $authorizedUser->getId()]);
        $this->assertEquals($passwordHasher->isPasswordValid($authorizedUser, $plainPassword), true);

        $userRepository->removeUser($authorizedUser);
    }

    public function testResetUserPassword(): void
    {
        $client = static::createClient();
        $userRepository = static::getContainer()->get(UserRepositoryInterface::class);

        $authorizedUser = new User();
        User::registerUser($authorizedUser, $this::AUTHORIZED_USER_FIRST_NAME, $this::AUTHORIZED_USER_LAST_NAME, $this::AUTHORIZED_USER_EMAIL, [User::ROLE_ADMIN], 'testPasswordHash', '');
        $userRepository->saveUser($authorizedUser);

        $generateApiKeyService = static::getContainer()->get(GenerateApiKeyService::class);
        $apiKey = $generateApiKeyService->handle($authorizedUser);

        $passwordHasher = static::getContainer()->get(UserPasswordHasherInterface::class);
        $generatePasswordService = static::getContainer()->get(GeneratePasswordService::class);
        $plainPassword = $generatePasswordService->handle($authorizedUser);

        $userRepository->saveUser($authorizedUser);

        $client->setServerParameter('HTTP_AUTHORIZATION', $apiKey);

        $crawler = $client->request('POST', '/api/reset-password');

        $response = $client->getResponse();
        $responseCode = $response->getStatusCode();
        $responseContent = json_decode($response->getContent(), true);

        $this->assertEquals($responseCode, Response::HTTP_OK);
        $this->assertEquals($responseContent['status'], 'success');

        $commitedUser = $userRepository->findOneUserBy(['id' => $authorizedUser->getId()]);
        $this->assertEquals($passwordHasher->isPasswordValid($authorizedUser, $plainPassword), false);

        $userRepository->removeUser($authorizedUser);
    }
}