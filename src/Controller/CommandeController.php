<?php

namespace App\Controller;

use App\Entity\Commande;
use App\Entity\Produit;
use App\Form\CommandeType;
use App\Entity\OrderDetails;
use App\Entity\User;
use App\Repository\ProduitRepository;
use App\Repository\CommandeRepository;
use App\Repository\clubRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use App\Enum\StatutCommandeEnum;
use App\Services\OrderValidationService;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Workflow\Registry;


#[Route('/commande')]
class CommandeController extends AbstractController
{
    

    #[Route('/admin', name: 'admin_commandes')]
public function index(
    Request $request,
    EntityManagerInterface $entityManager,
    PaginatorInterface $paginator
): Response
{
    // Get search query if present
    $keyword = $request->query->get('q', '');
    
    // Create query for commands
    $commandeRepository = $entityManager->getRepository(Commande::class);
    
    if (!empty($keyword)) {
        // Search query - you need to implement this in your repository
        $query = $commandeRepository->searchByKeyword($keyword);
    } else {
        // Basic query to get all commands
        $query = $commandeRepository->createQueryBuilder('c')
            ->orderBy('c.dateComm', 'DESC')
            ->getQuery();
    }
    
    // Paginate the raw commands (we'll process them after pagination)
    $pagination = $paginator->paginate(
        $query,
        $request->query->getInt('page', 1),
        3 // Number of items per page
    );
    
    // Process the paginated commands
    $data = [];
    foreach ($pagination->getItems() as $commande) {
        $user = $commande->getUser();
        if (!$user) {
            $user = null;
        }
        
        $orderDetails = $commande->getOrderDetails();
        if ($orderDetails->isEmpty()) {
            $data[] = [
                'user' => $user ? $user : 'Utilisateur inconnu',
                'commande' => $commande,
                'produit' => null,
                'club' => null,
                'dateComm' => $commande->getDateComm(),
                'orderDetails' => $orderDetails,
            ];
        } else {
            foreach ($orderDetails as $orderDetail) {
                $produit = $orderDetail->getProduit();
                $club = $produit ? $produit->getClub() : null;
                
                $data[] = [
                    'user' => $user ? $user : 'Utilisateur inconnu',
                    'commande' => $commande,
                    'produit' => $produit,
                    'club' => $club,
                    'dateComm' => $commande->getDateComm(),
                ];
            }
        }
    }
    
    return $this->render('produit/commande_admin.html.twig', [
        'data' => $data,
        'pagination' => $pagination,
        'keyword' => $keyword
    ]);
}

#[Route('/president', name: 'presi_commandes')]
public function commande(EntityManagerInterface $entityManager,
    PaginatorInterface $paginator, Request $request): Response
{
    // Get search query if present
    $keyword = $request->query->get('q', '');

    // Create query for commands
    $commandeRepository = $entityManager->getRepository(Commande::class);

    if (!empty($keyword)) {
        // Search query - you need to implement this in your repository
        $query = $commandeRepository->searchByKeyword($keyword);
    } else {
        // Basic query to get all commands
        $query = $commandeRepository->createQueryBuilder('c')
            ->orderBy('c.dateComm', 'DESC')
            ->getQuery();
    }

    // Paginate the filtered or all commands (based on search)
    $pagination = $paginator->paginate(
        $query,
        $request->query->getInt('page', 1),
        3 // Number of items per page
    );

    // Process the paginated commands
    $data = [];
    foreach ($pagination->getItems() as $commande) {
        $user = $commande->getUser();
        if (!$user) {
            $user = null;
        }

        $orderDetails = $commande->getOrderDetails();
        if ($orderDetails->isEmpty()) {
            $data[] = [
                'user' => $user ? $user : 'Utilisateur inconnu',
                'commande' => $commande,
                'produit' => null,
                'club' => null,
                'dateComm' => $commande->getDateComm(),
                'orderDetails' => $orderDetails,
            ];
        } else {
            foreach ($orderDetails as $orderDetail) {
                $produit = $orderDetail->getProduit();
                $club = $produit ? $produit->getClub() : null;

                $data[] = [
                    'user' => $user ? $user : 'Utilisateur inconnu',
                    'commande' => $commande,
                    'produit' => $produit,
                    'club' => $club,
                    'dateComm' => $commande->getDateComm(),
                ];
            }
        }
    }

    return $this->render('produit/index2.html.twig', [
        'data' => $data,
        'pagination' => $pagination,
        'keyword' => $keyword
    ]);
}


#[Route('/commande/valider/{id}', name: 'commande_validate', methods: ['GET'])]
public function validateCommande(int $id, EntityManagerInterface $entityManager): Response
{
    // Récupérer la commande par ID
    $commande = $entityManager->getRepository(Commande::class)->find($id);

    if (!$commande) {
        $this->addFlash('danger', 'Commande non trouvée.');
        return $this->redirectToRoute('presi_commandes');
    }

    // Appliquer la transition "confirm_order"
    $workflow = $this->workflowRegistry->get($commande);

    if ($workflow->can($commande, 'confirm_order')) {
        $workflow->apply($commande, 'confirm_order');
        // Sauvegarder la commande avec son nouveau statut
        $entityManager->flush();

        // Message de succès
        $this->addFlash('success', 'Commande confirmée avec succès!');
    } else {
        // Message d'erreur si la transition ne peut pas être effectuée
        $this->addFlash('danger', 'Impossible de confirmer la commande.');
    }

    // Rediriger vers la liste des commandes ou une page de confirmation
    return $this->redirectToRoute('presi_commandes');
}
    

    #[Route('/admin/supprimer/{id}', name: 'admin_commande_supprimer', methods: ['POST', 'GET'])]
    public function supprimerCommande(int $id, EntityManagerInterface $entityManager): Response
    {
        // Récupérer la commande par ID
        $commande = $entityManager->getRepository(Commande::class)->find($id);

        if (!$commande) {
            $this->addFlash('danger', 'Commande non trouvée.');
            return $this->redirectToRoute('admin_commandes');
        }

        // Supprimer la commande
        $entityManager->remove($commande);
        $entityManager->flush();

        $this->addFlash('success', 'Commande supprimée avec succès.');

        return $this->redirectToRoute('admin_commandes');
    }

    #[Route('/presi/supprimer/{id}', name: 'presi_commande_supprimer', methods: ['POST', 'GET'])]
    public function supprimerCommandepresi(int $id, EntityManagerInterface $entityManager): Response
    {
        // Récupérer la commande par ID
        $commande = $entityManager->getRepository(Commande::class)->find($id);

        if (!$commande) {
            $this->addFlash('danger', 'Commande non trouvée.');
            return $this->redirectToRoute('admin_commandes');
        }

        // Appeler la méthode cancelOrder à partir du service
        try {
            $this->commandeService->cancelOrder($commande);
            $this->addFlash('success', 'Commande annulée avec succès.');
        } catch (\Exception $e) {
            $this->addFlash('danger', $e->getMessage());
            return $this->redirectToRoute('presi_commandes');
        }

        // Supprimer la commande
        $entityManager->remove($commande);
        $entityManager->flush();

        $this->addFlash('success', 'Commande supprimée avec succès.');

        return $this->redirectToRoute('presi_commandes');
    }

    


    #[Route('/{id}', name: 'app_commande_show', methods: ['GET'])]
    public function show(Commande $commande): Response
    {
        return $this->render('commande/show.html.twig', [
            'commande' => $commande,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_commande_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Commande $commande, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(CommandeType::class, $commande);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_commande_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('commande/edit.html.twig', [
            'commande' => $commande,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_commande_delete', methods: ['POST'])]
    public function delete(Request $request, Commande $commande, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$commande->getId(), $request->request->get('_token'))) {
            $entityManager->remove($commande);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_commande_index', [], Response::HTTP_SEE_OTHER);
    }
    
    #[Route('/commande/creer', name: 'order_create')]
    
    public function createOrder(
        SessionInterface $session, 
        ProduitRepository $produitRepository, 
        EntityManagerInterface $entityManager
    ): Response {
        // Récupérer l'utilisateur connecté
    $user = $this->getUser();

    if (!$user) {
        // Si l'utilisateur n'est pas connecté, le rediriger vers la page de connexion
        return $this->redirectToRoute('app_login');
    }
    
        // Récupérer le panier depuis la session
        $cart = $session->get('cart', []);
        
    
        // Créer une nouvelle commande
        $commande = new Commande();
        $commande->setUser($user);
        $commande->setDateComm(new \DateTime());
        
        // Définir le statut de la commande
        $statutCommande = StatutCommandeEnum::EN_COURS; 
        $commande->setStatut($statutCommande);
    
        $orderTotal = 0;
    
        // Pour chaque produit dans le panier, créer une ligne de commande (OrderDetails)
        foreach ($cart as $productId => $data) {
            $produit = $produitRepository->find($productId);
            if (!$produit) {
                continue;
            }
            
            // Gérer le cas où $data est un entier (ancienne structure) ou un tableau
            $quantity = is_array($data) ? ($data['quantity'] ?? 1) : $data;
            
            // Créer une nouvelle ligne de commande (OrderDetails)
            $orderDetails = new OrderDetails();
            $orderDetails->setproduit($produit);
            $orderDetails->setquantity($quantity);
            $orderDetails->setprice($produit->getPrix());
            $orderDetails->calculateTotal(); // Calcul automatique du total de la ligne
            
            // Associer la ligne de commande à la commande
            $commande->addOrderDetails($orderDetails);
            
            // Additionner le total de la ligne au total de la commande
            $orderTotal += $orderDetails->getTotal();
        }
        
        // Si vous avez une méthode setTotal sur la commande, utilisez-la sinon stockez le total comme vous le souhaitez
        $commande->setTotal($orderTotal);
    
        // Persister la commande et ses lignes dans la base de données
        $entityManager->persist($commande);
        $entityManager->flush();
    
        // Optionnel : vider le panier de la session après validation
        $session->remove('cart');
    
        $this->addFlash('success', 'Commande enregistrée avec succès.');
    
        // Rediriger vers une page de confirmation ou vers la liste des commandes
        return $this->redirectToRoute('order_success', ['id' => $commande->getId()]);
    }
    
#[Route('/order/{id}', name: 'order_success', methods: ['GET'])]
public function success(Commande $commande): Response
{
    // Vérifier si l'utilisateur est connecté
    if (!$this->getUser()) {
        // Stocker la route actuelle pour redirection après connexion
        $session->set('_security.target_path', $this->generateUrl('order_success', ['id' => $commande->getId()]));

        // Rediriger vers la page de connexion
        return $this->redirectToRoute('app_user_signup');
    }
    return $this->render('commande/chekout.html.twig', [
        'commande' => $commande,
    ]);
}

#[Route('/stats/top-produits', name: 'top_produits')]
    public function topProduits(EntityManagerInterface $entityManager): JsonResponse
    {
        // Récupérer les produits les plus vendus
        $query = $entityManager->createQuery(
            'SELECT p.nomProd, SUM(o.quantity) as totalVentes
             FROM App\Entity\Produit p
             JOIN p.orderdetails o
             GROUP BY p.id
             ORDER BY totalVentes DESC'
        );

        $topProduits = $query->getResult();
        

        return $this->json($topProduits);
    }
    
    

       
    

}