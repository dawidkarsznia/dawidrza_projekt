<?php

namespace App\User\Domain\Repository;

use App\User\Domain\Entity\User;

interface UserRepositoryInterface
{
    public function findUser($id, $lockMode = null, ?int $lockVersion = null): ?User;
    public function findOneUserBy(array $criteria): ?User;
    public function findAllUsers(int $pageNumber = 1, int $pageLimit = 10): array;
    public function saveUser(User $user): void;
    public function removeUser(User $user): void;
}