<?php

namespace App\Repository;

use App\Entity\Evenement;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Evenement>
 *
 * @method Evenement|null find($id, $lockMode = null, $lockVersion = null)
 * @method Evenement|null findOneBy(array $criteria, array $orderBy = null)
 * @method Evenement[]    findAll()
 * @method Evenement[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class EvenementRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Evenement::class);
    }
    // src/Repository/EvenementRepository.php

public function searchEvents(?string $nomEvent, ?int $categorieId)
{
    $queryBuilder = $this->createQueryBuilder('e');

    if ($nomEvent) {
        $queryBuilder->andWhere('e.nomEvent LIKE :nomEvent')
                     ->setParameter('nomEvent', '%' . $nomEvent . '%');
    }

    if ($categorieId) {
        $queryBuilder->andWhere('e.categorie = :categorieId')
                     ->setParameter('categorieId', $categorieId);
    }

    return $queryBuilder->getQuery()->getResult();
}

// src/Repository/EvenementRepository.php

public function findEventsByFilters(?string $search = null, ?string $type = null, ?\DateTime $date = null)
{
    $queryBuilder = $this->createQueryBuilder('e');
    
    if ($search) {
        $queryBuilder->andWhere('e.nomEvent LIKE :search')
                     ->setParameter('search', '%' . $search . '%');
    }
    
    if ($type) {
        $queryBuilder->andWhere('e.type = :type')
                     ->setParameter('type', $type);
    }
    
    if ($date) {
        $startOfDay = (clone $date)->setTime(0, 0, 0);
        $endOfDay = (clone $date)->setTime(23, 59, 59);
        
        $queryBuilder->andWhere('e.startDate BETWEEN :startOfDay AND :endOfDay')
                     ->setParameter('startOfDay', $startOfDay)
                     ->setParameter('endOfDay', $endOfDay);
    }
    
    return $queryBuilder->getQuery()->getResult();
}

public function findEventsForCalendar()
{
    return $this->createQueryBuilder('e')
        ->select('e.id', 'e.nomEvent', 'e.startDate', 'e.endDate', 'e.lieux')
        ->getQuery()
        ->getResult();
}

public function findEventsByClub(int $clubId)
{
    return $this->createQueryBuilder('e')
        ->andWhere('e.club = :clubId')
        ->setParameter('clubId', $clubId)
        ->getQuery()
        ->getResult();
}

public function findUpcomingEvents(int $limit = 5)
{
    return $this->createQueryBuilder('e')
        ->andWhere('e.startDate >= :now')
        ->setParameter('now', new \DateTime())
        ->orderBy('e.startDate', 'ASC')
        ->setMaxResults($limit)
        ->getQuery()
        ->getResult();
}


//    /**
//     * @return Evenement[] Returns an array of Evenement objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('e')
//            ->andWhere('e.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('e.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?Evenement
//    {
//        return $this->createQueryBuilder('e')
//            ->andWhere('e.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
