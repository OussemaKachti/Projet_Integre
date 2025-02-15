<?php

namespace App\Repository;

use App\Entity\ChoixSondage;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ChoixSondage>
 *
 * @method ChoixSondage|null find($id, $lockMode = null, $lockVersion = null)
 * @method ChoixSondage|null findOneBy(array $criteria, array $orderBy = null)
 * @method ChoixSondage[]    findAll()
 * @method ChoixSondage[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ChoixSondageRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ChoixSondage::class);
    }

//    /**
//     * @return ChoixSondage[] Returns an array of ChoixSondage objects
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

//    public function findOneBySomeField($value): ?ChoixSondage
//    {
//        return $this->createQueryBuilder('c')
//            ->andWhere('c.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
