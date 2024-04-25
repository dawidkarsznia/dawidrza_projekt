<?php

namespace App\Tests\User\Application\Service;

use App\User\Domain\Entity\User;
use App\User\Domain\Repository\UserRepositoryInterface;
use App\User\Application\Service\GenerateApiKeyService;
use PHPUnit\Framework\TestCase;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class GenerateApiKeyServiceTest extends KernelTestCase
{
    public function testGenerateApiKeyService(): void
    {
        self::bootKernel();

        $testUser = new User();
        $userRepository = static::getContainer()->get(UserRepositoryInterface::class);

        $generateApiKeyService = self::getContainer()->get(GenerateApiKeyService::class);
        $apiKey = $generateApiKeyService->handle($testUser);

        $userRepository->saveUser($testUser);

        $this->assertEquals($testUser->getApiKey(), $apiKey);
        $this->assertEquals(strlen($testUser->getApiKey()), 32);

        $userRepository->removeUser($testUser);
    }
}