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

use Symfony\Component\Mime\Email;
use Symfony\Component\Mailer\MailerInterface;


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
public function index2(ClubRepository $clubRepository, EntityManagerInterface $entityManager, PaginatorInterface $paginator, Request $request): Response
{   
    // Récupérer la recherche
    $keyword = $request->query->get('query', '');

    // Construire la requête principale avec recherche
    $queryBuilder = $entityManager->getRepository(Club::class)->createQueryBuilder('c')
        ->orderBy('c.nomC', 'ASC'); // Trier par ordre alphabétique

    // Appliquer le filtre de recherche si un mot-clé est fourni
    if (!empty($keyword)) {
        $queryBuilder
            ->where('LOWER(c.nomC) LIKE LOWER(:keyword)') // Insensible à la casse
            ->setParameter('keyword', '%' . $keyword . '%');
    }

    // Paginer les résultats
    $pagination = $paginator->paginate(
        $queryBuilder->getQuery(), // Exécuter la requête construite
        $request->query->getInt('page', 1), // Page actuelle
        3 // Nombre d'éléments par page
    );

    return $this->render('club/index2.html.twig', [
        'pagination' => $pagination,
        'keyword' => $keyword, // Passer la recherche au template
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

    #[Route('/clubdelete/{id}', name: 'app_club_delete', methods: ['GET', 'POST', 'DELETE'])]
    public function deleteClub(int $id, EntityManagerInterface $entityManager, Request $request): Response
    {
        try {
            // First get the club to see if it exists
            $club = $entityManager->getRepository(Club::class)->find($id);
            
            if (!$club) {
                $this->addFlash('error', 'Le club n\'existe pas.');
                return $this->redirectToRoute('app_club_index2');
            }
            
            // Validate CSRF token if it's a POST request
            if ($request->isMethod('POST') && !$this->isCsrfTokenValid('delete'.$club->getId(), $request->request->get('_token'))) {
                $this->addFlash('error', 'Invalid CSRF token.');
                return $this->redirectToRoute('app_club_index2');
            }
            
            // Use direct SQL to delete the club and related records
            $conn = $entityManager->getConnection();
            
            // Start transaction
            $conn->beginTransaction();
            
            try {
                // 1. Delete sondage-related records first
                $conn->executeStatement('DELETE FROM reponse WHERE sondage_id IN (SELECT id FROM sondage WHERE club_id = :id)', ['id' => $id]);
                $conn->executeStatement('DELETE FROM commentaire WHERE sondage_id IN (SELECT id FROM sondage WHERE club_id = :id)', ['id' => $id]);
                $conn->executeStatement('DELETE FROM choix_sondage WHERE sondage_id IN (SELECT id FROM sondage WHERE club_id = :id)', ['id' => $id]);
                $conn->executeStatement('DELETE FROM sondage WHERE club_id = :id', ['id' => $id]);
                
                // 2. Delete event-related records
                $conn->executeStatement('DELETE FROM likes WHERE evenement_id IN (SELECT id FROM evenement WHERE club_id = :id)', ['id' => $id]);
                $conn->executeStatement('DELETE FROM participation_event WHERE evenement_id IN (SELECT id FROM evenement WHERE club_id = :id)', ['id' => $id]);
                $conn->executeStatement('DELETE FROM evenement WHERE club_id = :id', ['id' => $id]);
                
                // 3. Delete products & order details
                $conn->executeStatement('DELETE FROM orderdetails WHERE produit_id IN (SELECT id FROM produit WHERE club_id = :id)', ['id' => $id]);
                $conn->executeStatement('DELETE FROM produit WHERE club_id = :id', ['id' => $id]);
                
                // 4. Delete participations
                $conn->executeStatement('DELETE FROM participation_membre WHERE club_id = :id', ['id' => $id]);
                
                // 5. Delete mission progress entries
                $conn->executeStatement('DELETE FROM mission_progress WHERE club_id = :id', ['id' => $id]);
                
                // 6. Finally delete the club
                $conn->executeStatement('DELETE FROM club WHERE id = :id', ['id' => $id]);
                
                // Commit transaction
                $conn->commit();
                
                $this->addFlash('success', 'Le club a été supprimé avec succès.');
            } catch (\Exception $e) {
                // Rollback transaction on error
                $conn->rollBack();
                throw $e;
            }
            
        } catch (\Exception $e) {
            $this->addFlash('error', 'Erreur lors de la suppression: ' . $e->getMessage());
            
            // Log full exception details
            error_log('Club deletion error: ' . $e->getMessage());
            error_log('Exception trace: ' . $e->getTraceAsString());
        }
        
        return $this->redirectToRoute('app_club_index2');
    }

    #[Route('/accepteclub/{id}', name: 'club_accepte')]
    public function acceptePoste(
        int $id, 
        EntityManagerInterface $entityManager, 
        ClubRepository $clubRepository,
        MailerInterface $mailer
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
            
            // Send email notification to club president
            $email = (new Email())
                ->from("amaltr249@gmail.com")
                ->to($club->getPresident()->getEmail())
                ->subject('Club Validation')
                ->html("
                    <p>Votre club a été accepté!</p>
                ");
            
            $mailer->send($email);
            
            // Add success message
            $this->addFlash('success', 'Club has been successfully accepted.');

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