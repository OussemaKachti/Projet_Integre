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

#[Route('/commande')]
class CommandeController extends AbstractController
{
    

    #[Route('/admin', name: 'admin_commandes')]
    public function index(EntityManagerInterface $entityManager): Response
    { 
        // Récupérer toutes les commandes
        $commandes = $entityManager->getRepository(Commande::class)->findAll();
    
        $data = [];
        foreach ($commandes as $commande) {
            $user = $commande->getUser();
            if (!$user) {
                $user = null; // Correction ici pour éviter l'erreur
            }
    
            $orderDetails = $commande->getOrderDetails(); // Collection d'OrderDetails
            if ($orderDetails->isEmpty()) {
                $data[] = [
                    'user' => $user ? $user : 'Utilisateur inconnu',
                    'commande' => $commande,
                    'produit' => null, // Pas de produit
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
        ]);
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
        $this->addFlash('error', 'Vous devez être connecté pour passer une commande.');
        return $this->redirectToRoute('cart_index'); // Rediriger vers la page de connexion
    }
        // Récupérer le panier depuis la session
        $cart = $session->get('cart', []);
        
        if (empty($cart)) {
            $this->addFlash('error', 'Votre panier est vide.');
            return $this->redirectToRoute('cart_index');
        }

        // Créer une nouvelle commande
        $commande = new Commande();
        $commande->setUser($user);
        $commande->setDateComm(new \DateTime());
        // Si vous avez une gestion d'utilisateur, par exemple :
        // $order->setUser($this->getUser());

        $orderTotal = 0;
        $statutCommande = StatutCommandeEnum::EN_COURS; 
        $commande->setStatut($statutCommande); // Assure-toi que cette méthode existe dans ta classe Commande


        // Pour chaque produit dans le panier, créer une ligne de commande (OrderDetails)
        foreach ($cart as $productId => $data) {
            $produit = $produitRepository->find($productId);
            if (!$produit) {
                continue;
            }
            
            // Gérer le cas où $data est un entier (ancienne structure) ou un tableau
            if (is_array($data)) {
                $quantity = $data['quantity'] ?? 1;
            } else {
                $quantity = $data;
            }
            
            // Créer une nouvelle ligne de commande (OrderDetails)
            $orderDetails = new OrderDetails();
            $orderDetails->setproduit($produit);
            $orderDetails->setquantity($quantity);
            $orderDetails->setprice($produit->getPrix());
            $orderDetails->calculateTotal(); //  Calcul automatique du total de la ligne

        // Associer la ligne de commande à la commande
        $commande->addOrderDetails($orderDetails);

        $orderTotal += $orderDetails->getTotal();

        // Définir le total de la commande
        $orderDetails->setTotal($orderTotal);

        // Persister la commande et ses lignes dans la base de données
        $entityManager->persist($commande);
        $entityManager->flush();

        // Optionnel : vider le panier de la session après validation
        $session->remove('cart');

        $this->addFlash('success', 'Commande enregistrée avec succès.');

        // Rediriger vers une page de confirmation ou vers la liste des commandes
        return $this->redirectToRoute('order_success', ['id' => $commande->getId()]);
        }
}
#[Route('/order/{id}', name: 'order_success', methods: ['GET'])]
public function success(Commande $commande): Response
{
    return $this->render('commande/chekout.html.twig', [
        'commande' => $commande,
    ]);
}

}