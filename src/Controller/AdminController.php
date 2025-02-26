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


// account disabling
// src/Controller/AdminController.php

// Add this method to your existing AdminController
#[Route('/admin/user/toggle-status/{id}', name: 'app_admin_toggle_user_status', methods: ['POST', 'GET'])]
public function toggleUserStatus(int $id): Response 
{
    $user = $this->entityManager->getRepository(User::class)->find($id);
    
    if (!$user) {
        throw $this->createNotFoundException('User not found');
    }
    
    // Prevent admins from disabling themselves
    if ($this->getUser() && $user->getId() === $this->getUser()->getId()) {
        $this->addFlash('error', 'You cannot disable your own account.');
        return $this->redirectToRoute('app_admin_dashboard');
    }
    
    $newStatus = $user->isActive() ? User::STATUS_DISABLED : User::STATUS_ACTIVE;
    $user->setStatus($newStatus);
    
    $this->entityManager->flush();
    
    $statusText = $user->isActive() ? 'activated' : 'disabled';
    $this->addFlash('success', 'User account ' . $statusText . ' successfully.');
    
    return $this->redirectToRoute('app_admin_dashboard');
}
}