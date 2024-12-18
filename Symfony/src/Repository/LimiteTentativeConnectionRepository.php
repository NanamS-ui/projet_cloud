<?php
namespace App\Repository;
use App\Entity\LimiteTentativeConnection;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
class LimiteTentativeConnectionRepository extends ServiceEntityRepository{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, LimiteTentativeConnection::class);
    }
    

}