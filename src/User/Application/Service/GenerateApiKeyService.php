<?php

namespace App\User\Application\Service;

use App\User\Domain\Entity\User;
use App\User\Domain\Repository\UserRepositoryInterface;
use Symfony\Component\String\ByteString;

final class GenerateApiKeyService
{
    private UserRepositoryInterface $userRepository;

    public function __construct(
        UserRepositoryInterface $userRepository
    ) {
        $this->userRepository = $userRepository;
    }

    public function handle(User $user): string
    {
        $apiKey = ByteString::fromRandom(32)->toString();
        $user->setApiKey($apiKey);

        return $apiKey;
    }
}