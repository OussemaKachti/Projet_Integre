<?php
namespace App\Controller;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class AdminController extends AbstractController
{
    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    #[Route('/admin', name: 'app_admin_dashboard')]
    public function dashboard(): Response
    {
        
        $users = $this->entityManager->getRepository(User::class)
            ->createQueryBuilder('user')
            ->where('user.role NOT LIKE :role')
            ->setParameter('role', '%administrateur%')
            ->getQuery()
            ->getResult();

        return $this->render('admin.html.twig', [
            'users' => $users,
        ]);
    }
    #[Route('/admin/user/delete/{id}', name: 'app_admin_delete_user', methods: ['POST', 'GET'])]
    public function deleteUser(int $id): Response
{
    $user = $this->entityManager->getRepository(User::class)->find($id);

    if (!$user) {
        throw $this->createNotFoundException('User not found');
    }

    $this->entityManager->remove($user);
    $this->entityManager->flush();

    $this->addFlash('success', 'User deleted successfully.');

    return $this->redirectToRoute('app_admin_dashboard');
}
}