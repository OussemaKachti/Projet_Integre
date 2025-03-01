<?php

namespace App\Controller;

use App\Entity\Produit;
use App\Form\ProduitType;
use App\Repository\ProduitRepository;
use App\Repository\CommandeRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\String\Slugger\SluggerInterface;
use App\Entity\Club;
use App\Repository\ClubRepository;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Knp\Component\Pager\PaginatorInterface;

#[Route('/produit')]
class ProduitController extends AbstractController
{
    #[Route('/', name: 'app_produit_index', methods: ['GET'])]
public function index(
    ProduitRepository $produitRepository,
    ClubRepository $clubRepository,
    PaginatorInterface $paginator, // Utilisation correcte de la pagination
    Request $request
): Response {
    $query = $produitRepository->createQueryBuilder('p')->getQuery(); // Récupérer une requête valide

    $pagination = $paginator->paginate(
        $query, 
        $request->query->getInt('page', 1), // Récupération du numéro de page
        2 // Nombre d'éléments par page
    );

    return $this->render('produit/show.html.twig', [
        'pagination' => $pagination,
        'clubs' => $clubRepository->findAll(),
        'produits' => $pagination->getItems(), // Extraction des produits paginés
    ]);
}

#[Route('/presi', name: 'produit_index', methods: ['GET'])]
public function inde(
    Request $request,
    ProduitRepository $produitRepository,
    ClubRepository $clubRepository,
    PaginatorInterface $paginator
): Response
{
    // Get search query if present
    $keyword = $request->query->get('q', '');
    
    // Create base query
    if (!empty($keyword)) {
        $query = $produitRepository->searchByKeyword($keyword);
    } else {
        $query = $produitRepository->createQueryBuilder('p')->getQuery();
    }
    
    // Paginate results
    $pagination = $paginator->paginate(
        $query,
        $request->query->getInt('page', 1),
        2 // Number of items per page
    );
    
    return $this->render('produit/index.html.twig', [
        'produits' => $pagination->getItems(),
        'clubs' => $clubRepository->findAll(),
        'pagination' => $pagination,
        'keyword' => $keyword
    ]);

    }
    #[Route('/admin', name: 'produit_admin', methods: ['GET'])]
public function proadmin(
    Request $request,
    ProduitRepository $produitRepository,
    ClubRepository $clubRepository,
    PaginatorInterface $paginator
): Response
{
    // Get search query if present
    $keyword = $request->query->get('q', '');
    
    // Create base query
    if (!empty($keyword)) {
        $query = $produitRepository->searchByKeyword($keyword);
    } else {
        $query = $produitRepository->createQueryBuilder('p')->getQuery();
    }
    
    // Paginate results
    $pagination = $paginator->paginate(
        $query,
        $request->query->getInt('page', 1),
        2 // Number of items per page - adjust as needed
    );
    
    return $this->render('produit/produit.admin.html.twig', [
        'produits' => $pagination->getItems(),
        'clubs' => $clubRepository->findAll(),
        'pagination' => $pagination,
        'keyword' => $keyword
    ]);
}
    #[Route('/{id}/delete', name: 'produit.admin_delete', methods: ['POST'])]
public function deletee(Request $request, int $id, ProduitRepository $produitRepository, EntityManagerInterface $entityManager): Response
{
    $produit = $produitRepository->find($id);

    if (!$produit) {
        throw $this->createNotFoundException('Le produit avec ID '.$id.' n\'existe pas.');
    }

    if ($this->isCsrfTokenValid('delete'.$produit->getId(), $request->request->get('_token'))) {
        $entityManager->remove($produit);
        $entityManager->flush();
        $this->addFlash('success', 'Le produit a été supprimé avec succès.');
    } else {
        $this->addFlash('error', 'Jeton CSRF invalide.');
    
    }

    return $this->redirect($request->headers->get('referer') ?: $this->generateUrl('produit_admin'));
}

    #[Route('/new', name: 'app_produit_new', methods: ['GET', 'POST'])] //ajout de produits
    public function new(Request $request, EntityManagerInterface $entityManager, SluggerInterface $slugger): Response
    {
        //dump($request->getMethod()); // Should show 'POST' when the form is submitted
     //dump($request->request->all()); // Display the submitted form data
        $produit = new Produit();
        $form = $this->createForm(ProduitType::class, $produit);
        $form->handleRequest($request);
        // Debug: Vérifier si le formulaire est soumis et valide
        
        //dump($form->isSubmitted());
      //if ($form->isSubmitted()) {
         // dump($form->isValid());
         //}
       //die;
    
        if ($form->isSubmitted() && $form->isValid()) {
            // Vérifie si un club a été sélectionné dans le formulaire
          $club = $form->get('club')->getData();  // Récupérer l'objet Club

        if (!$club) {
        // Si aucun club n'est sélectionné, tu peux assigner un club par défaut
    // Assure-toi qu'un club par défaut existe en base de données
         $club = $entityManager->getRepository(Club::class)->find(1); // Par exemple, récupérer un club avec ID 1

    // Si le club par défaut n'existe pas, tu peux retourner une erreur ou gérer cette situation
        if (!$club) {
        // Ajouter une erreur ou un message d'alerte si aucun club n'est trouvé
        $this->addFlash('error', 'Aucun club trouvé. Veuillez ajouter un club.');
        return $this->redirectToRoute('app_produit_new');
    }
}

// Assigner le club au produit
        $produit->setClub($club);
            
    
            // Gestion de l'upload de l'image du produit
            $imageFile = $form->get('imgProd')->getData();
            if ($imageFile) {
                $originalFilename = pathinfo($imageFile->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = $slugger->slug($originalFilename);
                $newFilename = $safeFilename . '-' . uniqid() . '.' . $imageFile->guessExtension();
    
                try {
                    $imageFile->move(
                        $this->getParameter('uploads_directory'), // Assurez-vous de définir ce paramètre
                        $newFilename
                    );
                    $produit->setImgProd($newFilename);
                } catch (FileException $e) {
                    $this->addFlash('error', 'Une erreur est survenue lors du téléchargement de l\'image.');
                    return $this->redirectToRoute('app_produit_new');
                }
            }
            if (!$produit->getCreatedAt()) {
                $produit->setCreatedAt(new \DateTime());
            }
    
            // Sauvegarde en base de données
            $entityManager->persist($produit);
            $entityManager->flush();
    
            $this->addFlash('success', 'Produit créé avec succès !');
            return $this->redirectToRoute('app_produit_index');
        }
    
        return $this->render('produit/new.html.twig', [
            'form' => $form->createView(),
            'produit' => $produit,
        ]);
    }
    #[Route('/{id}', name: 'app_produit_show', requirements: ['id' => '\d+'],methods: ['GET'])]
public function show(ProduitRepository $produitRepository, ClubRepository $clubRepository, int $id): Response
{
    $produit = $produitRepository->find($id);
    
    if (!$produit) {
        throw $this->createNotFoundException('Produit non trouvé.');
    }

    return $this->render('produit/produit.html.twig', [
        'produit' => $produit,
        'clubs' => $clubRepository->findAll(), // Si vous avez besoin d'afficher tous les clubs
    ]);
}


#[Route('/{id}/edit', name: 'app_produit_edit', methods: ['GET', 'POST'])]
public function edit(Request $request, ?Produit $produit, EntityManagerInterface $entityManager): Response
{
    //dump($produit); die;
    if (!$produit) {
        throw $this->createNotFoundException('Produit non trouvé.');
    }
    
    $form = $this->createForm(ProduitType::class, $produit);
    $form->handleRequest($request);

    if ($form->isSubmitted() && $form->isValid()) {
        $entityManager->persist($produit);
        $entityManager->flush();

        return $this->redirectToRoute('app_produit_index', [], Response::HTTP_SEE_OTHER);
    }

    return $this->render('produit/edit.html.twig', [
        'produit' => $produit,
        'form'    => $form->createView(),
    ]);
}

    #[Route('/{id}/delete', name: 'app_produit_delete', methods: ['POST'])]
public function delete(Request $request, int $id, ProduitRepository $produitRepository, EntityManagerInterface $entityManager): Response
{
    $produit = $produitRepository->find($id);

    if (!$produit) {
        throw $this->createNotFoundException('Le produit avec ID '.$id.' n\'existe pas.');
    }

    if ($this->isCsrfTokenValid('delete'.$produit->getId(), $request->request->get('_token'))) {
        $entityManager->remove($produit);
        $entityManager->flush();
        $this->addFlash('success', 'Le produit a été supprimé avec succès.');
    } else {
        $this->addFlash('error', 'Jeton CSRF invalide.');
    
    }

    return $this->redirect($request->headers->get('referer') ?: $this->generateUrl('produit_index'));
}

      #[Route('/produits/{id}', name: 'app_produits_par_club', methods: ['GET'])]
    public function produitsParClub(int $id, EntityManagerInterface $entityManager): Response
    {
        // Récupérer le club par son ID
        $club = $entityManager->getRepository(Club::class)->find($id);
        
        if (!$club) {
            throw $this->createNotFoundException('Club non trouvé');
        }

        // Récupérer les produits associés au club
        $produits = $entityManager->getRepository(Produit::class)->findBy(['club' => $club]);
        var_dump($club, $produits);
exit;

        return $this->render('produit/show_id.html.twig', [
            'club' => $club,
            'produits' => $produits,
        ]);
    }


//cart
    #[Route('/cart', name: 'cart_index')]
    public function cart(SessionInterface $session, ProduitRepository $produitRepository): Response
{
    // Récupérer le panier depuis la session
    $cartData = $session->get('cart', []);
    $cartProduits = []; // Utilisation du nom demandé
    $total = 0; // Initialisation du total

    // Pour chaque produit dans le panier, récupérer ses informations
    foreach ($cartData as $productId => $quantity) {
        $produit = $produitRepository->find($productId); // Correction de la variable
        if ($produit) {
            $cartProduits[] = [
                'produit'  => $produit, // Changer la clé pour 'produit'
                'quantity' => $quantity,
            ];
            $total += $produit->getPrix() * $quantity;
        }
    }

    // Passer les données à la vue
    return $this->render('produit/cart.html.twig', compact('cartProduits', 'total')
    );
}

 
    #[Route('/add/{id}', name: 'cart_add', methods: ['GET'] )]
    public function add(int $id,Request $request, ProduitRepository $produitRepository, SessionInterface $session): Response
    {
        // Récupérer le produit à partir de l'ID
    $produit = $produitRepository->find($id);
    

    // Vérifier si le produit existe
    if (!$produit) {
        throw $this->createNotFoundException('Le produit n\'existe pas.');
    }

        // On récupère le panier existant
        $cart = $session->get('cart', []);

        // On ajoute le produit dans le panier s'il n'y est pas encore
        // Sinon on incrémente sa quantité
        if(empty($cart[$id])){
            $cart[$id] = 1;
        }else{
            $cart[$id]++;
        }

        $session->set('cart', $cart);
        
        //return $this->redirectToRoute('cart_index');
        return $this->redirectToRoute('cart_index');
    }
    

    #[Route('/remove/{id}', name: 'cart_remove')]
    public function remove(Request $request, int $id, SessionInterface $session,ProduitRepository $produitRepository): Response
    {  
         // Si vous avez besoin de récupérer le produit, vous pouvez le faire ainsi :
    $produit = $produitRepository->find($id);
    if (!$produit) {
        throw $this->createNotFoundException("Produit introuvable !");
    }
        // Récupérer le panier depuis la session (tableau associatif)
        $cart = $session->get('cart', []);
        
        // Vérifier si le produit avec cet ID existe dans le panier
        if (isset($cart[$id])) {
            
            
            unset($cart[$id]);
            dump($cart);
            // Mettre à jour la session avec le nouveau contenu du panier
            $session->set('cart', $cart);
        }
    
        return $this->redirect($request->headers->get('referer') ?: $this->generateUrl('cart_index'));
    }

    #[Route('/increase/{id}', name: 'cart_increase')]
public function increase(Produit $produit, SessionInterface $session): Response
{
    $cart = $session->get('cart', []);
    $id = $produit->getId();

    if (isset($cart[$id])) {
        $cart[$id]['quantity']++;
        // Recalcul du total pour ce produit
        $cart[$id]['total'] = $produit->getPrix() * $cart[$id]['quantity'];
    }

    $session->set('cart', $cart);

    return $this->redirectToRoute('cart_index');
}

#[Route('/decrease/{id}', name: 'cart_decrease')]
public function decrease(Produit $produit, SessionInterface $session): Response
{
    $cart = $session->get('cart', []);
    $id = $produit->getId();

    if (isset($cart[$id])) {
        if ($cart[$id]['quantity'] > 1) {
            $cart[$id]['quantity']--;
            // Recalcul du total pour ce produit
            $cart[$id]['total'] = $produit->getPrix() * $cart[$id]['quantity'];
        } else {
            unset($cart[$id]);
        }
    }
    var_dump($cart);

    

    $session->set('cart', $cart);

    return $this->redirectToRoute('cart_index');
}
#[Route('/cart/clear', name: 'cart_clear', methods: ['POST', 'GET'])]
public function clearCart(Request $request, SessionInterface $session): Response
{
    // Supprimer la clé 'cart' de la session (vidant ainsi le panier)
    $session->remove('cart');

    // Ou alternativement, vous pouvez simplement réinitialiser le panier à un tableau vide :
    // $session->set('cart', []);

    $this->addFlash('success', 'Votre panier a été vidé avec succès.');

    // Rediriger vers la page précédente ou vers la page du panier
    return $this->redirect(
        $request->headers->get('referer') ?: $this->generateUrl('cart_index')
    );
}


#[Route('/search', name: 'produit_search', methods: ['GET'])]
public function search(
    Request $request,
    ProduitRepository $produitRepository,
    ClubRepository $clubRepository,
    PaginatorInterface $paginator
): Response {
    $keyword = $request->query->get('q', ''); // Récupérer le mot-clé de recherche
    $query = $produitRepository->searchByKeyword($keyword);

    $pagination = $paginator->paginate(
        $query, 
        $request->query->getInt('page', 1), 
        2 // Nombre d'éléments par page
    );

    return $this->render('produit/show.html.twig', [
        'pagination' => $pagination,
        'keyword' => $keyword,
        'clubs' => $clubRepository->findAll(),
        'produits' => $pagination->getItems(),
    ]);
}

    



}
 
