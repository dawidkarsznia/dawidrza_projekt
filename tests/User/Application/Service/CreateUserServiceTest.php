<?php

namespace App\Tests\User\Application\Service;

use App\User\Domain\Entity\User;
use App\User\Domain\Repository\UserRepositoryInterface;
use App\User\Application\Service\CreateUserService;
use PHPUnit\Framework\TestCase;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class CreateUserServiceTest extends KernelTestCase
{
    public function testCreateUserService(): void
    {
        self::bootKernel();

        $testUser = new User();
        $userRepository = static::getContainer()->get(UserRepositoryInterface::class);

        $createUserService = self::getContainer()->get(CreateUserService::class);
        $userRepresentation = $createUserService->handle($testUser, 'Anna', 'Nowak', 'anna_nowak@gmail.com', [User::ROLE_ADMIN]);

        $userRepository->saveUser($testUser);

        $this->assertEquals($testUser->getFirstName(), 'Anna');
        $this->assertEquals($testUser->getLastName(), 'Nowak');
        $this->assertEquals($testUser->getEmail(), 'anna_nowak@gmail.com');
        $this->assertEquals(in_array(User::ROLE_ADMIN, $testUser->getRoles()), true);
        $this->assertEquals($testUser->getPassword(), '');
        $this->assertEquals($testUser->getApiKey(), '');

        $userRepository->removeUser($testUser);
    }
}