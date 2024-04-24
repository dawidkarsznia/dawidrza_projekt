<?php

namespace App\Tests\User\Domain\Entity;

use App\User\Domain\Entity\User;
use PHPUnit\Framework\TestCase;

final class UserTest extends TestCase
{
    // Test whether the user is properly registered.
    public function testRegisterUser(): void
    {
        $testUser = new User();
        User::registerUser($testUser, 'testFirst', 'testLast', 'testEmail@gmail.com', [User::ROLE_ADMIN], 'testHash', 'testApiKey');

        self::assertTrue('testFirst' === $testUser->getFirstName());
        self::assertTrue('testLast' === $testUser->getLastName());
        self::assertTrue('testEmail@gmail.com' === $testUser->getEmail());
        self::assertTrue(in_array(User::ROLE_ADMIN, $testUser->getRoles()));
        self::assertTrue('testHash' === $testUser->getPassword());
        self::assertTrue('testApiKey' === $testUser->getApiKey());
        self::assertTrue(true === $testUser->isActive());
    }

    // Test whether the e-mail is the user identifier.
    public function testEmailIdentifierUser(): void
    {
        $testUser = new User();
        User::registerUser($testUser, 'testFirst', 'testLast', 'testEmail@gmail.com', [User::ROLE_ADMIN], 'testHash', 'testApiKey');

        self::assertTrue($testUser->getUserIdentifier() === $testUser->getEmail());
    }

    // Tests whether the user always has a 'ROLE_USER' role.
    public function testDefaultRoleUser(): void
    {
        $testUser1 = new User();
        User::registerUser($testUser1, 'testFirst', 'testLast', 'testEmail1@gmail.com', [], 'testHash', 'testApiKey1');

        self::assertTrue(in_array(User::ROLE_USER, $testUser1->getRoles()));

        $testUser2 = new User();
        User::registerUser($testUser2, 'testFirst', 'testLast', 'testEmail2@gmail.com', [User::ROLE_ADMIN], 'testHash', 'testApiKey2');

        self::assertTrue(in_array(User::ROLE_USER, $testUser2->getRoles()));
        self::assertTrue(in_array(User::ROLE_ADMIN, $testUser2->getRoles()));
    }
}