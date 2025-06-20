<?php

namespace App\Repository;

use App\Entity\ParticipationMembre;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\Query;

/**
 * @extends ServiceEntityRepository<ParticipationMembre>
 *
 * @method ParticipationMembre|null find($id, $lockMode = null, $lockVersion = null)
 * @method ParticipationMembre|null findOneBy(array $criteria, array $orderBy = null)
 * @method ParticipationMembre[]    findAll()
 * @method ParticipationMembre[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ParticipationMembreRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ParticipationMembre::class);
    }

    public function findParticipationDetails(int $participationId): ?array
{
    return $this->createQueryBuilder('p')
        ->select('p.id', 'p.dateRequest', 'p.statut', 'p.description', 'u.id AS userId', 'u.nom', 'u.prenom', 'u.email', 'c.id AS clubId', 'c.nomC')
        ->join('p.user', 'u')
        ->join('p.club', 'c')
        ->where('p.id = :participationId')
        ->setParameter('participationId', $participationId)
        ->getQuery()
        ->getOneOrNullResult();
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
//find the most popular club in the last week

// Custom method to find the most popular clubs in the last week





//    /**
//     * @return ParticipationMembre[] Returns an array of ParticipationMembre objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('p')
//            ->andWhere('p.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('p.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?ParticipationMembre
//    {
//        return $this->createQueryBuilder('p')
//            ->andWhere('p.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
