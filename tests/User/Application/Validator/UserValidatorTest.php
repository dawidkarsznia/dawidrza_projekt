<?php

namespace App\Tests\User\Application\Validator;

use App\User\Application\Validation\UserValidator;
use PHPUnit\Framework\TestCase;

final class UserValidatorTest extends TestCase
{
    public function testValidateUserFirstName(): void
    {
        $firstName1 = '123';
        self::assertFalse(UserValidator::validateUserFirstName($firstName1));

        $firstName2 = 'Jan123';
        self::assertFalse(UserValidator::validateUserFirstName($firstName2));

        $firstName3 = 'Jan@!';
        self::assertFalse(UserValidator::validateUserFirstName($firstName3));

        $firstName4 = 'a';
        self::assertFalse(UserValidator::validateUserFirstName($firstName4));

        $firstName5 = 'Jan';
        self::assertTrue(UserValidator::validateUserFirstName($firstName5));
    }

    public function testValidateUserLastName(): void
    {
        $lastName1 = '123';
        self::assertFalse(UserValidator::validateUserLastName($lastName1));

        $lastName2 = 'Kowalski123';
        self::assertFalse(UserValidator::validateUserLastName($lastName2));

        $lastName3 = 'Kowalski@!';
        self::assertFalse(UserValidator::validateUserLastName($lastName3));

        $lastName4 = 'a';
        self::assertFalse(UserValidator::validateUserLastName($lastName4));

        $lastName5 = 'Kowalski';
        self::assertTrue(UserValidator::validateUserLastName($lastName5));
    }
}