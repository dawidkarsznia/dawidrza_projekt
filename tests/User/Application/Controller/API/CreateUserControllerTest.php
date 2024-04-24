<?php

namespace App\Tests\User\Application\Controller\API;

use App\User\Domain\Entity\User;
use App\User\Domain\Repository\UserRepositoryInterface;
use App\User\Application\Service\GenerateApiKeyService;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

class CreateUserControllerTest extends WebTestCase
{
    function testCreateUserUnauthorized(): void
    {
        $client = static::createClient();

        $authorizedUser = new User();
        User::registerUser($authorizedUser, 'testFirst', 'testLast', 'testEmail@gmail.com', [User::ROLE_ADMIN], 'testPasswordHash', '');

        $userRepository = static::getContainer()->get(UserRepositoryInterface::class);

        $generateApiKeyService = static::getContainer()->get(GenerateApiKeyService::class);
        $apiKey = $generateApiKeyService->handle($authorizedUser);

        $userRepository->saveUser($authorizedUser);

        $client->setServerParameter('HTTP_AUTHORIZATION', 'incorrectApiKey');

        $requestBody = json_encode([
            'firstName' => 'Jan',
            'lastName' => 'Kowalski',
            'email' => 'jankowalski@gmail.com'
        ]);

        $crawler = $client->request('POST', '/api/users', [], [], [], $requestBody);

        $responseCode = $client->getResponse()->getStatusCode();
        $response = json_decode($client->getResponse()->getContent(), true);

        $userRepository->removeUser($authorizedUser);

        $this->assertEquals($responseCode, Response::HTTP_NOT_FOUND);
        $this->assertEquals($response['status'], 'fail');
    }

    function testCreateUserWrongFirstName(): void
    {
        $client = static::createClient();

        $authorizedUser = new User();
        User::registerUser($authorizedUser, 'testFirst', 'testLast', 'testEmail@gmail.com', [User::ROLE_ADMIN], 'testPasswordHash', '');

        $userRepository = static::getContainer()->get(UserRepositoryInterface::class);

        $generateApiKeyService = static::getContainer()->get(GenerateApiKeyService::class);
        $apiKey = $generateApiKeyService->handle($authorizedUser);

        $userRepository->saveUser($authorizedUser);

        $client->setServerParameter('HTTP_AUTHORIZATION', $apiKey);

        $requestBody = json_encode([
            'firstName' => 'Jan123',
            'lastName' => 'Kowalski',
            'email' => 'jankowalski@gmail.com'
        ]);

        $crawler = $client->request('POST', '/api/users', [], [], [], $requestBody);

        $responseCode = $client->getResponse()->getStatusCode();
        $response = json_decode($client->getResponse()->getContent(), true);

        $userRepository->removeUser($authorizedUser);

        $this->assertEquals($responseCode, Response::HTTP_BAD_REQUEST);
        $this->assertEquals($response['status'], 'fail');
    }

    function testCreateUserWrongLastName(): void
    {
        $client = static::createClient();

        $authorizedUser = new User();
        User::registerUser($authorizedUser, 'testFirst', 'testLast', 'testEmail@gmail.com', [User::ROLE_ADMIN], 'testPasswordHash', '');

        $userRepository = static::getContainer()->get(UserRepositoryInterface::class);

        $generateApiKeyService = static::getContainer()->get(GenerateApiKeyService::class);
        $apiKey = $generateApiKeyService->handle($authorizedUser);

        $userRepository->saveUser($authorizedUser);

        $client->setServerParameter('HTTP_AUTHORIZATION', $apiKey);

        $requestBody = json_encode([
            'firstName' => 'Jan',
            'lastName' => 'Kowalski123',
            'email' => 'jankowalski@gmail.com'
        ]);

        $crawler = $client->request('POST', '/api/users', [], [], [], $requestBody);

        $responseCode = $client->getResponse()->getStatusCode();
        $response = json_decode($client->getResponse()->getContent(), true);

        $userRepository->removeUser($authorizedUser);

        $this->assertEquals($responseCode, Response::HTTP_BAD_REQUEST);
        $this->assertEquals($response['status'], 'fail');
    }

    function testCreateUserWrongEmail(): void
    {
        $client = static::createClient();

        $authorizedUser = new User();
        User::registerUser($authorizedUser, 'testFirst', 'testLast', 'testEmail@gmail.com', [User::ROLE_ADMIN], 'testPasswordHash', '');

        $userRepository = static::getContainer()->get(UserRepositoryInterface::class);

        $generateApiKeyService = static::getContainer()->get(GenerateApiKeyService::class);
        $apiKey = $generateApiKeyService->handle($authorizedUser);

        $userRepository->saveUser($authorizedUser);

        $client->setServerParameter('HTTP_AUTHORIZATION', $apiKey);

        $requestBody = json_encode([
            'firstName' => 'Jan',
            'lastName' => 'Kowalski',
            'email' => 'jankowalski'
        ]);

        $crawler = $client->request('POST', '/api/users', [], [], [], $requestBody);

        $responseCode = $client->getResponse()->getStatusCode();
        $response = json_decode($client->getResponse()->getContent(), true);

        $userRepository->removeUser($authorizedUser);

        $this->assertEquals($responseCode, Response::HTTP_BAD_REQUEST);
        $this->assertEquals($response['status'], 'fail');
    }
    
    function testCreateUser(): void
    {
        $client = static::createClient();

        $authorizedUser = new User();
        User::registerUser($authorizedUser, 'testFirst', 'testLast', 'testEmail@gmail.com', [User::ROLE_ADMIN], 'testPasswordHash', '');

        $userRepository = static::getContainer()->get(UserRepositoryInterface::class);

        $generateApiKeyService = static::getContainer()->get(GenerateApiKeyService::class);
        $apiKey = $generateApiKeyService->handle($authorizedUser);

        $userRepository->saveUser($authorizedUser);

        $client->setServerParameter('HTTP_AUTHORIZATION', $apiKey);

        $requestBody = json_encode([
            'firstName' => 'Jan',
            'lastName' => 'Kowalski',
            'email' => 'jankowalski@gmail.com'
        ]);

        $crawler = $client->request('POST', '/api/users', [], [], [], $requestBody);

        $responseCode = $client->getResponse()->getStatusCode();
        $response = json_decode($client->getResponse()->getContent(), true);

        $createdUser = $userRepository->findOneUserBy(['email' => 'jankowalski@gmail.com']);
        $userRepository->removeUser($authorizedUser);
        $userRepository->removeUser($createdUser);

        $this->assertEquals($responseCode, Response::HTTP_OK);
        $this->assertEquals($response['status'], 'success');
    }
}