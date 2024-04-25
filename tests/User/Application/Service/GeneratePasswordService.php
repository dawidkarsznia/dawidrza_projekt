<?php

namespace App\Tests\User\Application\Service;

use App\User\Domain\Entity\User;
use App\User\Domain\Repository\UserRepositoryInterface;
use App\User\Application\Service\GeneratePasswordService;
use PHPUnit\Framework\TestCase;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final class GeneratePasswordServiceTest extends KernelTestCase
{
    public function testGenerateApiKeyService(): void
    {
        self::bootKernel();

        $testUser = new User();
        $userRepository = static::getContainer()->get(UserRepositoryInterface::class);

        $passwordHasher = static::getContainer()->get(UserPasswordHasherInterface::class);
        $generatePasswordService = self::getContainer()->get(GeneratePasswordService::class);
        $plainPassword = $generatePasswordService->handle($testUser);

        $userRepository->saveUser($testUser);

        $this->assertEquals(strlen($plainPassword), 32);
        $this->assertEquals($passwordHasher->isPasswordValid($testUser, $plainPassword), true);

        $userRepository->removeUser($testUser);
    }
}