<?php

namespace App\Controller;

use App\Entity\Produit;
use App\Form\ProduitType;
use App\Repository\ProduitRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\String\Slugger\SluggerInterface;
use App\Entity\Club;
use App\Repository\ClubRepository;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
#[Route('/produit')]
class ProduitController extends AbstractController
{
    #[Route('/', name: 'app_produit_index', methods: ['GET'])]
    public function index(ProduitRepository $produitRepository,ClubRepository $clubRepository): Response
    {
        return $this->render('produit/show.html.twig', [
            'produits' => $produitRepository->findAll(),
            'clubs' => $clubRepository->findAll(), // Fetch all clubs
        ]);
    }
    #[Route('/admin', name: 'produit_index', methods: ['GET'])]
    public function inde(ProduitRepository $produitRepository): Response
    {
        return $this->render('produit/produit.html.twig', [
            'produits' => $produitRepository->findAll(),
        ]);
    }
    #[Route('/cart', name: 'cart_commande', methods: ['GET'])]
    public function cart(ProduitRepository $produitRepository,ClubRepository $clubRepository): Response
    {
        return $this->render('produit/commande.html.twig', [
            'produits' => $produitRepository->findAll(),
            'clubs' => $clubRepository->findAll(), // Fetch all clubs
        ]);
    }
    public function configurefields(string $pageName):iterable{
        return[
      datetimefield::new('createdAt'->hideonForm(),)];
    }

    #[Route('/new', name: 'app_produit_new', methods: ['GET', 'POST'])]
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
    #[Route('/{id}', name: 'app_produit_show', methods: ['GET'])]
    public function show(Produit $produit): Response
    {
        return $this->render('produit/show.html.twig', [
            'produit' => $produit,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_produit_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Produit $produit, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(ProduitType::class, $produit);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            
            $entityManager->persist($produit);
            $entityManager->flush();

            return $this->redirectToRoute('app_produit_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('produit/edit.html.twig', [
            'produit' => $produit,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_produit_delete', methods: ['POST'])]
    public function delete(Request $request, Produit $produit, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$produit->getId(), $request->request->get('_token'))) {
            $entityManager->remove($produit);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_produit_index', [], Response::HTTP_SEE_OTHER);
    }
    #[Route('/cart', name: 'cart_index')]
    public function cartt(SessionInterface $session): Response
    {
        $cart = $session->get('cart', []);

        return $this->render('produit/commande.html.twig', [
            'cart' => $cart,
        ]);
    }

    #[Route('/add/{id}', name: 'cart_add')]
    public function add(Produit $produit, SessionInterface $session): Response
    {
        $cart = $session->get('cart', []);
        $id = $produit->getId();

        if (!isset($cart[$id])) {
            $cart[$id] = [
                'produit' => $produit,
                'quantity' => 1,
            ];
        } else {
            $cart[$id]['quantity']++;
        }

        $session->set('cart', $cart);

        return $this->redirectToRoute('cart_index');
    }

    #[Route('/remove/{id}', name: 'cart_remove')]
    public function remove(Produit $produit, SessionInterface $session): Response
    {
        $cart = $session->get('cart', []);
        $id = $produit->getId();

        if (isset($cart[$id])) {
            unset($cart[$id]);
        }

        $session->set('cart', $cart);

        return $this->redirectToRoute('cart_index');
    }

    #[Route('/increase/{id}', name: 'cart_increase')]
    public function increase(Produit $produit, SessionInterface $session): Response
    {
        $cart = $session->get('cart', []);
        $id = $produit->getId();

        if (isset($cart[$id])) {
            $cart[$id]['quantity']++;
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
            } else {
                unset($cart[$id]);
            }
        }

        $session->set('cart', $cart);

        return $this->redirectToRoute('cart_index');
    }

}
 
