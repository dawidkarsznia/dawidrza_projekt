<?php

namespace App\Tests\User\Application\Controller\API;

use App\User\Domain\Entity\User;
use App\User\Domain\Repository\UserRepositoryInterface;
use App\User\Application\Service\GeneratePasswordService;
use App\User\Application\Service\GenerateApiKeyService;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

class DeleteUserControllerTest extends WebTestCase
{
    public function testDeleteUserUnauthorized(): void
    {
        $client = static::createClient();

        $authorizedUser = new User();
        $testUser = new User();
        User::registerUser($authorizedUser, 'testFirst', 'testLast', 'testEmail@gmail.com', [], 'testPasswordHash', '');
        User::registerUser($testUser, 'testFirst', 'testLast', 'testEmail2@gmail.com', [], 'testPasswordHash2', 'testApiKey');

        $userRepository = static::getContainer()->get(UserRepositoryInterface::class);

        $generateApiKeyService = static::getContainer()->get(GenerateApiKeyService::class);
        $apiKey = $generateApiKeyService->handle($authorizedUser);

        $userRepository->saveUser($authorizedUser);
        $userRepository->saveUser($testUser);
        $testUserId = $testUser->getId();

        $client->setServerParameter('HTTP_AUTHORIZATION', $apiKey);

        $crawler = $client->request('DELETE', '/api/users/' . $testUserId);

        $responseCode = $client->getResponse()->getStatusCode();
        $response = json_decode($client->getResponse()->getContent(), true);

        $userRepository->removeUser($testUser);
        $userRepository->removeUser($authorizedUser);

        $this->assertEquals($responseCode, Response::HTTP_FORBIDDEN);
        $this->assertEquals($response['status'], 'fail');
    }

    public function testDeleteUserWrongId(): void
    {
        $client = static::createClient();

        $authorizedUser = new User();
        $testUser = new User();
        User::registerUser($authorizedUser, 'testFirst', 'testLast', 'testEmail@gmail.com', [], 'testPasswordHash', '');
        User::registerUser($testUser, 'testFirst', 'testLast', 'testEmail2@gmail.com', [], 'testPasswordHash2', 'testApiKey');

        $userRepository = static::getContainer()->get(UserRepositoryInterface::class);

        $generateApiKeyService = static::getContainer()->get(GenerateApiKeyService::class);
        $apiKey = $generateApiKeyService->handle($authorizedUser);

        $userRepository->saveUser($authorizedUser);
        $userRepository->saveUser($testUser);
        $testUserId = $testUser->getId();

        $client->setServerParameter('HTTP_AUTHORIZATION', 'incorrectApiKey');

        $crawler = $client->request('DELETE', '/api/users/' . ($testUserId + 1));

        $responseCode = $client->getResponse()->getStatusCode();
        $response = json_decode($client->getResponse()->getContent(), true);

        $userRepository->removeUser($testUser);
        $userRepository->removeUser($authorizedUser);

        $this->assertEquals($responseCode, Response::HTTP_NOT_FOUND);
        $this->assertEquals($response['status'], 'fail');
    }

    public function testDeleteUserWrongKey(): void
    {
        $client = static::createClient();

        $authorizedUser = new User();
        $testUser = new User();
        User::registerUser($authorizedUser, 'testFirst', 'testLast', 'testEmail@gmail.com', [], 'testPasswordHash', '');
        User::registerUser($testUser, 'testFirst', 'testLast', 'testEmail2@gmail.com', [], 'testPasswordHash2', 'testApiKey');

        $userRepository = static::getContainer()->get(UserRepositoryInterface::class);

        $generateApiKeyService = static::getContainer()->get(GenerateApiKeyService::class);
        $apiKey = $generateApiKeyService->handle($authorizedUser);

        $userRepository->saveUser($authorizedUser);
        $userRepository->saveUser($testUser);
        $testUserId = $testUser->getId();

        $client->setServerParameter('HTTP_AUTHORIZATION', 'incorrectApiKey');

        $crawler = $client->request('DELETE', '/api/users/' . $testUserId);

        $responseCode = $client->getResponse()->getStatusCode();
        $response = json_decode($client->getResponse()->getContent(), true);

        $userRepository->removeUser($testUser);
        $userRepository->removeUser($authorizedUser);

        $this->assertEquals($responseCode, Response::HTTP_NOT_FOUND);
        $this->assertEquals($response['status'], 'fail');
    }

    public function testDeleteUser(): void
    {
        $client = static::createClient();

        $authorizedUser = new User();
        $testUser = new User();
        User::registerUser($authorizedUser, 'testFirst', 'testLast', 'testEmail@gmail.com', [User::ROLE_ADMIN], 'testPasswordHash', '');
        User::registerUser($testUser, 'testFirst', 'testLast', 'testEmail2@gmail.com', [], 'testPasswordHash2', 'testApiKey');

        $userRepository = static::getContainer()->get(UserRepositoryInterface::class);

        $generateApiKeyService = static::getContainer()->get(GenerateApiKeyService::class);
        $apiKey = $generateApiKeyService->handle($authorizedUser);

        $userRepository->saveUser($authorizedUser);
        $userRepository->saveUser($testUser);
        $testUserId = $testUser->getId();

        $client->setServerParameter('HTTP_AUTHORIZATION', $apiKey);

        $crawler = $client->request('DELETE', '/api/users/' . $testUserId);

        $responseCode = $client->getResponse()->getStatusCode();
        $response = json_decode($client->getResponse()->getContent(), true);

        $userRepository->removeUser($authorizedUser);

        $this->assertEquals($responseCode, Response::HTTP_OK);
        $this->assertEquals($response['status'], 'success');
    }
}