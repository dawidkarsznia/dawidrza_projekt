<?php

namespace App\Tests\User\Application\Controller\API;

use App\User\Domain\Entity\User;
use App\User\Domain\Repository\UserRepositoryInterface;
use App\User\Application\Service\GeneratePasswordService;
use App\User\Application\Service\GenerateApiKeyService;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;

class BlockUserControllerTest extends WebTestCase
{
    private const AUTHORIZED_USER_FIRST_NAME = 'Jan';
    private const AUTHORIZED_USER_LAST_NAME = 'Kowalski';
    private const AUTHORIZED_USER_EMAIL = 'jankowalski@gmail.com';

    private function createTest(KernelBrowser $client, int $userId, bool $block, bool $isAuthorized = true, bool $incorrectApiKey = false): Response
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

        $path = '/api/users/';
        $path .= (true === $block) ? 'block/' : 'unblock/';
        $path .= $userId;

        $crawler = $client->request('POST', $path);

        $userRepository->removeUser($authorizedUser);

        return $client->getResponse();
    }

    public function testBlockUserUnauthorized(): void
    {
        $client = static::createClient();

        $testUser = new User();
        $userRepository = static::getContainer()->get(UserRepositoryInterface::class);
        User::registerUser($testUser, 'testFirst', 'testLast', 'testEmail2@gmail.com', [], 'testPasswordHash2', 'testApiKey');
        $userRepository->saveUser($testUser);

        $response = $this->createTest($client, $testUser->getId(), true, false);
        $responseCode = $response->getStatusCode();
        $responseContent = json_decode($response->getContent(), true);

        $this->assertEquals($responseCode, Response::HTTP_FORBIDDEN);
        $this->assertEquals($responseContent['status'], 'fail');

        $commitedUser = $userRepository->findOneUserBy(['id' => $testUser->getId()]);
        $this->assertEquals($commitedUser->isActive(), true);

        $userRepository->removeUser($testUser);
    } 

    public function testBlockUserWrongId(): void
    {
        $client = static::createClient();

        $testUser = new User();
        $userRepository = static::getContainer()->get(UserRepositoryInterface::class);
        User::registerUser($testUser, 'testFirst', 'testLast', 'testEmail2@gmail.com', [], 'testPasswordHash2', 'testApiKey');
        $userRepository->saveUser($testUser);

        $response = $this->createTest($client, $testUser->getId() + 2, true);
        $responseCode = $response->getStatusCode();
        $responseContent = json_decode($response->getContent(), true);

        $this->assertEquals($responseCode, Response::HTTP_NOT_FOUND);
        $this->assertEquals($responseContent['status'], 'fail');

        $commitedUser = $userRepository->findOneUserBy(['id' => $testUser->getId()]);
        $this->assertEquals($commitedUser->isActive(), true);

        $userRepository->removeUser($testUser);
    }

    public function testBlockUserWrongKey(): void
    {
        $client = static::createClient();

        $testUser = new User();
        $userRepository = static::getContainer()->get(UserRepositoryInterface::class);
        User::registerUser($testUser, 'testFirst', 'testLast', 'testEmail2@gmail.com', [], 'testPasswordHash2', 'testApiKey');
        $userRepository->saveUser($testUser);

        $response = $this->createTest($client, $testUser->getId(), true, true, true);
        $responseCode = $response->getStatusCode();
        $responseContent = json_decode($response->getContent(), true);

        $this->assertEquals($responseCode, Response::HTTP_NOT_FOUND);
        $this->assertEquals($responseContent['status'], 'fail');

        $commitedUser = $userRepository->findOneUserBy(['id' => $testUser->getId()]);
        $this->assertEquals($commitedUser->isActive(), true);

        $userRepository->removeUser($testUser);
    }

    public function testBlockUser(): void
    {
        $client = static::createClient();

        $testUser = new User();
        $userRepository = static::getContainer()->get(UserRepositoryInterface::class);
        User::registerUser($testUser, 'testFirst', 'testLast', 'testEmail2@gmail.com', [], 'testPasswordHash2', 'testApiKey');
        $userRepository->saveUser($testUser);

        $response = $this->createTest($client, $testUser->getId(), true);
        $responseCode = $response->getStatusCode();
        $responseContent = json_decode($response->getContent(), true);

        $this->assertEquals($responseCode, Response::HTTP_OK);
        $this->assertEquals($responseContent['status'], 'success');

        $commitedUser = $userRepository->findOneUserBy(['id' => $testUser->getId()]);
        $this->assertEquals($commitedUser->isActive(), false);

        $userRepository->removeUser($testUser);
    }

    public function testUnblockUserUnauthorized(): void
    {
        $client = static::createClient();

        $testUser = new User();
        $userRepository = static::getContainer()->get(UserRepositoryInterface::class);
        User::registerUser($testUser, 'testFirst', 'testLast', 'testEmail2@gmail.com', [], 'testPasswordHash2', 'testApiKey');
        $testUser->setActive(false);
        $userRepository->saveUser($testUser);

        $response = $this->createTest($client, $testUser->getId(), false, false);
        $responseCode = $response->getStatusCode();
        $responseContent = json_decode($response->getContent(), true);

        $this->assertEquals($responseCode, Response::HTTP_FORBIDDEN);
        $this->assertEquals($responseContent['status'], 'fail');

        $commitedUser = $userRepository->findOneUserBy(['id' => $testUser->getId()]);
        $this->assertEquals($commitedUser->isActive(), false);

        $userRepository->removeUser($testUser);
    } 

    public function testUnblockUserWrongId(): void
    {
        $client = static::createClient();

        $testUser = new User();
        $userRepository = static::getContainer()->get(UserRepositoryInterface::class);
        User::registerUser($testUser, 'testFirst', 'testLast', 'testEmail2@gmail.com', [], 'testPasswordHash2', 'testApiKey');
        $testUser->setActive(false);
        $userRepository->saveUser($testUser);

        $response = $this->createTest($client, $testUser->getId() + 2, false);
        $responseCode = $response->getStatusCode();
        $responseContent = json_decode($response->getContent(), true);

        $this->assertEquals($responseCode, Response::HTTP_NOT_FOUND);
        $this->assertEquals($responseContent['status'], 'fail');

        $commitedUser = $userRepository->findOneUserBy(['id' => $testUser->getId()]);
        $this->assertEquals($commitedUser->isActive(), false);

        $userRepository->removeUser($testUser);
    }

    public function testUnblockUserWrongKey(): void
    {
        $client = static::createClient();

        $testUser = new User();
        $userRepository = static::getContainer()->get(UserRepositoryInterface::class);
        User::registerUser($testUser, 'testFirst', 'testLast', 'testEmail2@gmail.com', [], 'testPasswordHash2', 'testApiKey');
        $testUser->setActive(false);
        $userRepository->saveUser($testUser);

        $response = $this->createTest($client, $testUser->getId(), false, true, true);
        $responseCode = $response->getStatusCode();
        $responseContent = json_decode($response->getContent(), true);

        $this->assertEquals($responseCode, Response::HTTP_NOT_FOUND);
        $this->assertEquals($responseContent['status'], 'fail');

        $commitedUser = $userRepository->findOneUserBy(['id' => $testUser->getId()]);
        $this->assertEquals($commitedUser->isActive(), false);

        $userRepository->removeUser($testUser);
    }

    public function testUnblockUser(): void
    {
        $client = static::createClient();

        $testUser = new User();
        $userRepository = static::getContainer()->get(UserRepositoryInterface::class);
        User::registerUser($testUser, 'testFirst', 'testLast', 'testEmail2@gmail.com', [], 'testPasswordHash2', 'testApiKey');
        $testUser->setActive(false);
        $userRepository->saveUser($testUser);

        $response = $this->createTest($client, $testUser->getId(), false);
        $responseCode = $response->getStatusCode();
        $responseContent = json_decode($response->getContent(), true);

        $this->assertEquals($responseCode, Response::HTTP_OK);
        $this->assertEquals($responseContent['status'], 'success');

        $commitedUser = $userRepository->findOneUserBy(['id' => $testUser->getId()]);
        $this->assertEquals($commitedUser->isActive(), true);

        $userRepository->removeUser($testUser);
    }
}