<?php

namespace App\Controller;

use App\Entity\Club;
use App\Form\ClubType;
use App\Repository\ClubRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Enum\StatutClubEnum;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\String\Slugger\SluggerInterface;  
use Knp\Component\Pager\PaginatorInterface;


#[Route('/club')]
class ClubController extends AbstractController
{
    public function __construct(
        private ClubRepository $clubRepository
    ) {
    }

    #[Route('/carte', name: 'app_club_index', methods: ['GET'])]
    public function index(
        Request $request, 
        EntityManagerInterface $entityManager, 
        PaginatorInterface $paginator
    ): Response {
        // Get search query if present
        $keyword = $request->query->get('query', ''); // Match the input field name


        // Create base query builder
        $queryBuilder = $entityManager->getRepository(Club::class)->createQueryBuilder('c');

        // Apply search filter if keyword is not empty
        if (!empty($keyword)) {
            $queryBuilder
                ->andWhere('c.nomC LIKE :keyword')
                ->setParameter('keyword', '%' . $keyword . '%');
        }

        // Order by club name
        $queryBuilder->orderBy('c.nomC', 'ASC');

        // Create query
        $query = $queryBuilder->getQuery();

        // Paginate results
        $pagination = $paginator->paginate(
            $query,
            $request->query->getInt('page', 1),
            3 // Number of items per page
        );

        return $this->render('club/index.html.twig', [
            'pagination' => $pagination,
            'keyword' => $keyword,
        ]);
    }


    #[Route('/', name: 'clubdetail', methods: ['GET'])]
    public function clubdetail(ClubRepository $clubRepository): Response
    {
        return $this->render('club/clubDetails.html.twig', [
            'clubs' => $clubRepository->findAll(),
        ]);
    }

    #[Route('/myclub', name: 'myclub', methods: ['GET'])]
    public function index23(ClubRepository $clubRepository): Response
    {
        return $this->render('club/myClub.html.twig', [
            'clubs' => $clubRepository->findAll(),
        ]);
    }

    #[Route('/member', name: 'member', methods: ['GET'])]
    public function member(ClubRepository $clubRepository): Response
    {
        return $this->render('club/member.html.twig', [
            'clubs' => $clubRepository->findAll(),
        ]);
    }

    #[Route('/listemember', name: 'listemember', methods: ['GET'])]
    public function listemember(ClubRepository $clubRepository): Response
    {
        return $this->render('club/listeMembre.html.twig', [
            'clubs' => $clubRepository->findAll(),
        ]);
    }

    #[Route('/joinclub', name: 'joinclub', methods: ['GET'])]
    public function joinclub(ClubRepository $clubRepository): Response
    {
        return $this->render('club/joinClub.html.twig', [
            'clubs' => $clubRepository->findAll(),
        ]);
    }

    #[Route('/frontpage', name: 'frontpage', methods: ['GET'])]
    public function frontpage(ClubRepository $clubRepository): Response
    {
        return $this->render('club/frontpage.html.twig', [
            'clubs' => $clubRepository->findAll(),
        ]);
    }

    //#[Route('/adminclub', name: 'adminclub', methods: ['GET'])]
    //public function adminclub(ClubRepository $clubRepository): Response
    //{
    //    return $this->render('club/adminClub.html.twig', [
    //        'clubs' => $clubRepository->findAll(),
    //    ]);
    //}

    //#[Route('/adminmember', name: 'adminmember', methods: ['GET'])]
    //public function adminmember(ClubRepository $clubRepository): Response
    //{
    //    return $this->render('club/adminMember.html.twig', [
    //        'clubs' => $clubRepository->findAll(),
    //    ]);
    //}

    #[Route('/index2', name: 'app_club_index2', methods: ['GET'])]
    public function index2(ClubRepository $clubRepository, EntityManagerInterface $entityManager, PaginatorInterface $paginator, Request $request ): Response
    {   
        // Créer une requête pour récupérer les clubs
        $query = $entityManager->getRepository(Club::class)->createQueryBuilder('c')
        ->orderBy('c.nomC', 'ASC') // Trier par nom
        ->getQuery();

    // Paginate results
    $pagination = $paginator->paginate(
        $query,
        $request->query->getInt('page', 1),
        3// Number of items per page
    );

        return $this->render('club/index2.html.twig', [
            'clubs' => $clubRepository->findAll(),
            'pagination' => $pagination,
        ]);
    }
    #[Route('/newClub', name: 'app_club_new', methods: ['GET', 'POST'])]
    public function newClub(Request $request, EntityManagerInterface $entityManager, SluggerInterface $slugger): Response
    {
        $club = new Club();
        $form = $this->createForm(ClubType::class, $club);
        $form->handleRequest($request);
    
        if ($form->isSubmitted() && $form->isValid()) { 
            $club->setStatus(StatutClubEnum::EN_ATTENTE);
            $club->setPoints(0);
    
            // Gestion de l'upload de l'image du club
            $imageFile = $form->get('image')->getData();
            if ($imageFile) {
                $originalFilename = pathinfo($imageFile->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = $slugger->slug($originalFilename);
                $newFilename = $safeFilename . '-' . uniqid() . '.' . $imageFile->guessExtension();
    
                try {
                    $imageFile->move(
                        $this->getParameter('uploads_directory'), // Assurez-vous de définir ce paramètre
                        $newFilename
                    );
                    $club->setImage($newFilename);
                } catch (FileException $e) {
                    $this->addFlash('error', 'Une erreur est survenue lors du téléchargement de l\'image.');
                    return $this->redirectToRoute('app_club_new');
                }
            }

            // Associer le club à l'utilisateur connecté (le président)
            $president = $this->getUser(); // Utilisateur connecté
            if (!$president) {
                throw new \Exception("Aucun utilisateur connecté.");
            }
            $club->setPresident($president); // Définir le président du club
    
            $entityManager->persist($club); 
            $entityManager->flush(); 
    
            return $this->redirectToRoute('app_club_index', [], Response::HTTP_SEE_OTHER); 
        }
    //afficher le formulaire
        return $this->render('club/new.html.twig', [
            'club' => $club, 
            'form' => $form, 
        ]);
    }
    
    #[Route('/{id}', name: 'app_club_show', methods: ['GET'])] // show the club
    public function show($id, EntityManagerInterface $entityManager): Response

    {
        $club = $entityManager->getRepository(Club::class)->find($id);
        return $this->render('club/myClub.html.twig', [
            'club' => $club,
        ]);
    }

    #[Route('/myClub', name: 'myClub', methods: ['GET'])]
    public function myClub()
    {
        return $this->render('club/myClub.html.twig', [
            
        ]);
    }

    #[Route('/{id}/edit', name: 'app_club_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Club $club, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(ClubType::class, $club);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_club_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('club/edit.html.twig', [
            'club' => $club,
            'form' => $form,
        ]);
    }

    //#[Route('/{id}', name: 'app_club_delete', methods: ['POST'])]
   // public function delete(int $id, Request $request, EntityManagerInterface $entityManager): Response
    /////////

        //if ($this->isCsrfTokenValid('delete'.$club->getId(), $request->request->get('_token'))) {
            //$entityManager->remove($club);
            //$entityManager->flush();
            //$this->addFlash('success', 'Club supprimé avec succès.');
        //}

        //return $this->redirectToRoute('app_club_index2');
    //}


        //if ($this->isCsrfTokenValid('delete'.$club->getId(), $request->request->get('_token'))) {
            //$entityManager->remove($club);
            //$entityManager->flush();
            //$this->addFlash('success', 'Club supprimé avec succès.');
        //}

        //return $this->redirectToRoute('app_club_index2');
    //}

    #[Route('/clubdelete/{id}', name: 'app_club_delete')]
    public function supprimerPoste(int $id, EntityManagerInterface $entityManager, ClubRepository $clubRepository): Response
    {
       // $club = $entityManager->getRepository(Club::class)->find($id);
        $club = $clubRepository->findById($id);
        if (!$club) {
            // Post does not exist, redirect back
           // $this->addFlash('error', 'Le poste n\'existe pas.');
            return $this->redirectToRoute('app_club_index2');
        }

        // Remove the post from the database
        $entityManager->remove($club);
        $entityManager->flush();

        $this->addFlash('success', 'Le poste a été supprimé avec succès.');
        return $this->redirectToRoute('app_club_index2');
    }

    #[Route('/accepteclub/{id}', name: 'club_accepte')]
    public function acceptePoste(
        int $id, 
        EntityManagerInterface $entityManager, 
        ClubRepository $clubRepository
    ): Response {
        // Find the club using the repository method
        $club = $clubRepository->find($id);

        // Check if the club exists
        if (!$club) {
            // Add error handling
            $this->addFlash('error', 'Club not found.');
            return $this->redirectToRoute('app_club_index2');
        }

        try {
            // Get the current status before changing
            $currentStatus = $club->getStatus();

            // Update the status to ACCEPTE
            $club->setStatus(StatutClubEnum::ACCEPTE);

            // Persist and flush the changes
            $entityManager->persist($club);
            $entityManager->flush();

            // Add success message
            //$this->addFlash('success', 'Club has been successfully accepted. Previous status: ' . $currentStatus);

            // Redirect back to the index page
            return $this->redirectToRoute('app_club_index2');

        } catch (\Exception $e) {
            // Log the error
            $this->addFlash('error', 'An error occurred: ' . $e->getMessage());
            return $this->redirectToRoute('app_club_index2');
        }
    }

    #[Route('/search', name: 'app_club_search', methods: ['GET'])]
    public function search(Request $request, ClubRepository $clubRepository): Response
    {
        // Récupérer le terme de recherche
        $query = $request->query->get('query');

        // Filtrer les clubs si un terme de recherche est présent
        if ($query) {
            $clubs = $clubRepository->findByName($query);
        } else {
            // Sinon, afficher tous les clubs
            $clubs = $clubRepository->findAll();
        }

        // Passer la variable `clubs` au template
        return $this->render('club/index.html.twig', [
            'clubs' => $clubs,
        ]);
    }
}


