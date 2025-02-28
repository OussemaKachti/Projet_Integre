<?php

namespace App\Controller;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\HttpFoundation\Request;

class AdminController extends AbstractController {
    private $entityManager;
    
    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }
    
    #[Route('/admin', name: 'app_admin_dashboard')]
    public function dashboard(Request $request, PaginatorInterface $paginator): Response
    {
        $searchQuery = $request->query->get('q', '');
        $roleFilter = $request->query->get('role', '');
        $statusFilter = $request->query->get('status', '');
        $verificationFilter = $request->query->get('verification', '');
        
        // Create base query builder
        $queryBuilder = $this->entityManager->getRepository(User::class)
            ->createQueryBuilder('user')
            ->where('user.role NOT LIKE :role')
            ->setParameter('role', '%administrateur%');
        
        // Add search filter if a search query is provided
        if (!empty($searchQuery)) {
            $queryBuilder->andWhere('user.nom LIKE :search OR user.prenom LIKE :search OR user.email LIKE :search OR user.tel LIKE :search')
                ->setParameter('search', '%' . $searchQuery . '%');
        }
        
        // Add role filter if provided
        if (!empty($roleFilter)) {
            $queryBuilder->andWhere('user.role = :roleFilter')
                ->setParameter('roleFilter', $roleFilter);
        }
        
        // Add status filter if provided
        if ($statusFilter !== '') {
            if ($statusFilter === 'active') {
                $queryBuilder->andWhere('user.status = :statusFilter')
                    ->setParameter('statusFilter', User::STATUS_ACTIVE);
            } elseif ($statusFilter === 'disabled') {
                $queryBuilder->andWhere('user.status = :statusFilter')
                    ->setParameter('statusFilter', User::STATUS_DISABLED);
            }
        }
        
        // Add verification filter if provided
        if ($verificationFilter !== '') {
            $isVerified = $verificationFilter === 'verified';
            $queryBuilder->andWhere('user.isVerified = :verifiedFilter')
                ->setParameter('verifiedFilter', $isVerified);
        }
        
        // Paginate the results
        $pagination = $paginator->paginate(
            $queryBuilder,
            $request->query->getInt('page', 1),
            5 // Number of items per page
        );
        
        // Get available roles for filter dropdown (adjusted to handle string roles)
        $rolesQuery = $this->entityManager->getRepository(User::class)
            ->createQueryBuilder('u')
            ->select('DISTINCT u.role')
            ->where('u.role NOT LIKE :adminRole')
            ->setParameter('adminRole', '%administrateur%')
            ->getQuery();
        
        $roles = array_map(function($item) {
            return $item['role'];
        }, $rolesQuery->getScalarResult());
        
        // If it's an AJAX request for pagination only
        if ($request->isXmlHttpRequest() && $request->query->has('pagination_only')) {
            return $this->render('pagination_only.html.twig', [
                'pagination' => $pagination,
                'searchQuery' => $searchQuery,
                'roleFilter' => $roleFilter,
                'statusFilter' => $statusFilter,
                'verificationFilter' => $verificationFilter
            ]);
        }
        
        // If it's an AJAX request for table data only
        if ($request->isXmlHttpRequest()) {
            // Check if no results are found
            if ($pagination->count() === 0) {
                return new Response('<tr><td colspan="5" class="text-center">No users found matching your criteria.</td></tr>');
            }
            
            return $this->render('_table_body.html.twig', [
                'pagination' => $pagination,
            ]);
        }
        
        // Otherwise return the full page
        return $this->render('admin.html.twig', [
            'pagination' => $pagination,
            'searchQuery' => $searchQuery,
            'roleFilter' => $roleFilter,
            'statusFilter' => $statusFilter,
            'verificationFilter' => $verificationFilter,
            'availableRoles' => $roles
        ]);
    }
    // Other methods remain the same...


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