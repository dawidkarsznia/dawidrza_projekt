<?php

namespace App\Tests\User\Application\Controller\API;

use App\User\Domain\Entity\User;
use App\User\Domain\Repository\UserRepositoryInterface;
use App\User\Application\Service\GenerateApiKeyService;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;

class CreateUserControllerTest extends WebTestCase
{
    private const AUTHORIZED_USER_FIRST_NAME = 'Jan';
    private const AUTHORIZED_USER_LAST_NAME = 'Kowalski';
    private const AUTHORIZED_USER_EMAIL = 'jankowalski@gmail.com';

    private function createTest(string $testBody = '', bool $isAuthorized = true, bool $incorrectApiKey = false): Response
    {
        $client = static::createClient();

        // Create an authorized user to test our API with.
        $authorizedUser = new User();
        User::registerUser($authorizedUser, $this::AUTHORIZED_USER_FIRST_NAME, $this::AUTHORIZED_USER_LAST_NAME, $this::AUTHORIZED_USER_EMAIL, [], 'testPasswordHash', '');
        if ($isAuthorized)
        {
            $authorizedUser->setRoles([User::ROLE_ADMIN]);
        }

        $userRepository = static::getContainer()->get(UserRepositoryInterface::class);

        // Set the authentication field to the API key or the incorrect value.
        if (true === $incorrectApiKey)
        {
            $client->setServerParameter('HTTP_AUTHORIZATION', 'incorrectApiKey');
        }
        else
        {
            $generateApiKeyService = static::getContainer()->get(GenerateApiKeyService::class);
            $apiKey = $generateApiKeyService->handle($authorizedUser);

            $client->setServerParameter('HTTP_AUTHORIZATION', $apiKey);
        }

        $userRepository->saveUser($authorizedUser);

        $crawler = $client->request('POST', '/api/users', [], [], [], $testBody);

        $userRepository->removeUser($authorizedUser);

        return $client->getResponse();
    }

    public function testCreateUserUnauthorized(): void
    {
        $requestBody = json_encode([
            'firstName' => 'Anna',
            'lastName' => 'Nowak',
            'email' => 'anna_nowak@gmail.com'
        ]);

        $response = $this->createTest($requestBody, false);
        $responseCode = $response->getStatusCode();
        $responseContent = json_decode($response->getContent(), true);

        $this->assertEquals($responseCode, Response::HTTP_FORBIDDEN);
        $this->assertEquals($responseContent['status'], 'fail');

        $userRepository = static::getContainer()->get(UserRepositoryInterface::class);
        $createdUser = $userRepository->findOneUserBy(['email' => 'anna_nowak@gmail.com']);
        $this->assertEquals($createdUser, null);
    }

    public function testCreateUserWrongKey(): void
    {
        $requestBody = json_encode([
            'firstName' => 'Anna',
            'lastName' => 'Nowak',
            'email' => 'anna_nowak@gmail.com'
        ]);

        $response = $this->createTest($requestBody, true, true);
        $responseCode = $response->getStatusCode();
        $responseContent = json_decode($response->getContent(), true);

        $this->assertEquals($responseCode, Response::HTTP_NOT_FOUND);
        $this->assertEquals($responseContent['status'], 'fail');

        $userRepository = static::getContainer()->get(UserRepositoryInterface::class);
        $createdUser = $userRepository->findOneUserBy(['email' => 'anna_nowak@gmail.com']);
        $this->assertEquals($createdUser, null);
    }

    public function testCreateUserEmptyFirstName(): void
    {
        $requestBody = json_encode([
            'lastName' => 'Nowak',
            'email' => 'anna_nowak@gmail.com'
        ]);

        $response = $this->createTest($requestBody);
        $responseCode = $response->getStatusCode();
        $responseContent = json_decode($response->getContent(), true);

        $this->assertEquals($responseCode, Response::HTTP_BAD_REQUEST);
        $this->assertEquals($responseContent['status'], 'fail');

        $userRepository = static::getContainer()->get(UserRepositoryInterface::class);
        $createdUser = $userRepository->findOneUserBy(['email' => 'anna_nowak@gmail.com']);
        $this->assertEquals($createdUser, null);
    }

    public function testCreateUserWrongFirstName(): void
    {
        $requestBody = json_encode([
            'firstName' => 'Anna123',
            'lastName' => 'Nowak',
            'email' => 'anna_nowak@gmail.com'
        ]);

        $response = $this->createTest($requestBody);
        $responseCode = $response->getStatusCode();
        $responseContent = json_decode($response->getContent(), true);

        $this->assertEquals($responseCode, Response::HTTP_BAD_REQUEST);
        $this->assertEquals($responseContent['status'], 'fail');

        $userRepository = static::getContainer()->get(UserRepositoryInterface::class);
        $createdUser = $userRepository->findOneUserBy(['email' => 'anna_nowak@gmail.com']);
        $this->assertEquals($createdUser, null);
    }

    public function testCreateUserEmptyLastName(): void
    {
        $requestBody = json_encode([
            'firstName' => 'Anna',
            'email' => 'anna_nowak@gmail.com'
        ]);

        $response = $this->createTest($requestBody);
        $responseCode = $response->getStatusCode();
        $responseContent = json_decode($response->getContent(), true);

        $this->assertEquals($responseCode, Response::HTTP_BAD_REQUEST);
        $this->assertEquals($responseContent['status'], 'fail');

        $userRepository = static::getContainer()->get(UserRepositoryInterface::class);
        $createdUser = $userRepository->findOneUserBy(['email' => 'anna_nowak@gmail.com']);
        $this->assertEquals($createdUser, null);
    }

    public function testCreateUserWrongLastName(): void
    {
        $requestBody = json_encode([
            'firstName' => 'Anna',
            'lastName' => 'Nowak123',
            'email' => 'anna_nowak@gmail.com'
        ]);

        $response = $this->createTest($requestBody);
        $responseCode = $response->getStatusCode();
        $responseContent = json_decode($response->getContent(), true);

        $this->assertEquals($responseCode, Response::HTTP_BAD_REQUEST);
        $this->assertEquals($responseContent['status'], 'fail');
        
        $userRepository = static::getContainer()->get(UserRepositoryInterface::class);
        $createdUser = $userRepository->findOneUserBy(['email' => 'anna_nowak@gmail.com']);
        $this->assertEquals($createdUser, null);
    }

    public function testCreateUserEmptyEmail(): void
    {
        $requestBody = json_encode([
            'firstName' => 'Anna',
            'lastName' => 'Nowak',
        ]);

        $response = $this->createTest($requestBody);
        $responseCode = $response->getStatusCode();
        $responseContent = json_decode($response->getContent(), true);

        $this->assertEquals($responseCode, Response::HTTP_BAD_REQUEST);
        $this->assertEquals($responseContent['status'], 'fail');

        $userRepository = static::getContainer()->get(UserRepositoryInterface::class);
        $createdUser = $userRepository->findOneUserBy(['email' => 'anna_nowak@gmail.com']);
        $this->assertEquals($createdUser, null);
    }
    
    public function testCreateUserWrongEmail(): void
    {
        $requestBody = json_encode([
            'firstName' => 'Anna',
            'lastName' => 'Nowak',
            'email' => 'jankowalski'
        ]);

        $response = $this->createTest($requestBody);
        $responseCode = $response->getStatusCode();
        $responseContent = json_decode($response->getContent(), true);

        $this->assertEquals($responseCode, Response::HTTP_BAD_REQUEST);
        $this->assertEquals($responseContent['status'], 'fail');

        $userRepository = static::getContainer()->get(UserRepositoryInterface::class);
        $createdUser = $userRepository->findOneUserBy(['email' => 'anna_nowak@gmail.com']);
        $this->assertEquals($createdUser, null);
    }

    public function testCreateUserDuplicateEmail(): void
    {
        $requestBody = json_encode([
            'firstName' => 'Anna',
            'lastName' => 'Nowak',
            'email' => $this::AUTHORIZED_USER_EMAIL
        ]);

        $response = $this->createTest($requestBody);
        $responseCode = $response->getStatusCode();
        $responseContent = json_decode($response->getContent(), true);

        $this->assertEquals($responseCode, Response::HTTP_BAD_REQUEST);
        $this->assertEquals($responseContent['status'], 'fail');

        $userRepository = static::getContainer()->get(UserRepositoryInterface::class);
        $createdUser = $userRepository->findOneUserBy(['email' => 'anna_nowak@gmail.com']);
        $this->assertEquals($createdUser, null);
    }
    
    public function testCreateUser(): void
    {
        $requestBody = json_encode([
            'firstName' => 'Anna',
            'lastName' => 'Nowak',
            'email' => 'anna_nowak@gmail.com'
        ]);

        $response = $this->createTest($requestBody);
        $responseCode = $response->getStatusCode();
        $responseContent = json_decode($response->getContent(), true);

        $this->assertEquals($responseCode, Response::HTTP_OK);
        $this->assertEquals($responseContent['status'], 'success');

        $userRepository = static::getContainer()->get(UserRepositoryInterface::class);
        $createdUser = $userRepository->findOneUserBy(['email' => 'anna_nowak@gmail.com']);
        $this->assertNotEquals($createdUser, null);

        $userRepository->removeUser($createdUser);
    }
}