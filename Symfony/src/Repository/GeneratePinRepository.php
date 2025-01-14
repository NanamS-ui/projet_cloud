<?php

namespace App\Repository;

use App\Entity\GeneratePin;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use DateTime;

/**
 * @extends ServiceEntityRepository<GeneratePin>
 */
class GeneratePinRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, GeneratePin::class);
    }

    public function findByUserIdAndDateRangeAndPin(int $userId, DateTime $date, string $pin)
    {
        return $this->createQueryBuilder('g')
            ->andWhere('g.users = :userId')
            ->andWhere('g.dateDebut <= :date')
            ->andWhere('g.dateFin >= :date')
            ->andWhere('g.pin = :pin')
            ->setParameter('userId', $userId)
            ->setParameter('date', $date)
            ->setParameter('date', $date)
            ->setParameter('pin', $pin)
            ->getQuery()
            ->getResult();
    }

    public function deleteExpiredPins(DateTime $date): int
    {
        $queryBuilder = $this->createQueryBuilder('g')
            ->delete()
            ->andWhere('g.dateFin < :date')
            ->setParameter('date', $date);

        return $queryBuilder->getQuery()->execute();
    }

    //    /**
    //     * @return GeneratePin[] Returns an array of GeneratePin objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('g')
    //            ->andWhere('g.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('g.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?GeneratePin
    //    {
    //        return $this->createQueryBuilder('g')
    //            ->andWhere('g.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }


}
