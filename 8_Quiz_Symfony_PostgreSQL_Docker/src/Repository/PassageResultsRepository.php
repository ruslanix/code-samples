<?php

namespace App\Repository;

use App\Entity\PassageResults;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<PassageResults>
 *
 * @method PassageResults|null find($id, $lockMode = null, $lockVersion = null)
 * @method PassageResults|null findOneBy(array $criteria, array $orderBy = null)
 * @method PassageResults[]    findAll()
 * @method PassageResults[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class PassageResultsRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PassageResults::class);
    }
}
