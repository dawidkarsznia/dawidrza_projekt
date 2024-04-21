<?php

namespace App\Validator;

use Symfony\Component\Console\Exception\InvalidArgumentException;
use function Symfony\Component\String\u;

class UserValidator
{
    public function validateName(?string $name): string
    {
        if (empty($name)) {
            throw new InvalidArgumentException('The name can not be empty.');
        }

        if (1 !== preg_match("/^[a-zA-Z]+$/", $name)) {
            throw new InvalidArgumentException('The name must contain only letters.');
        }

        return $name;
    }

    public function validatePassword(?string $password): string
    {
        if (empty($password)) {
            throw new InvalidArgumentException('The password can not be empty.');
        }

        if (u($password)->trim()->length() < 6) {
            throw new InvalidArgumentException('The password must be at least 6 characters long.');
        }

        return $password;
    }

    public function validateEmail(?string $email): string
    {
        if (empty($email)) {
            throw new InvalidArgumentException('The email can not be empty.');
        }

        if (null === u($email)->indexOf('@')) {
            throw new InvalidArgumentException('The email provided is not an email (does not contain the @ symbol).');
        }

        return $email;
    }
}
