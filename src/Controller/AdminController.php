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
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Bundle\SecurityBundle\Security;

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
#[Route('/change-password-admin', name: 'app_admin_password', methods: ['POST'])]
public function changePassword(
    Request $request,
    UserPasswordHasherInterface $passwordHasher,
    Security $security,
    EntityManagerInterface $entityManager,
    SessionInterface $session,
    MailerInterface $mailer,
    \Twig\Environment $twig // Inject Twig to render the template
): Response {
    // Set a flag in the session to keep the password tab active
    $session->set('password_tab_active', true);

    // Get the logged-in user
    $user = $security->getUser();

    if (!$user) {
        $this->addFlash('error', 'You must be logged in to change your password');
        return $this->redirectToRoute('app_login');
    }

    // Get form data
    $oldPassword = $request->request->get('oldPassword');
    $newPassword = $request->request->get('newPassword');
    $confirmPassword = $request->request->get('confirmPassword');

    // Verify old password
    if (!$passwordHasher->isPasswordValid($user, $oldPassword)) {
        $this->addFlash('error', 'Current password is incorrect');
        return $this->redirectToRoute('app_admin_profile');
    }

    // Check if new password is the same as the old password
    if ($passwordHasher->isPasswordValid($user, $newPassword)) {
        $this->addFlash('error', 'New password must be different from current password');
        return $this->redirectToRoute('app_admin_profile');
    }

    // Check if new passwords match
    if ($newPassword !== $confirmPassword) {
        $this->addFlash('error', 'New passwords do not match');
        return $this->redirectToRoute('app_admin_profile');
    }

    // Hash new password
    $hashedPassword = $passwordHasher->hashPassword($user, $newPassword);
    $user->setPassword($hashedPassword);

    // Save to database
    try {
        $entityManager->persist($user);
        $entityManager->flush();

        // Combine `prenom` and `nom` to create the full name
        $fullName = $user->getPrenom() . ' ' . $user->getNom();

        // Render the Twig template for the email
        $emailBody = $twig->render('security/password_change_notification.html.twig', [
            'fullName' => $fullName, // Pass the combined full name to the template
        ]);

        // Create and send the email
        $email = (new Email())
            ->from('no-reply@yourdomain.com') // Replace with your sender email
            ->to($user->getEmail()) // Use the user's email
            ->subject('Password Changed Successfully')
            ->html($emailBody); // Use ->html() instead of ->text()

        $mailer->send($email);

        $this->addFlash('success', 'Password updated successfully');
    } catch (\Exception $e) {
        $this->addFlash('error', 'An error occurred while updating your password');
    }

    return $this->redirectToRoute('app_admin_profile');
}
#[Route('/update-profile', name: 'admin_update_profile', methods: ['POST'])]
    public function updateProfile(Request $request, UserPasswordHasherInterface $passwordHasher, EntityManagerInterface $entityManager): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }
        
        // Get current password for verification
        $currentPassword = $request->request->get('current_password');
        
        // Verify the current password
        if (!$passwordHasher->isPasswordValid($user, $currentPassword)) {
            $this->addFlash('error', 'Current password is incorrect');
            return $this->redirectToRoute('app_admin_profile');
        }
        
        // Get form data
        $fullName = $request->request->get('full_name');
        $email = $request->request->get('email');
        $phone = $request->request->get('phone');
        
        // Validate full name
        $nameParts = explode(' ', trim($fullName));
        if (count($nameParts) < 2 || empty($nameParts[0]) || empty($nameParts[1])) {
            $this->addFlash('error', 'Please provide both first and last name');
            return $this->redirectToRoute('app_admin_profile');
        }
        
        // Set first and last name
        $firstName = $nameParts[0];
        $lastName = implode(' ', array_slice($nameParts, 1));
        
        // Validate email
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->addFlash('error', 'Email address is not valid');
            return $this->redirectToRoute('app_admin_profile');
        }
        
        // Check if email is already in use by another user
        if ($email !== $user->getEmail()) {
            $existingUser = $entityManager->getRepository(User::class)->findOneBy(['email' => $email]);
            if ($existingUser && $existingUser->getId() !== $user->getId()) {
                $this->addFlash('error', 'The email address is already in use');
                return $this->redirectToRoute('app_admin_profile');
            }
        }
        
        // Validate phone number (Tunisian format)
        $phonePattern = '/^((\+|00)216)?([2579][0-9]{7}|(3[012]|4[01]|8[0128])[0-9]{6}|42[16][0-9]{5})$/';
        if (!empty($phone) && !preg_match($phonePattern, $phone)) {
            $this->addFlash('error', 'Invalid phone number format');
            return $this->redirectToRoute('app_admin_profile');
        }
        
        // Check if any data has changed
        $hasChanges = false;
        
        if ($firstName !== $user->getPrenom()) {
            $user->setPrenom($firstName);
            $hasChanges = true;
        }
        
        if ($lastName !== $user->getNom()) {
            $user->setNom($lastName);
            $hasChanges = true;
        }
        
        if ($email !== $user->getEmail()) {
            $user->setEmail($email);
            $hasChanges = true;
        }
        
        if ($phone !== $user->getTel()) {
            $user->setTel($phone);
            $hasChanges = true;
        }
        
        // Only persist if there are changes
        if ($hasChanges) {
            $entityManager->persist($user);
            $entityManager->flush();
            $this->addFlash('success', 'Profile updated successfully');
        } else {
            $this->addFlash('info', 'No changes were made to your profile');
        }
        
        return $this->redirectToRoute('app_admin_profile');
    }
}