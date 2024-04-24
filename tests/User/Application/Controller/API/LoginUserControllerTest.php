<?php

namespace App\Tests\User\Application\Controller\API;

use App\User\Domain\Entity\User;
use App\User\Domain\Repository\UserRepositoryInterface;
use App\User\Application\Service\GeneratePasswordService;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

class LoginUserControllerTest extends WebTestCase
{
    // Test whether the login returns a HTTP Basic authorization.
    public function testUnauthorizedLogin(): void
    {
        $client = static::createClient();

        $crawler = $client->request('GET', '/api/login');

        $this->assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
        $this->assertResponseHasHeader('Www-Authenticate');
        $this->assertResponseHeaderSame('Www-Authenticate', 'Basic realm="Secured Area"');
    }

    // Test whether the login returns unauthorized with the wrong e-mail given.
    public function testAuthorizeLoginWrongEmail(): void
    {
        $client = static::createClient();

        $testUser = new User();
        User::registerUser($testUser, 'testFirst', 'testLast', 'testEmail@gmail.com', [User::ROLE_ADMIN], '', 'testApiKey');

        $userRepository = static::getContainer()->get(UserRepositoryInterface::class);

        $generatePasswordService = static::getContainer()->get(GeneratePasswordService::class);
        $plainPassword = $generatePasswordService->handle($testUser);

        $userRepository->saveUser($testUser);

        $userRepresentation = 'anotherTestEmail@gmail.com:' . $plainPassword;
        $client->setServerParameter('HTTP_AUTHORIZATION', 'Basic ' . base64_encode($userRepresentation));
        $crawler = $client->request('GET', '/api/login');

        $userRepository->removeUser($testUser);

        $response = json_decode($client->getResponse()->getContent(), true);

        $this->assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
        $this->assertResponseHasHeader('Www-Authenticate');
        $this->assertResponseHeaderSame('Www-Authenticate', 'Basic realm="Secured Area"');
    }

    // Test whether the login returns unauthorized with the wrong password given.
    public function testAuthorizeLoginWrongPassword(): void
    {
        $client = static::createClient();

        $testUser = new User();
        User::registerUser($testUser, 'testFirst', 'testLast', 'testEmail@gmail.com', [User::ROLE_ADMIN], '', 'testApiKey');

        $userRepository = static::getContainer()->get(UserRepositoryInterface::class);

        $generatePasswordService = static::getContainer()->get(GeneratePasswordService::class);
        $generatePasswordService->handle($testUser);

        $userRepository->saveUser($testUser);

        $userRepresentation = 'testEmail@gmail.com:' . '123';
        $client->setServerParameter('HTTP_AUTHORIZATION', 'Basic ' . base64_encode($userRepresentation));
        $crawler = $client->request('GET', '/api/login');

        $userRepository->removeUser($testUser);

        $response = json_decode($client->getResponse()->getContent(), true);

        $this->assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
        $this->assertResponseHasHeader('Www-Authenticate');
        $this->assertResponseHeaderSame('Www-Authenticate', 'Basic realm="Secured Area"');
    }

    public function testAuthorizedLogin(): void
    {
        $client = static::createClient();

        $testUser = new User();
        User::registerUser($testUser, 'testFirst', 'testLast', 'testEmail@gmail.com', [User::ROLE_ADMIN], '', 'testApiKey');

        $userRepository = static::getContainer()->get(UserRepositoryInterface::class);

        $generatePasswordService = static::getContainer()->get(GeneratePasswordService::class);
        $plainPassword = $generatePasswordService->handle($testUser);

        $userRepository->saveUser($testUser);
        $userId = $testUser->getId();

        $userRepresentation = 'testEmail@gmail.com:' . $plainPassword;
        $client->setServerParameter('HTTP_AUTHORIZATION', 'Basic ' . base64_encode($userRepresentation));
        $crawler = $client->request('GET', '/api/login');

        $userRepository->removeUser($testUser);

        $response = json_decode($client->getResponse()->getContent(), true);

        $this->assertResponseStatusCodeSame(Response::HTTP_OK);

        $this->assertEquals($response['status'], 'success');
        $this->assertEquals($response['data']['id'], $userId);
        $this->assertEquals($response['data']['email'], 'testEmail@gmail.com');
        $this->assertEquals($response['data']['apiKey'], 'testApiKey');
    }
}