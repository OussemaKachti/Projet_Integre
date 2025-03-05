<?php

namespace App\Repository;

use App\Entity\Club;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;



/**
 * @extends ServiceEntityRepository<Club>
 *
 * @method Club|null find($id, $lockMode = null, $lockVersion = null)
 * @method Club|null findOneBy(array $criteria, array $orderBy = null)
 * @method Club[]    findAll()
 * @method Club[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ClubRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Club::class);
    }

    

public function searchByKeyword(string $keyword): Query
{
    return $this->createQueryBuilder('c')
        ->leftJoin('c.club', 'c')  // Joindre la table Club
        ->where('c.nomC LIKE :keyword')
        ->setParameter('keyword', '%' . $keyword . '%')
        ->orderBy('c.id', 'ASC') // Optionnel : trier par ID
        ->getQuery();
        
}
//    /**
//     * @return Club[] Returns an array of Club objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('c')
//            ->andWhere('c.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('c.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?Club
//    {
//        return $this->createQueryBuilder('c')
//            ->andWhere('c.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }

// src/Repository/ClubRepository.php

public function findByName(string $name): array
{
    return $this->createQueryBuilder('c')
        ->andWhere('c.nomC LIKE :name')
        ->setParameter('name', '%' . $name . '%')
        ->getQuery()
        ->getResult();
}

public function findById(int $id): ?Club
{
    return $this->createQueryBuilder('c')
        ->andWhere('c.id = :id')
        ->setParameter('id', $id)
        ->getQuery()
        ->getOneOrNullResult();
}

    public function findMostActiveClub(): ?Club
    {
        return $this->createQueryBuilder('c')
            ->leftJoin('c.sondages', 's')
            ->groupBy('c.id')
            ->orderBy('COUNT(s.id)', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
