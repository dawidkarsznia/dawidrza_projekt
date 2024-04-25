<?php

namespace App\Tests\User\Application\Controller\API;

use App\User\Domain\Entity\User;
use App\User\Domain\Repository\UserRepositoryInterface;
use App\User\Application\Service\GeneratePasswordService;
use App\User\Application\Service\GenerateApiKeyService;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;

class GetUserControllerTest extends WebTestCase
{
    private const AUTHORIZED_USER_FIRST_NAME = 'Jan';
    private const AUTHORIZED_USER_LAST_NAME = 'Kowalski';
    private const AUTHORIZED_USER_EMAIL = 'jankowalski@gmail.com';

    private function createTest(KernelBrowser $client, string $path, bool $isAuthorized = true, bool $incorrectApiKey = false): Response
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

        $crawler = $client->request('GET', $path);

        $userRepository->removeUser($authorizedUser);

        return $client->getResponse();
    }
    
    public function testGetUserProfileWrongKey(): void
    {
        $client = static::createClient();

        $response = $this->createTest($client, '/api/profile', false, true);
        $responseCode = $response->getStatusCode();
        $responseContent = json_decode($response->getContent(), true);

        $this->assertEquals($responseCode, Response::HTTP_NOT_FOUND);
        $this->assertEquals($responseContent['status'], 'fail');
    }

    public function testGetUserProfile(): void
    {
        $client = static::createClient();

        $response = $this->createTest($client, '/api/profile', false);
        $responseCode = $response->getStatusCode();
        $responseContent = json_decode($response->getContent(), true);

        $this->assertEquals($responseCode, Response::HTTP_OK);
        $this->assertEquals($responseContent['status'], 'success');
        $this->assertEquals($responseContent['data']['firstName'], $this::AUTHORIZED_USER_FIRST_NAME);
        $this->assertEquals($responseContent['data']['lastName'], $this::AUTHORIZED_USER_LAST_NAME);
        $this->assertEquals($responseContent['data']['email'], $this::AUTHORIZED_USER_EMAIL);
    }

    public function testGetUserUnauthorized(): void
    {
        $client = static::createClient();
        $userRepository = static::getContainer()->get(UserRepositoryInterface::class);

        $testUser = new User();
        User::registerUser($testUser, 'Anna', 'Nowak', 'anna_nowak@gmail.com', [], 'testPasswordHash', '');
        $userRepository->saveUser($testUser);

        $response = $this->createTest($client, '/api/users/' . $testUser->getId(), false);
        $responseCode = $response->getStatusCode();
        $responseContent = json_decode($response->getContent(), true);

        $userRepository->removeUser($testUser);

        $this->assertEquals($responseCode, Response::HTTP_FORBIDDEN);
        $this->assertEquals($responseContent['status'], 'fail');
    }
    
    public function testGetUserWrongId(): void
    {
        $client = static::createClient();
        $userRepository = static::getContainer()->get(UserRepositoryInterface::class);

        $testUser = new User();
        User::registerUser($testUser, 'Anna', 'Nowak', 'anna_nowak@gmail.com', [], 'testPasswordHash', '');
        $userRepository->saveUser($testUser);

        $response = $this->createTest($client, '/api/users/' . ($testUser->getId() + 2), true);
        $responseCode = $response->getStatusCode();
        $responseContent = json_decode($response->getContent(), true);

        $userRepository->removeUser($testUser);

        $this->assertEquals($responseCode, Response::HTTP_NOT_FOUND);
        $this->assertEquals($responseContent['status'], 'fail');
    }

    public function testGetUserWrongKey(): void
    {
        $client = static::createClient();
        $userRepository = static::getContainer()->get(UserRepositoryInterface::class);

        $testUser = new User();
        User::registerUser($testUser, 'Anna', 'Nowak', 'anna_nowak@gmail.com', [], 'testPasswordHash', '');
        $userRepository->saveUser($testUser);

        $response = $this->createTest($client, '/api/users/' . $testUser->getId(), true, true);
        $responseCode = $response->getStatusCode();
        $responseContent = json_decode($response->getContent(), true);

        $userRepository->removeUser($testUser);

        $this->assertEquals($responseCode, Response::HTTP_NOT_FOUND);
        $this->assertEquals($responseContent['status'], 'fail');
    }

    public function testGetUser(): void
    {
        $client = static::createClient();
        $userRepository = static::getContainer()->get(UserRepositoryInterface::class);

        $testUser = new User();
        User::registerUser($testUser, 'Anna', 'Nowak', 'anna_nowak@gmail.com', [], 'testPasswordHash', '');
        $userRepository->saveUser($testUser);

        $response = $this->createTest($client, '/api/users/' . $testUser->getId(), true);
        $responseCode = $response->getStatusCode();
        $responseContent = json_decode($response->getContent(), true);

        $userRepository->removeUser($testUser);

        $this->assertEquals($responseCode, Response::HTTP_OK);
        $this->assertEquals($responseContent['status'], 'success');
        $this->assertEquals($responseContent['data']['firstName'], 'Anna');
        $this->assertEquals($responseContent['data']['lastName'], 'Nowak');
        $this->assertEquals($responseContent['data']['email'], 'anna_nowak@gmail.com');
    }

    public function testGetUsersUnauthorized(): void
    {
        $client = static::createClient();
        $userRepository = static::getContainer()->get(UserRepositoryInterface::class);

        $users = array();
        for ($currentUserNumber = 0; $currentUserNumber < 15; $currentUserNumber++)
        {
            $users[$currentUserNumber] = new User();
            User::registerUser($users[$currentUserNumber], 'Anna', 'Nowak', $currentUserNumber . 'anna_nowak@gmail.com', [], 'testPasswordHash', '');
            $userRepository->saveUser($users[$currentUserNumber]);
        }

        $response = $this->createTest($client, '/api/users', false);
        $responseCode = $response->getStatusCode();
        $responseContent = json_decode($response->getContent(), true);

        $this->assertEquals($responseCode, Response::HTTP_FORBIDDEN);
        $this->assertEquals($responseContent['status'], 'fail');

        for ($currentUserNumber = 0; $currentUserNumber < count($users); $currentUserNumber++)
        {
            $userRepository->removeUser($users[$currentUserNumber]);
        }
    }

    // Also tests default pagination.
    public function testGetUsers(): void
    {
        $client = static::createClient();
        $userRepository = static::getContainer()->get(UserRepositoryInterface::class);

        $users = array();
        for ($currentUserNumber = 0; $currentUserNumber < 15; $currentUserNumber++)
        {
            $users[$currentUserNumber] = new User();
            User::registerUser($users[$currentUserNumber], 'Anna', 'Nowak', $currentUserNumber . 'anna_nowak@gmail.com', [], 'testPasswordHash', '');
            $userRepository->saveUser($users[$currentUserNumber]);
        }

        $response = $this->createTest($client, '/api/users', true);
        $responseCode = $response->getStatusCode();
        $responseContent = json_decode($response->getContent(), true);

        $this->assertEquals($responseCode, Response::HTTP_OK);
        $this->assertEquals($responseContent['status'], 'success');
        $this->assertEquals(count($responseContent['data']), 10);

        for ($currentUserNumber = 0; $currentUserNumber < 10; $currentUserNumber++)
        {
            $currentUserData = $responseContent['data'][$currentUserNumber];

            $this->assertEquals($currentUserData['firstName'], 'Anna');
            $this->assertEquals($currentUserData['lastName'], 'Nowak');
            $this->assertEquals($currentUserData['email'], $currentUserNumber . 'anna_nowak@gmail.com');
        }

        for ($currentUserNumber = 0; $currentUserNumber < count($users); $currentUserNumber++)
        {
            $userRepository->removeUser($users[$currentUserNumber]);
        }
    }
}