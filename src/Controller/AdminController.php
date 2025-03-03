<?php

namespace App\Controller;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
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
 


#[Route('/admin/profile', name: 'app_admin_profile')]
public function profile(Request $request): Response
{
    // Get the current admin user
    $user = $this->getUser();
    
    // Make sure it's an authenticated user
    if (!$user) {
        return $this->redirectToRoute('app_login');
    }
    
    // Check if user has admin role
    if (!$this->isGranted('ROLE_ADMINISTRATEUR')) {
        return $this->redirectToRoute('access_denied');
    }
    
    // Handle cleanup parameter for password tab session
    if ($request->query->get('cleanup')) {
        $request->getSession()->remove('password_tab_active');
    }
    
    // Render the admin profile template
    return $this->render('admin_profile.html.twig', [
        'user' => $user,
    ]);
}
    
    #[Route('/admin', name: 'app_admin_dashboard')]
    public function dashboard(Request $request, PaginatorInterface $paginator): Response
    {
        try {
            // Basic search and filter parameters
            $searchQuery = $request->query->get('q', '');
            $roleFilter = $request->query->get('role', '');
            $statusFilter = $request->query->get('status', '');
            $verificationFilter = $request->query->get('verification', '');
            
            // Advanced search parameters
            $searchType = $request->query->get('searchType', 'contains');
            $searchField = $request->query->get('searchField', 'all');
            
            // Date range parameters
            $dateRange = $request->query->get('dateRange', '');
            $dateFrom = $request->query->get('dateFrom', '');
            $dateTo = $request->query->get('dateTo', '');
            
            // Activity filter
            $activityFilter = $request->query->get('activity', '');
            
            // Check if fields exist in User entity
            $fieldExists = [
                'createdAt' => property_exists(User::class, 'createdAt'),
                'lastLoginAt' => property_exists(User::class, 'lastLoginAt')
            ];
            
            // Create base query builder
            $queryBuilder = $this->entityManager->getRepository(User::class)
                ->createQueryBuilder('user')
                ->where('user.role NOT LIKE :role')
                ->setParameter('role', '%administrateur%');
            
            // Apply search filter with optimized query building
            if (!empty($searchQuery)) {
                // Apply minimum character check for contains searches
                if ($searchType === 'contains' && strlen($searchQuery) < 3) {
                    throw new \Exception('Please enter at least 3 characters for text search');
                }
                
                $this->applySearchFilter($queryBuilder, $searchQuery, $searchType, $searchField);
            }
            
            // Add role filter - support multiple roles
            if (!empty($roleFilter)) {
                $roles = explode(',', $roleFilter);
                if (count($roles) > 1) {
                    $queryBuilder->andWhere('user.role IN (:roles)')
                        ->setParameter('roles', $roles);
                } else {
                    $queryBuilder->andWhere('user.role = :roleFilter')
                        ->setParameter('roleFilter', $roleFilter);
                }
            }
            
            // Add status filter
            if ($statusFilter !== '') {
                if ($statusFilter === 'active') {
                    $queryBuilder->andWhere('user.status = :statusFilter')
                        ->setParameter('statusFilter', User::STATUS_ACTIVE);
                } elseif ($statusFilter === 'disabled') {
                    $queryBuilder->andWhere('user.status = :statusFilter')
                        ->setParameter('statusFilter', User::STATUS_DISABLED);
                }
            }
            
            // Add verification filter
            if ($verificationFilter !== '') {
                $isVerified = $verificationFilter === 'verified';
                $queryBuilder->andWhere('user.isVerified = :verifiedFilter')
                    ->setParameter('verifiedFilter', $isVerified);
            }
            
            // Add date range filter - only if the createdAt field exists
            if (!empty($dateRange) && $fieldExists['createdAt']) {
                switch ($dateRange) {
                    case 'today':
                        $queryBuilder->andWhere('DATE(user.createdAt) = CURRENT_DATE()');
                        break;
                    case 'week':
                        $queryBuilder->andWhere('user.createdAt >= :weekStart')
                            ->setParameter('weekStart', new \DateTime('-7 days'));
                        break;
                    case 'month':
                        $queryBuilder->andWhere('user.createdAt >= :monthStart')
                            ->setParameter('monthStart', new \DateTime('-30 days'));
                        break;
                    case 'custom':
                        if (!empty($dateFrom)) {
                            $queryBuilder->andWhere('user.createdAt >= :dateFrom')
                                ->setParameter('dateFrom', new \DateTime($dateFrom));
                        }
                        if (!empty($dateTo)) {
                            $queryBuilder->andWhere('user.createdAt <= :dateTo')
                                ->setParameter('dateTo', new \DateTime($dateTo . ' 23:59:59'));
                        }
                        break;
                }
            }
            
            // Add activity filter - only if the lastLoginAt field exists
            if (!empty($activityFilter) && $fieldExists['lastLoginAt']) {
                switch ($activityFilter) {
                    case 'today':
                        $queryBuilder->andWhere('DATE(user.lastLoginAt) = CURRENT_DATE()');
                        break;
                    case 'week':
                        $queryBuilder->andWhere('user.lastLoginAt >= :weekStart')
                            ->setParameter('weekStart', new \DateTime('-7 days'));
                        break;
                    case 'month':
                        $queryBuilder->andWhere('user.lastLoginAt >= :monthStart')
                            ->setParameter('monthStart', new \DateTime('-30 days'));
                        break;
                    case 'inactive':
                        $queryBuilder->andWhere('user.lastLoginAt <= :inactiveDate OR user.lastLoginAt IS NULL')
                            ->setParameter('inactiveDate', new \DateTime('-30 days'));
                        break;
                }
            }
            
            // Add ordering - default to ID if createdAt doesn't exist
            if ($fieldExists['createdAt']) {
                $queryBuilder->orderBy('user.createdAt', 'DESC');
            } else {
                $queryBuilder->orderBy('user.id', 'DESC');
            }
            
            // Debug the query in dev environment
            if ($_ENV['APP_ENV'] === 'dev') {
                // Uncomment to debug SQL query
                // error_log($queryBuilder->getQuery()->getSQL());
            }
            
            // Paginate the results with explicit error handling
            // Ensure page is at least 1 to avoid "Invalid page number" errors
            $page = max(1, $request->query->getInt('page', 1));
            
            try {
                $pagination = $paginator->paginate(
                    $queryBuilder,
                    $page,
                    3// Number of items per page
                );
            } catch (\Exception $paginationError) {
                // Handle pagination errors gracefully
                if ($request->isXmlHttpRequest()) {
                    return new Response(
                        '<tr><td colspan="7" class="text-center text-danger">Pagination error: ' . $paginationError->getMessage() . '</td></tr>'
                    );
                }
                
                $this->addFlash('error', 'Pagination error: ' . $paginationError->getMessage());
                
                // Create an empty pagination result to avoid further errors
                $emptyQuery = $this->entityManager->getRepository(User::class)
                    ->createQueryBuilder('u')
                    ->where('u.id = 0') // This ensures no results
                    ->getQuery();
                
                $pagination = $paginator->paginate(
                    $emptyQuery,
                    1,
                    10
                );
            }
            
            // Get available roles for filter dropdown
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
                    'verificationFilter' => $verificationFilter,
                    'searchType' => $searchType,
                    'searchField' => $searchField,
                    'dateRange' => $dateRange,
                    'dateFrom' => $dateFrom,
                    'dateTo' => $dateTo,
                    'activityFilter' => $activityFilter
                ]);
            }
            
            // If it's an AJAX request for table data only
            if ($request->isXmlHttpRequest()) {
                // Check if no results are found
                if ($pagination->count() === 0) {
                    return new Response('<tr><td colspan="7" class="text-center">No users found matching your criteria.</td></tr>');
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
                'searchType' => $searchType,
                'searchField' => $searchField,
                'dateRange' => $dateRange,
                'dateFrom' => $dateFrom,
                'dateTo' => $dateTo,
                'activityFilter' => $activityFilter,
                'availableRoles' => $roles,
                'field_exists' => $fieldExists
            ]);
        } catch (\Exception $e) {
            // Handle errors gracefully
            if ($request->isXmlHttpRequest()) {
                return new Response(
                    '<tr><td colspan="7" class="text-center text-warning">' . $e->getMessage() . '</td></tr>'
                );
            }
            
            $this->addFlash('warning', $e->getMessage());
            return $this->redirectToRoute('app_admin_dashboard');
        }
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
    
    #[Route('/admin/user/export', name: 'app_admin_export_users')]
    public function exportUsers(Request $request): Response
    {
        try {
            // Get filtered query
            $queryBuilder = $this->applyFilters($request);
            $users = $queryBuilder->getQuery()->getResult();
            
            if (empty($users)) {
                $this->addFlash('warning', 'No users match the current filters for export.');
                return $this->redirectToRoute('app_admin_dashboard');
            }
            
            // Create CSV content
            $csv = "ID,Name,Email,Phone,Role,Status,Verification\n";
            
            foreach ($users as $user) {
                $csv .= sprintf(
                    "%s,%s %s,%s,%s,%s,%s,%s\n",
                    $user->getId(),
                    str_replace(',', ' ', $user->getNom()), // Escape commas
                    str_replace(',', ' ', $user->getPrenom()),
                    $user->getEmail(),
                    $user->getTel(),
                    $user->getRole()->value, // Assuming it's an enum
                    $user->isActive() ? 'Active' : 'Disabled',
                    $user->isVerified() ? 'Yes' : 'No'
                );
            }
            
            $response = new Response($csv);
            $response->headers->set('Content-Type', 'text/csv');
            $response->headers->set('Content-Disposition', 'attachment; filename="users_export_' . date('Y-m-d') . '.csv"');
            
            return $response;
        } catch (\Exception $e) {
            $this->addFlash('error', 'Error exporting users: ' . $e->getMessage());
            return $this->redirectToRoute('app_admin_dashboard');
        }
    }
    
    /**
     * Apply search filter with optimized query building
     */
    private function applySearchFilter(QueryBuilder $queryBuilder, string $searchQuery, string $searchType = 'contains', string $searchField = 'all'): void
    {
        if (empty($searchQuery)) {
            return;
        }
        
        // Determine which fields to search
        $searchFields = [];
        if ($searchField === 'all' || $searchField === 'name') {
            $searchFields[] = ['field' => 'user.nom', 'param' => 'searchLastName'];
            $searchFields[] = ['field' => 'user.prenom', 'param' => 'searchFirstName'];
        }
        if ($searchField === 'all' || $searchField === 'email') {
            $searchFields[] = ['field' => 'user.email', 'param' => 'searchEmail'];
        }
        if ($searchField === 'all' || $searchField === 'phone') {
            $searchFields[] = ['field' => 'user.tel', 'param' => 'searchPhone'];
        }
        
        // Create individual conditions for better index usage
        $conditions = [];
        foreach ($searchFields as $fieldData) {
            $field = $fieldData['field'];
            $param = $fieldData['param'];
            
            switch ($searchType) {
                case 'exact':
                    $conditions[] = "$field = :$param";
                    $queryBuilder->setParameter($param, $searchQuery);
                    break;
                    
                case 'starts':
                    $conditions[] = "$field LIKE :$param";
                    $queryBuilder->setParameter($param, $searchQuery . '%');
                    break;
                    
                case 'ends':
                    $conditions[] = "$field LIKE :$param";
                    $queryBuilder->setParameter($param, '%' . $searchQuery);
                    break;
                    
                case 'contains':
                default:
                    $conditions[] = "$field LIKE :$param";
                    $queryBuilder->setParameter($param, '%' . $searchQuery . '%');
                    break;
            }
        }
        
        // Add the OR condition for all search fields
        if (!empty($conditions)) {
            $queryBuilder->andWhere('(' . implode(' OR ', $conditions) . ')');
        }
    }
    
    /**
     * Apply all filters to create a QueryBuilder for various operations
     */
    private function applyFilters(Request $request): QueryBuilder
    {
        // Get filter parameters
        $searchQuery = $request->query->get('q', '');
        $roleFilter = $request->query->get('role', '');
        $statusFilter = $request->query->get('status', '');
        $verificationFilter = $request->query->get('verification', '');
        $searchType = $request->query->get('searchType', 'contains');
        $searchField = $request->query->get('searchField', 'all');
        $dateRange = $request->query->get('dateRange', '');
        $dateFrom = $request->query->get('dateFrom', '');
        $dateTo = $request->query->get('dateTo', '');
        $activityFilter = $request->query->get('activity', '');
        
        // Check if fields exist in User entity
        $fieldExists = [
            'createdAt' => property_exists(User::class, 'createdAt'),
            'lastLoginAt' => property_exists(User::class, 'lastLoginAt')
        ];
        
        // Create base query builder
        $queryBuilder = $this->entityManager->getRepository(User::class)
            ->createQueryBuilder('user')
            ->where('user.role NOT LIKE :role')
            ->setParameter('role', '%administrateur%');
        
        // Apply search filter
        if (!empty($searchQuery)) {
            $this->applySearchFilter($queryBuilder, $searchQuery, $searchType, $searchField);
        }
        
        // Add role filter
        if (!empty($roleFilter)) {
            $roles = explode(',', $roleFilter);
            if (count($roles) > 1) {
                $queryBuilder->andWhere('user.role IN (:roles)')
                    ->setParameter('roles', $roles);
            } else {
                $queryBuilder->andWhere('user.role = :roleFilter')
                    ->setParameter('roleFilter', $roleFilter);
            }
        }
        
        // Add status filter
        if ($statusFilter !== '') {
            if ($statusFilter === 'active') {
                $queryBuilder->andWhere('user.status = :statusFilter')
                    ->setParameter('statusFilter', User::STATUS_ACTIVE);
            } elseif ($statusFilter === 'disabled') {
                $queryBuilder->andWhere('user.status = :statusFilter')
                    ->setParameter('statusFilter', User::STATUS_DISABLED);
            }
        }
        
        // Add verification filter
        if ($verificationFilter !== '') {
            $isVerified = $verificationFilter === 'verified';
            $queryBuilder->andWhere('user.isVerified = :verifiedFilter')
                ->setParameter('verifiedFilter', $isVerified);
        }
        
        // Add date range filter - only if the createdAt field exists
        if (!empty($dateRange) && $fieldExists['createdAt']) {
            switch ($dateRange) {
                case 'today':
                    $queryBuilder->andWhere('DATE(user.createdAt) = CURRENT_DATE()');
                    break;
                case 'week':
                    $queryBuilder->andWhere('user.createdAt >= :weekStart')
                        ->setParameter('weekStart', new \DateTime('-7 days'));
                    break;
                case 'month':
                    $queryBuilder->andWhere('user.createdAt >= :monthStart')
                        ->setParameter('monthStart', new \DateTime('-30 days'));
                    break;
                case 'custom':
                    if (!empty($dateFrom)) {
                        $queryBuilder->andWhere('user.createdAt >= :dateFrom')
                            ->setParameter('dateFrom', new \DateTime($dateFrom));
                    }
                    if (!empty($dateTo)) {
                        $queryBuilder->andWhere('user.createdAt <= :dateTo')
                            ->setParameter('dateTo', new \DateTime($dateTo . ' 23:59:59'));
                    }
                    break;
            }
        }
        
        // Add activity filter - only if the lastLoginAt field exists
        if (!empty($activityFilter) && $fieldExists['lastLoginAt']) {
            switch ($activityFilter) {
                case 'today':
                    $queryBuilder->andWhere('DATE(user.lastLoginAt) = CURRENT_DATE()');
                    break;
                case 'week':
                    $queryBuilder->andWhere('user.lastLoginAt >= :weekStart')
                        ->setParameter('weekStart', new \DateTime('-7 days'));
                    break;
                case 'month':
                    $queryBuilder->andWhere('user.lastLoginAt >= :monthStart')
                        ->setParameter('monthStart', new \DateTime('-30 days'));
                    break;
                case 'inactive':
                    $queryBuilder->andWhere('user.lastLoginAt <= :inactiveDate OR user.lastLoginAt IS NULL')
                        ->setParameter('inactiveDate', new \DateTime('-30 days'));
                    break;
            }
        }
        
        // Add ordering - default to ID if createdAt doesn't exist
        if ($fieldExists['createdAt']) {
            $queryBuilder->orderBy('user.createdAt', 'DESC');
        } else {
            $queryBuilder->orderBy('user.id', 'DESC');
        }
        
        return $queryBuilder;
    }
    
//     /**
//      * Log query execution time for debugging
//      * @return array The query results
//      */
//     private function logQueryTime(QueryBuilder $queryBuilder, string $label = 'Query'): array
//     {
//         if ($_ENV['APP_ENV'] === 'dev') {
//             $start = microtime(true);
//             $result = $queryBuilder->getQuery()->getResult();
//             $executionTime = microtime(true) - $start;
            
//             // Log the execution time
//             error_log(sprintf("%s execution time: %.2f ms", $label, $executionTime * 1000));
            
//             return $result;
//         }
        
//         return $queryBuilder->getQuery()->getResult();
//     }
}