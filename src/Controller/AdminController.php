<?php

namespace App\Controller;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\HttpFoundation\Request;

class AdminController extends AbstractController
{
    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    #[Route('/admin', name: 'app_admin_dashboard')]
    public function dashboard(Request $request, PaginatorInterface $paginator): Response
    {
        // Fetch users excluding those with the 'administrateur' role
        $queryBuilder = $this->entityManager->getRepository(User::class)
            ->createQueryBuilder('user')
            ->where('user.role NOT LIKE :role')
            ->setParameter('role', '%administrateur%');

        // Paginate the results
        // Paginate the results
        $pagination = $paginator->paginate(
            $queryBuilder, // QueryBuilder instance
            $request->query->getInt('page', 1), // Current page number (default is 1)
            5 // Number of items per page (reduced to 5)
        );

        return $this->render('admin.html.twig', [
            'pagination' => $pagination,
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


    // account disabling
    #[Route('/admin/user/toggle-status/{id}', name: 'app_admin_toggle_user_status', methods: ['POST', 'GET'])]
    public function toggleUserStatus(int $id): Response
    {
        $user = $this->entityManager->getRepository(User::class)->find($id);

        if (!$user) {
            throw $this->createNotFoundException('User not found');
        }

        $newStatus = $user->isActive() ? User::STATUS_DISABLED : User::STATUS_ACTIVE;
        $user->setStatus($newStatus);

        $this->entityManager->flush();

        $statusText = $user->isActive() ? 'activated' : 'disabled';
        $this->addFlash('success', 'User account ' . $statusText . ' successfully.');

        return $this->redirectToRoute('app_admin_dashboard');
    }
}
