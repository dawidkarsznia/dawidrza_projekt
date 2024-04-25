<?php

namespace App\Tests\User\Application\Controller\API;

use App\User\Domain\Entity\User;
use App\User\Domain\Repository\UserRepositoryInterface;
use App\User\Application\Service\GeneratePasswordService;
use App\User\Application\Service\GenerateApiKeyService;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;

class UpdateUserControllerTest extends WebTestCase
{
    private const AUTHORIZED_USER_FIRST_NAME = 'Jan';
    private const AUTHORIZED_USER_LAST_NAME = 'Kowalski';
    private const AUTHORIZED_USER_EMAIL = 'jankowalski@gmail.com';

    private function createTest(KernelBrowser $client, string $path, string $testBody = '', bool $isAuthorized = true, bool $incorrectApiKey = false): Response
    {
        $authorizedUser = new User();
        User::registerUser($authorizedUser, $this::AUTHORIZED_USER_FIRST_NAME, $this::AUTHORIZED_USER_LAST_NAME, $this::AUTHORIZED_USER_EMAIL, [], 'testPasswordHash', '');
        if ($isAuthorized)
        {
            $authorizedUser->setRoles([User::ROLE_ADMIN]);
        }

        $userRepository = static::getContainer()->get(UserRepositoryInterface::class);

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

        $crawler = $client->request('PATCH', $path, [], [], [], $testBody);

        $userRepository->removeUser($authorizedUser);

        return $client->getResponse();
    }

    public function testUpdateUserUnauthorized(): void
    {
        $client = static::createClient();
        $userRepository = static::getContainer()->get(UserRepositoryInterface::class);

        $testUser = new User();
        User::registerUser($testUser, 'Anna', 'Nowak', 'anna_nowak@gmail.com', [], 'testPasswordHash', '');
        $userRepository->saveUser($testUser);

        $requestBody = json_encode([
            'firstName' => 'Jakub',
            'lastName' => 'Detlaff',
            'email' => 'jakub_detlaff@gmail.com'
        ]);

        $response = $this->createTest($client, '/api/users/' . $testUser->getId(), $requestBody, false);
        $responseCode = $response->getStatusCode();
        $responseContent = json_decode($response->getContent(), true);

        $this->assertEquals($responseCode, Response::HTTP_FORBIDDEN);
        $this->assertEquals($responseContent['status'], 'fail');

        $commitedUser = $userRepository->findOneUserBy(['id' => $testUser->getId()]);
        $this->assertEquals($commitedUser->getFirstName(), 'Anna');
        $this->assertEquals($commitedUser->getLastName(), 'Nowak');
        $this->assertEquals($commitedUser->getEmail(), 'anna_nowak@gmail.com');

        $userRepository->removeUser($testUser);
    } 

    public function testUpdateUserWrongId(): void
    {
        $client = static::createClient();
        $userRepository = static::getContainer()->get(UserRepositoryInterface::class);

        $testUser = new User();
        User::registerUser($testUser, 'Anna', 'Nowak', 'anna_nowak@gmail.com', [], 'testPasswordHash', '');
        $userRepository->saveUser($testUser);

        $requestBody = json_encode([
            'firstName' => 'Jakub',
            'lastName' => 'Detlaff',
            'email' => 'jakub_detlaff@gmail.com'
        ]);

        $response = $this->createTest($client, '/api/users/' . ($testUser->getId() + 2), $requestBody);
        $responseCode = $response->getStatusCode();
        $responseContent = json_decode($response->getContent(), true);

        $this->assertEquals($responseCode, Response::HTTP_NOT_FOUND);
        $this->assertEquals($responseContent['status'], 'fail');

        $commitedUser = $userRepository->findOneUserBy(['id' => $testUser->getId()]);
        $this->assertEquals($commitedUser->getFirstName(), 'Anna');
        $this->assertEquals($commitedUser->getLastName(), 'Nowak');
        $this->assertEquals($commitedUser->getEmail(), 'anna_nowak@gmail.com');

        $userRepository->removeUser($testUser);
    }

    public function testUpdateUserWrongKey(): void
    {
        $client = static::createClient();
        $userRepository = static::getContainer()->get(UserRepositoryInterface::class);

        $testUser = new User();
        User::registerUser($testUser, 'Anna', 'Nowak', 'anna_nowak@gmail.com', [], 'testPasswordHash', '');
        $userRepository->saveUser($testUser);

        $requestBody = json_encode([
            'firstName' => 'Jakub',
            'lastName' => 'Detlaff',
            'email' => 'jakub_detlaff@gmail.com'
        ]);

        $response = $this->createTest($client, '/api/users/' . $testUser->getId(), $requestBody, true, true);
        $responseCode = $response->getStatusCode();
        $responseContent = json_decode($response->getContent(), true);

        $this->assertEquals($responseCode, Response::HTTP_NOT_FOUND);
        $this->assertEquals($responseContent['status'], 'fail');

        $commitedUser = $userRepository->findOneUserBy(['id' => $testUser->getId()]);
        $this->assertEquals($commitedUser->getFirstName(), 'Anna');
        $this->assertEquals($commitedUser->getLastName(), 'Nowak');
        $this->assertEquals($commitedUser->getEmail(), 'anna_nowak@gmail.com');

        $userRepository->removeUser($testUser);
    }

    public function testUpdateUserWrongFirstName(): void
    {
        $client = static::createClient();
        $userRepository = static::getContainer()->get(UserRepositoryInterface::class);

        $testUser = new User();
        User::registerUser($testUser, 'Anna', 'Nowak', 'anna_nowak@gmail.com', [], 'testPasswordHash', '');
        $userRepository->saveUser($testUser);

        $requestBody = json_encode([
            'firstName' => 'Jakub123',
            'lastName' => 'Detlaff',
            'email' => 'jakub_detlaff@gmail.com'
        ]);

        $response = $this->createTest($client, '/api/users/' . $testUser->getId(), $requestBody);
        $responseCode = $response->getStatusCode();
        $responseContent = json_decode($response->getContent(), true);

        $this->assertEquals($responseCode, Response::HTTP_BAD_REQUEST);
        $this->assertEquals($responseContent['status'], 'fail');

        $commitedUser = $userRepository->findOneUserBy(['id' => $testUser->getId()]);
        $this->assertEquals($commitedUser->getFirstName(), 'Anna');
        $this->assertEquals($commitedUser->getLastName(), 'Detlaff');
        $this->assertEquals($commitedUser->getEmail(), 'jakub_detlaff@gmail.com');

        $userRepository->removeUser($testUser);
    }

    public function testUpdateUserEmptyFirstName(): void
    {
        $client = static::createClient();
        $userRepository = static::getContainer()->get(UserRepositoryInterface::class);

        $testUser = new User();
        User::registerUser($testUser, 'Anna', 'Nowak', 'anna_nowak@gmail.com', [], 'testPasswordHash', '');
        $userRepository->saveUser($testUser);

        $requestBody = json_encode([
            'lastName' => 'Detlaff',
            'email' => 'jakub_detlaff@gmail.com'
        ]);

        $response = $this->createTest($client, '/api/users/' . $testUser->getId(), $requestBody);
        $responseCode = $response->getStatusCode();
        $responseContent = json_decode($response->getContent(), true);

        $this->assertEquals($responseCode, Response::HTTP_OK);
        $this->assertEquals($responseContent['status'], 'success');

        $commitedUser = $userRepository->findOneUserBy(['id' => $testUser->getId()]);
        $this->assertEquals($commitedUser->getFirstName(), 'Anna');
        $this->assertEquals($commitedUser->getLastName(), 'Detlaff');
        $this->assertEquals($commitedUser->getEmail(), 'jakub_detlaff@gmail.com');

        $userRepository->removeUser($testUser);
    }

    public function testUpdateUserWrongLastName(): void
    {
        $client = static::createClient();
        $userRepository = static::getContainer()->get(UserRepositoryInterface::class);

        $testUser = new User();
        User::registerUser($testUser, 'Anna', 'Nowak', 'anna_nowak@gmail.com', [], 'testPasswordHash', '');
        $userRepository->saveUser($testUser);

        $requestBody = json_encode([
            'firstName' => 'Jakub',
            'lastName' => 'Detlaff123',
            'email' => 'jakub_detlaff@gmail.com'
        ]);

        $response = $this->createTest($client, '/api/users/' . $testUser->getId(), $requestBody);
        $responseCode = $response->getStatusCode();
        $responseContent = json_decode($response->getContent(), true);

        $this->assertEquals($responseCode, Response::HTTP_BAD_REQUEST);
        $this->assertEquals($responseContent['status'], 'fail');

        $commitedUser = $userRepository->findOneUserBy(['id' => $testUser->getId()]);
        $this->assertEquals($commitedUser->getFirstName(), 'Jakub');
        $this->assertEquals($commitedUser->getLastName(), 'Nowak');
        $this->assertEquals($commitedUser->getEmail(), 'jakub_detlaff@gmail.com');

        $userRepository->removeUser($testUser);
    }

    public function testUpdateUserEmptyLastName(): void
    {
        $client = static::createClient();
        $userRepository = static::getContainer()->get(UserRepositoryInterface::class);

        $testUser = new User();
        User::registerUser($testUser, 'Anna', 'Nowak', 'anna_nowak@gmail.com', [], 'testPasswordHash', '');
        $userRepository->saveUser($testUser);

        $requestBody = json_encode([
            'firstName' => 'Jakub',
            'email' => 'jakub_detlaff@gmail.com'
        ]);

        $response = $this->createTest($client, '/api/users/' . $testUser->getId(), $requestBody);
        $responseCode = $response->getStatusCode();
        $responseContent = json_decode($response->getContent(), true);

        $commitedUser = $userRepository->findOneUserBy(['id' => $testUser->getId()]);
        $this->assertEquals($commitedUser->getFirstName(), 'Jakub');
        $this->assertEquals($commitedUser->getLastName(), 'Nowak');
        $this->assertEquals($commitedUser->getEmail(), 'jakub_detlaff@gmail.com');

        $userRepository->removeUser($testUser);
    }

    public function testUpdateUserWrongEmail(): void
    {
        $client = static::createClient();
        $userRepository = static::getContainer()->get(UserRepositoryInterface::class);

        $testUser = new User();
        User::registerUser($testUser, 'Anna', 'Nowak', 'anna_nowak@gmail.com', [], 'testPasswordHash', '');
        $userRepository->saveUser($testUser);

        $requestBody = json_encode([
            'firstName' => 'Jakub',
            'lastName' => 'Detlaff',
            'email' => 'jakub_detlaff'
        ]);

        $response = $this->createTest($client, '/api/users/' . $testUser->getId(), $requestBody);
        $responseCode = $response->getStatusCode();
        $responseContent = json_decode($response->getContent(), true);

        $this->assertEquals($responseCode, Response::HTTP_BAD_REQUEST);
        $this->assertEquals($responseContent['status'], 'fail');

        $commitedUser = $userRepository->findOneUserBy(['id' => $testUser->getId()]);
        $this->assertEquals($commitedUser->getFirstName(), 'Jakub');
        $this->assertEquals($commitedUser->getLastName(), 'Detlaff');
        $this->assertEquals($commitedUser->getEmail(), 'anna_nowak@gmail.com');

        $userRepository->removeUser($testUser);
    }

    public function testUpdateUserEmptyEmail(): void
    {
        $client = static::createClient();
        $userRepository = static::getContainer()->get(UserRepositoryInterface::class);

        $testUser = new User();
        User::registerUser($testUser, 'Anna', 'Nowak', 'anna_nowak@gmail.com', [], 'testPasswordHash', '');
        $userRepository->saveUser($testUser);

        $requestBody = json_encode([
            'firstName' => 'Jakub',
            'lastName' => 'Detlaff'
        ]);

        $response = $this->createTest($client, '/api/users/' . $testUser->getId(), $requestBody);
        $responseCode = $response->getStatusCode();
        $responseContent = json_decode($response->getContent(), true);

        $this->assertEquals($responseCode, Response::HTTP_OK);
        $this->assertEquals($responseContent['status'], 'success');
        
        $commitedUser = $userRepository->findOneUserBy(['id' => $testUser->getId()]);
        $this->assertEquals($commitedUser->getFirstName(), 'Jakub');
        $this->assertEquals($commitedUser->getLastName(), 'Detlaff');
        $this->assertEquals($commitedUser->getEmail(), 'anna_nowak@gmail.com');

        $userRepository->removeUser($testUser);
    }

    public function testUpdateUserDuplicateEmail(): void
    {
        $client = static::createClient();
        $userRepository = static::getContainer()->get(UserRepositoryInterface::class);

        $testUser = new User();
        User::registerUser($testUser, 'Anna', 'Nowak', 'anna_nowak@gmail.com', [], 'testPasswordHash', '');
        $userRepository->saveUser($testUser);

        $requestBody = json_encode([
            'firstName' => 'Jakub',
            'lastName' => 'Detlaff',
            'email' => 'anna_nowak@gmail.com'
        ]);

        $response = $this->createTest($client, '/api/users/' . $testUser->getId(), $requestBody);
        $responseCode = $response->getStatusCode();
        $responseContent = json_decode($response->getContent(), true);

        $this->assertEquals($responseCode, Response::HTTP_BAD_REQUEST);
        $this->assertEquals($responseContent['status'], 'fail');

        $commitedUser = $userRepository->findOneUserBy(['id' => $testUser->getId()]);
        $this->assertEquals($commitedUser->getFirstName(), 'Jakub');
        $this->assertEquals($commitedUser->getLastName(), 'Detlaff');
        $this->assertEquals($commitedUser->getEmail(), 'anna_nowak@gmail.com');

        $userRepository->removeUser($testUser);
    }

    public function testUpdateUser(): void
    {
        $client = static::createClient();
        $userRepository = static::getContainer()->get(UserRepositoryInterface::class);

        $testUser = new User();
        User::registerUser($testUser, 'Anna', 'Nowak', 'anna_nowak@gmail.com', [], 'testPasswordHash', '');
        $userRepository->saveUser($testUser);

        $requestBody = json_encode([
            'firstName' => 'Jakub',
            'lastName' => 'Detlaff',
            'email' => 'jakub_detlaff@gmail.com'
        ]);

        $response = $this->createTest($client, '/api/users/' . $testUser->getId(), $requestBody);
        $responseCode = $response->getStatusCode();
        $responseContent = json_decode($response->getContent(), true);

        $this->assertEquals($responseCode, Response::HTTP_OK);
        $this->assertEquals($responseContent['status'], 'success');

        $commitedUser = $userRepository->findOneUserBy(['id' => $testUser->getId()]);
        $this->assertEquals($commitedUser->getFirstName(), 'Jakub');
        $this->assertEquals($commitedUser->getLastName(), 'Detlaff');
        $this->assertEquals($commitedUser->getEmail(), 'jakub_detlaff@gmail.com');

        $userRepository->removeUser($testUser);
    }
}