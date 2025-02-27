<?php

namespace App\Repository;

use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;

/**
 * @extends ServiceEntityRepository<User>
 *
 * @method User|null find($id, $lockMode = null, $lockVersion = null)
 * @method User|null findOneBy(array $criteria, array $orderBy = null)
 * @method User[]    findAll()
 * @method User[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class UserRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
    }
    // src/Repository/UserRepository.php

// Add this method to your existing UserRepository class
public function findByStatus(string $status): array
{
    return $this->createQueryBuilder('u')
        ->where('u.status = :status')
        ->setParameter('status', $status)
        ->getQuery()
        ->getResult();
}
// public function loadVerifiedUserByIdentifier(string $identifier): UserInterface
// {
//     $user = $this->createQueryBuilder('u')
//         ->where('u.email = :email')
//         ->andWhere('u.isVerified = :verified')
//         ->setParameter('email', $identifier)
//         ->setParameter('verified', true)
//         ->getQuery()
//         ->getOneOrNullResult();

//     if (!$user) {
//         throw new UserNotFoundException('User not found or not verified');
//     }

//     return $user;
// }

//    /**
//     * @return User[] Returns an array of User objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('u')
//            ->andWhere('u.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('u.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?User
//    {
//        return $this->createQueryBuilder('u')
//            ->andWhere('u.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
