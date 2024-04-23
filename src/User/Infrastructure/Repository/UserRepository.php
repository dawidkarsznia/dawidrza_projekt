<?php

namespace App\User\Infrastructure\Repository;

use App\User\Domain\Entity\User;
use App\User\Domain\Repository\UserRepositoryInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Knp\Component\Pager\PaginatorInterface; 

final class UserRepository extends ServiceEntityRepository implements UserRepositoryInterface
{
    private ManagerRegistry $registry;
    private PaginatorInterface $paginator;

    public function __construct(
        ManagerRegistry $registry,
        PaginatorInterface $paginator
    ) {
        $this->registry = $registry;
        $this->paginator = $paginator;

        parent::__construct($registry, User::class);
    }

    public function findUser($id, $lockMode = null, ?int $lockVersion = null): ?User
    {
        return $this->find($id, $lockMode, $lockVersion);
    }

    public function findOneUserBy(array $criteria): ?User
    {
        return $this->findOneBy($criteria);
    }

    public function findAllUsers(int $pageNumber = 1, int $pageLimit = 10, string $orderField = 'id'): array
    {
        $queryBuilder = $this->createQueryBuilder('p')->select('p');

        $pagination = $this->paginator->paginate(
            $queryBuilder->getQuery(),
            $pageNumber,
            $pageLimit
        );

        return $pagination->getItems();
    }

    public function saveUser(User $user): void
    {
        $this->getEntityManager()->persist($user);
        $this->getEntityManager()->flush();
    }

    public function removeUser(User $user): void
    {
        $this->getEntityManager()->remove($user);
        $this->getEntityManager()->flush();
    }
}