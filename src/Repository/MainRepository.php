<?php

namespace AdminCore\Repository;

use AdminCore\Entity\Main;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Main>
 */
class MainRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Main::class);
    }

    public function findOneByFullPath(string $path): ?Main
    {
        return $this->findOneBy(['fullPath' => $path]);
    }
}
