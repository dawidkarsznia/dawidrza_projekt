<?php

namespace App\User\Application\Validation;

use App\User\Domain\Entity\User;
use App\User\Domain\Repository\UserRepositoryInterface;

final class UserValidator
{
    private UserRepositoryInterface $userRepository;

    public function __construct(
        UserRepositoryInterface $userRepository
    )
    {
        $this->userRepository = $userRepository;
    }

    public function validateUserFirstName($firstName): bool
    {
        if (!preg_match('/^[a-zA-Z]+$/', $firstName) || strlen($firstName) < 2)
        {
            return false;
        }

        return true;
    }

    public function validateUserLastName($lastName): bool
    {
        if (!preg_match('/^[a-zA-Z]+$/', $lastName) || strlen($lastName) < 2)
        {
            return false;
        }

        return true;
    }

    public function validateUserEmail($email): bool
    {
        if (!filter_var($email, FILTER_VALIDATE_EMAIL))
        {
            return false;
        }

        if (null !== $this->userRepository->findOneUserBy(['email' => $email]))
        {
            return true;
        }

        return true;
    }
}