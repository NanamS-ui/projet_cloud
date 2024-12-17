<?php

namespace App\Repository;

use App\Entity\Users;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Users>
 *
 * @method Users|null find($id, $lockMode = null, $lockVersion = null)
 * @method Users|null findOneBy(array $criteria, array $orderBy = null)
 * @method Users[]    findAll()
 * @method Users[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class UsersRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Users::class);
    }

    /**
     * Exemple : Trouver les utilisateurs par email.
     */
    public function findByEmail(string $email): ?Users
    {
        return $this->findOneBy(['email' => $email]);
    }
    public function login(String $email,String $mdp): ?Users {
        $user = $this->findByEmail($email);
        if($user == null) {return null;}
        else{
            $mdpUser = $user->getPassword();
            if(sha1($mdp) != $mdpUser) {return null;}
            else {return $user;}
        }
        
    }
}
