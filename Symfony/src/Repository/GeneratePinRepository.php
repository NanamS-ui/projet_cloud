<?php
namespace App\Repository;

use App\Entity\GeneratePin;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use DateTime;

class GeneratePinRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, GeneratePin::class);
    }

    // Méthode pour trouver un GeneratePin par userId, plage de dates et pin
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
    
}
