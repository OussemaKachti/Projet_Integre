<?php

namespace App\Repository;
use App\Entity\User;

use App\Entity\Sondage;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Sondage>
 *
 * @method Sondage|null find($id, $lockMode = null, $lockVersion = null)
 * @method Sondage|null findOneBy(array $criteria, array $orderBy = null)
 * @method Sondage[]    findAll()
 * @method Sondage[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class SondageRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Sondage::class);
    }

//    /**
//     * @return Sondage[] Returns an array of Sondage objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('s')
//            ->andWhere('s.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('s.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?Sondage
//    {
//        return $this->createQueryBuilder('s')
//            ->andWhere('s.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }


public function findSondagesByUser(User $user): array
{
    return $this->createQueryBuilder('s')
        ->where('s.user = :user')
        ->setParameter('user', $user)
        ->orderBy('s.createdAt', 'DESC') // Trier par date de création
        ->getQuery()
        ->getResult();
}

public function searchByQuestion(string $query)
{
    return $this->createQueryBuilder('s')
        ->leftJoin('s.choix', 'c')
        ->addSelect('c')
        ->where('LOWER(s.question) LIKE LOWER(:query)')
        ->setParameter('query', '%' . $query . '%')
        ->orderBy('s.createdAt', 'DESC')
        ->setMaxResults(10)
        ->getQuery()
        ->getResult();
}
public function advancedSearch(string $query, array $dateFilter, ?string $clubName)
{
    $qb = $this->createQueryBuilder('s')
        ->leftJoin('s.club', 'c')
        ->leftJoin('s.user', 'u')
        ->where('s.question LIKE :query')
        ->setParameter('query', '%' . $query . '%');

    // Filtrer par date si spécifié
    if (isset($dateFilter['start'])) {
        $qb->andWhere('s.createdAt >= :startDate')
           ->setParameter('startDate', $dateFilter['start']);
    }
    if (isset($dateFilter['end'])) {
        // Ajuster l'heure à la fin de la journée pour inclure tous les sondages jusqu'à 23:59:59
        $endDate = $dateFilter['end']->setTime(23, 59, 59);
        $qb->andWhere('s.createdAt <= :endDate')
           ->setParameter('endDate', $endDate);
    }

    // Filtrer par nom de club si spécifié
    if ($clubName) {
        $qb->andWhere('c.nomC LIKE :clubName')
           ->setParameter('clubName', '%' . $clubName . '%');
    }

    return $qb->getQuery()->getResult();
}

public function count(array $criteria = []): int
{
    $qb = $this->createQueryBuilder('s');
    
    foreach ($criteria as $field => $value) {
        $qb->andWhere("s.$field = :$field")
           ->setParameter($field, $value);
    }
    
    return $qb->select('COUNT(s.id)')
        ->getQuery()
        ->getSingleScalarResult();
}

public function getWeeklyStats(): array
{
    $stats = [
        'polls' => array_fill(0, 7, 0),
        'votes' => array_fill(0, 7, 0)
    ];
    
    // Récupérer les données brutes des 7 derniers jours
    $qb = $this->createQueryBuilder('s')
        ->select('s.createdAt, COUNT(s.id) as polls, COUNT(r.id) as votes')
        ->leftJoin('s.reponses', 'r')
        ->where('s.createdAt >= :weekAgo')
        ->setParameter('weekAgo', new \DateTime('-7 days'))
        ->groupBy('s.createdAt')
        ->orderBy('s.createdAt', 'ASC');

    $results = $qb->getQuery()->getResult();

    // Traiter les résultats et les formater par jour
    foreach ($results as $result) {
        $date = $result['createdAt']->format('Y-m-d');
        $dayIndex = (new \DateTime($date))->diff(new \DateTime('-7 days'))->days;
        
        if ($dayIndex >= 0 && $dayIndex < 7) {
            $stats['polls'][$dayIndex] = (int)$result['polls'];
            $stats['votes'][$dayIndex] = (int)$result['votes'];
        }
    }

    return $stats;
}

}