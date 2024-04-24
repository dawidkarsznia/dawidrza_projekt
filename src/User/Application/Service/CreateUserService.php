<?php

namespace App\User\Application\Service;

use App\User\Domain\Entity\User;
use App\User\Domain\Repository\UserRepositoryInterface;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Form\Exception\InvalidArgumentException;

class CreateUserService
{
    private UserRepositoryInterface $userRepository;
    private UserPasswordHasherInterface $passwordHasher;
    private SerializerInterface $serializer;
    private ValidatorInterface $validator;

    public function __construct(
        UserRepositoryInterface $userRepository,
        UserPasswordHasherInterface $passwordHasher,
        SerializerInterface $serializer,
        ValidatorInterface $validator
    ) {
        $this->userRepository = $userRepository;
        $this->passwordHasher = $passwordHasher;
        $this->serializer = $serializer;
        $this->validator = $validator;
    }

    public function handle(User $user, string $firstName, string $lastName, string $email, array $roles): string
    {
        User::registerUser(
            $user,
            $firstName,
            $lastName,
            $email,
            $roles,
            '',
            ''
        );

        return $this->serializer->serialize($user, 'json');
    }
}