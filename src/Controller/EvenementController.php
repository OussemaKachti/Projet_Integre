<?php

namespace App\Controller;

use App\Entity\Evenement;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use App\Form\EvenementType;
use App\Repository\CategorieRepository;
use App\Repository\EvenementRepository;
use App\Repository\ParticipationEventRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\String\Slugger\SluggerInterface;
use App\Repository\ClubRepository;

use Knp\Component\Pager\PaginatorInterface;
use App\Enum\RoleEnum;
use App\Entity\User;
use Symfony\Component\HttpFoundation\JsonResponse;

use Twig\Environment;
use Dompdf\Dompdf;
use Dompdf\Options;
use App\Entity\ParticipationEvent;



#[Route('/evenement')]
class EvenementController extends AbstractController
{
    #[Route('/admin', name: 'admin_page')]
    public function adminPage(EvenementRepository $evenementRepository, CategorieRepository $categorieRepository): Response
    {
        // Retrieve events and categories
        $evenements = $evenementRepository->findAll();
        $categories = $categorieRepository->findAll();

        return $this->render('evenement/admin.html.twig', [
            'evenements' => $evenements,
            'categories' => $categories,
        ]);
    }

    #[Route('/admincat', name: 'admincat_page')]
    public function admincatPage(CategorieRepository $categorieRepository, EntityManagerInterface $entityManager): Response
    {
        // Retrieve categories
        $categories = $categorieRepository->findAll();
        
        // Get statistics: count events for each category
        $categoryStats = [];
        
        // Using DQL to get the count of events per category
        $query = $entityManager->createQuery(
            'SELECT c.id, c.nomCat, COUNT(e) as eventCount 
             FROM App\Entity\Categorie c 
             LEFT JOIN c.evenements e 
             GROUP BY c.id 
             ORDER BY eventCount DESC'
        );
        
        $results = $query->getResult();
        
        // Format data for charts
        $categoryLabels = [];
        $categoryData = [];
        $backgroundColors = [
            'rgb(54, 162, 235)',
            'rgb(255, 99, 132)',
            'rgb(255, 205, 86)',
            'rgb(75, 192, 192)',
            'rgb(153, 102, 255)',
            'rgb(255, 159, 64)',
            'rgb(201, 203, 207)'
        ];
        
        foreach ($results as $result) {
            $categoryLabels[] = $result['nomCat'];
            $categoryData[] = $result['eventCount'];
        }
        
        return $this->render('evenement/admincat.html.twig', [
            'categories' => $categories,
            'categoryLabels' => json_encode($categoryLabels),
            'categoryData' => json_encode($categoryData),
            'backgroundColors' => json_encode($backgroundColors)
        ]);
    }

    #[Route('/', name: 'event', methods: ['GET'])]
public function index(
    Request $request,
    EvenementRepository $evenementRepository,
    PaginatorInterface $paginator,
    ClubRepository $clubRepository
): Response {
    // Récupération des paramètres de recherche et de filtre
    $search = $request->query->get('search');
    $type = $request->query->get('type');
    $date = $request->query->get('date');
    $clubId = $request->query->get('club');  // Ajout du paramètre club

    // Construction du QueryBuilder avec filtrage dynamique
    $queryBuilder = $evenementRepository->createQueryBuilder('e');

    if ($search) {
        $queryBuilder->andWhere('e.nomEvent LIKE :search')
                     ->setParameter('search', '%' . $search . '%');
    }

    if ($type) {
        $queryBuilder->andWhere('e.type = :type')
                     ->setParameter('type', $type);
    }

    if ($date) {
        try {
            $dateObj = new \DateTime($date);
            $startOfDay = (clone $dateObj)->setTime(0, 0, 0);
            $endOfDay = (clone $dateObj)->setTime(23, 59, 59);

            $queryBuilder->andWhere('e.startDate BETWEEN :startOfDay AND :endOfDay')
                         ->setParameter('startOfDay', $startOfDay)
                         ->setParameter('endOfDay', $endOfDay);
        } catch (\Exception $e) {
            if ($request->isXmlHttpRequest()) {
                return $this->json(['error' => 'Invalid date format'], 400);
            }
            $this->addFlash('error', 'Invalid date format');
        }
    }

    // Ajout du filtre par club
    if ($clubId) {
        $queryBuilder->andWhere('e.club = :clubId')
                     ->setParameter('clubId', $clubId);
    }

    // Traitement AJAX pour le filtrage dynamique
    if ($request->isXmlHttpRequest()) {
        $evenements = $queryBuilder->getQuery()->getResult();

        $events = array_map(fn($evenement) => [
            'id' => $evenement->getId(),
            'title' => $evenement->getNomEvent(),
            'start' => $evenement->getStartDate()->format('Y-m-d H:i:s'),
            'end' => $evenement->getEndDate() ? $evenement->getEndDate()->format('Y-m-d H:i:s') : null,
            'location' => $evenement->getLieux(),
        ], $evenements);

        return $this->json($events);
    }

    // Pagination des résultats
    $evenements = $paginator->paginate(
        $queryBuilder->getQuery(),
        $request->query->getInt('page', 1),
        4
    );

    // Récupérer tous les clubs pour le menu déroulant de filtrage
    $clubs = $clubRepository->findAll();

    // Vérification des permissions pour la création d'événements
    $canCreateEvent = $this->canUserCreateEvent($this->getUser());

    return $this->render('evenement/event.html.twig', [
        'evenements' => $evenements,
        'canCreateEvent' => $canCreateEvent,
        'clubs' => $clubs,
        'search' => $search,
        'type' => $type,
        'date' => $date,
        'club' => $clubId,
    ]);
}
    /**
     * Vérifie si l'utilisateur peut créer un événement.
     */
    private function canUserCreateEvent(?User $user): bool
    {
        return $user && in_array($user->getRole(), [RoleEnum::PRESIDENT_CLUB, RoleEnum::ADMINISTRATEUR], true);
    }
    
    
//    #[Route('/', name: 'event', methods: ['GET'])]
// public function index(Request $request, EvenementRepository $evenementRepository, PaginatorInterface $paginator): Response
// {
//     // Utilisation du QueryBuilder pour récupérer les événements
//     $query = $evenementRepository->createQueryBuilder('e')->getQuery();

//     // Paginer la requête : 10 éléments par page, page courante récupérée via le paramètre "page"
//     $evenements = $paginator->paginate(
//         $query,
//         $request->query->getInt('page', 1),
//         4
//     );

//     return $this->render('evenement/event.html.twig', [
//         'evenements' => $evenements,
//     ]);
// }
#[Route('/event/details/{id}', name: 'eventdetails', methods: ['GET'])]
public function show(EvenementRepository $evenementRepository, EntityManagerInterface $entityManager, int $id): Response
{
    // Récupération de l'événement
    $evenement = $evenementRepository->find($id);

    if (!$evenement) {
        throw $this->createNotFoundException('Événement non trouvé');
    }

    // Vérification si l'utilisateur peut afficher les actions administratives
    $canManageEvent = $this->canUserManageEvent($this->getUser());
    
    // Récupérer la participation de l'utilisateur actuel, si elle existe
    $participation = null;
    $user = $this->getUser();
    if ($user) {
        $participation = $entityManager->getRepository(ParticipationEvent::class)->findOneBy([
            'evenement' => $evenement,
            'user' => $user
        ]);
    }

    // Retourner la vue avec l'événement, la permission de gestion et la participation
    return $this->render('evenement/eventdetails.html.twig', [
        'evenement' => $evenement,
        'canManageEvent' => $canManageEvent,  // Variable pour les actions admin
        'participation' => $participation,    // Passer la participation à la vue
    ]);
}
    
    /**
     * Vérifie si l'utilisateur peut gérer un événement (modifier, supprimer, etc.).
     */
    private function canUserManageEvent(?User $user): bool
    {
        return $user && in_array($user->getRole(), [RoleEnum::PRESIDENT_CLUB, RoleEnum::ADMINISTRATEUR], true);
    }
    
    private Environment $twig;

    public function __construct(Environment $twig)
    {
        $this->twig = $twig;
    }



   
  
    
    #[Route('/event/join/{id}', name: 'event_join', methods: ['POST'])]
    public function joinEvent(Request $request, EvenementRepository $evenementRepository, 
                           EntityManagerInterface $entityManager, int $id): Response
    {
        // Récupération de l'événement
        $evenement = $evenementRepository->find($id);
        
        if (!$evenement) {
            throw $this->createNotFoundException('Événement non trouvé');
        }
        
        // Récupération de l'utilisateur connecté
        $user = $this->getUser();
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }
        
        // Vérifier si l'utilisateur participe déjà à l'événement
        $participation = $entityManager->getRepository(ParticipationEvent::class)->findOneBy([
            'evenement' => $evenement,
            'user' => $user
        ]);
        
        if (!$participation) {
            // Création d'une nouvelle participation
            $participation = new ParticipationEvent();
            $participation->setEvenement($evenement);
            $participation->setUser($user);
            $participation->setdateparticipation(new \DateTime());
            
            // Enregistrement de la participation
            $entityManager->persist($participation);
            $entityManager->flush();
            
            $this->addFlash('success', 'Votre participation a été enregistrée avec succès. Vous pouvez maintenant télécharger votre ticket.');
        } else {
            // L'utilisateur participe déjà à l'événement
            $this->addFlash('info', 'Vous participez déjà à cet événement.');
        }
        
        // Rediriger vers la page de détails de l'événement (au lieu de la page de téléchargement)
        return $this->redirectToRoute('eventdetails', ['id' => $id]);
    }
    
    // Les autres méthodes restent inchangées
    #[Route('/event/ticket/{id}', name: 'download_ticket', methods: ['GET'])]
    public function downloadTicket(int $id, EntityManagerInterface $entityManager): Response
    {
        // Récupérer la participation
        $participation = $entityManager->getRepository(ParticipationEvent::class)->find($id);
        
        if (!$participation) {
            throw $this->createNotFoundException('Participation non trouvée');
        }
        
        // Vérifier que l'utilisateur actuel est autorisé à accéder à ce ticket
        if ($participation->getUser() !== $this->getUser()) {
            throw $this->createAccessDeniedException('Vous n\'êtes pas autorisé à accéder à ce ticket');
        }
        
        try {
            // Générer et télécharger le PDF
            return $this->generateTicketPdf($participation);
        } catch (\Exception $e) {
            $this->addFlash('error', 'Erreur lors de la génération du ticket: ' . $e->getMessage());
            return $this->redirectToRoute('eventdetails', ['id' => $participation->getEvenement()->getId()]);
        }
    }
    
    /**
     * Génère un ticket PDF pour une participation
     */
    private function generateTicketPdf(ParticipationEvent $participation): Response
    {
        // Options personnalisées pour DomPDF
        $pdfOptions = new Options();
        $pdfOptions->set('defaultFont', 'DejaVu Sans');
        $pdfOptions->set('isHtml5ParserEnabled', true);
        $pdfOptions->set('isRemoteEnabled', true);
        
        $dompdf = new Dompdf($pdfOptions);
        
        // Récupérer les données de l'événement et du participant
        $evenement = $participation->getEvenement();
        $user = $participation->getUser();
        
        // Générer un ID de ticket unique
        $ticketId = 'TICKET-' . $participation->getId() . '-' . time();
        
        // Palette de couleurs personnalisée
        $primaryColor = '#3498db';  // Bleu
        $secondaryColor = '#2ecc71';  // Vert
        $backgroundColor = '#f1f2f6';  // Gris clair
        
        // Chemin vers le logo
        $logoPath = $this->getParameter('kernel.project_dir') . '/public/front_assets/img/logo/logo2000000.jpg';
        
        // Vérifier si le logo existe et le convertir en base64
        $logoBase64 = '';
        $logoImg = '';
        if (file_exists($logoPath)) {
            $logoData = file_get_contents($logoPath);
            $logoBase64 = base64_encode($logoData);
            $logoImg = "<img src='data:image/png;base64,{$logoBase64}' ' style='max-width: 300px; max-height: 250px; margin-bottom: 1px; display: block; margin-top: 0;'>";
        } else {
            error_log('Logo file not found at: ' . $logoPath);
        }
        
        // HTML personnalisé avec du style inline
        $html = "
        <!DOCTYPE html>
        <html>
        <head>
            <style>
                @import url('https://fonts.googleapis.com/css2?family=Montserrat:wght@400;700&display=swap');
                
                body {
                    font-family: 'Montserrat', sans-serif;
                    background-color: {$backgroundColor};
                    margin: 0;
                    padding: 20px;
                    color: #2c3e50;
                }
                
                .ticket-container {
                    max-width: 600px;
                    margin: 0 auto;
                    background-color: white;
                    border-radius: 15px;
                    box-shadow: 0 10px 20px rgba(0,0,0,0.1);
                    overflow: hidden;
                    border: 3px solid {$primaryColor};
                }
                
                .ticket-header {
    background: linear-gradient(to right, {$primaryColor}, {$secondaryColor});
    color: white;
    text-align: center;
    padding: 10px; /* Réduire le padding vertical */
    display: flex;
    flex-direction: column;
    align-items: center;
}

.ticket-logo {
    margin-bottom: 5px; /* Très petit espace */
}
                
                .ticket-header h1 {
                    margin: 0;
                    font-size: 28px;
                    text-transform: uppercase;
                    letter-spacing: 2px;
                }
                
                .ticket-body {
                    padding: 30px;
                }
                
                .ticket-logo {
                    text-align: center;
                    margin-bottom: 20px;
                }
                
                .ticket-detail {
                    margin-bottom: 20px;
                    padding: 15px;
                    background-color: #f8f9fa;
                    border-radius: 10px;
                }
                
                .ticket-detail label {
                    display: block;
                    color: #7f8c8d;
                    margin-bottom: 5px;
                    text-transform: uppercase;
                    font-size: 12px;
                }
                
                .ticket-detail .value {
                    font-weight: bold;
                    color: #2c3e50;
                    font-size: 16px;
                }
                
                .ticket-footer {
                    background-color: {$primaryColor};
                    color: white;
                    text-align: center;
                    padding: 15px;
                    font-size: 14px;
                }
            </style>
        </head>
        <body>
            <div class='ticket-container'>
                <div class='ticket-header'>
                    <div class='ticket-logo'>
                        {$logoImg}
                    </div>
                    <h1>{$evenement->getNomEvent()}</h1>
                </div>
                
                <div class='ticket-body'>
                    <div class='ticket-detail'>
                        <label>Participant</label>
                        <div class='value'>{$user->getPrenom()} {$user->getNom()}</div>
                    </div>
                    
                    <div class='ticket-detail'>
                        <label>Date de l'événement</label>
                        <div class='value'>{$evenement->getStartDate()->format('d/m/Y H:i')}</div>
                    </div>
                    
                    <div class='ticket-detail'>
                        <label>Lieu</label>
                        <div class='value'>{$evenement->getLieux()}</div>
                    </div>
                    
                    <div class='ticket-detail'>
                        <label>Numéro de ticket</label>
                        <div class='value'>{$ticketId}</div>
                    </div>
                </div>
                
                <div class='ticket-footer'>
                    Merci pour votre participation !
                </div>
            </div>
        </body>
        </html>
        ";
        
        // Générer le PDF
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();
        
        // Nom de fichier personnalisé
        $eventName = preg_replace('/[^a-zA-Z0-9_-]/', '', $evenement->getNomEvent());
        $fileName = 'ticket-' . $eventName . '-' . time() . '.pdf';
        
        // Retourner le PDF
        return new Response(
            $dompdf->output(),
            Response::HTTP_OK,
            [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'attachment; filename="' . $fileName . '"',
                'Cache-Control' => 'no-cache, no-store, must-revalidate',
            ]
        );
    }
        #[Route('/event/{id}/participants', name: 'event_participants', methods: ['GET'])]
        public function viewParticipants(int $id, EvenementRepository $evenementRepository, EntityManagerInterface $entityManager): Response
        {
            // Récupérer l'événement
            $evenement = $evenementRepository->find($id);
        
            if (!$evenement) {
                throw $this->createNotFoundException('Événement non trouvé');
            }
        
            // Récupérer les participants
            $participants = $entityManager->getRepository(ParticipationEvent::class)->findBy(['evenement' => $evenement]);
        
            return $this->render('evenement/participants.html.twig', [
                'evenement' => $evenement,
                'participants' => $participants,
            ]);
        }
        
        #[Route('/my-events', name: 'my_events')]
        public function myEvents(
            Request $request,
            ParticipationEventRepository $participationRepository,
            EvenementRepository $evenementRepository,
            ClubRepository $clubRepository,
            EntityManagerInterface $entityManager
        ): Response
        {
            // Récupérer l'utilisateur connecté
            $user = $this->getUser();
        
            if (!$user) {
                return $this->redirectToRoute('app_login'); // Redirige vers la page de login si l'utilisateur n'est pas connecté
            }
        
            // Récupération des paramètres de filtrage
            $search = $request->query->get('search');
            $type = $request->query->get('type');
            $date = $request->query->get('date');
            $clubId = $request->query->get('club');
        
            // Récupérer les participations de l'utilisateur
            $participations = $participationRepository->findBy(['user' => $user]);
        
            // Récupérer les IDs des événements auxquels l'utilisateur participe
            $evenementIds = [];
            foreach ($participations as $participation) {
                $evenementIds[] = $participation->getEvenement()->getId();
            }
        
            // Si l'utilisateur ne participe à aucun événement, retourner un tableau vide
            if (empty($evenementIds)) {
                return $this->render('evenement/myevents.html.twig', [
                    'evenements' => [],
                    'clubs' => $clubRepository->findAll(),
                ]);
            }
        
            // Création du QueryBuilder pour filtrer les événements
            $queryBuilder = $evenementRepository->createQueryBuilder('e')
                ->where('e.id IN (:ids)')
                ->setParameter('ids', $evenementIds);
        
            // Appliquer les filtres
            if ($search) {
                $queryBuilder->andWhere('e.nomEvent LIKE :search')
                    ->setParameter('search', '%' . $search . '%');
            }
        
            if ($type) {
                $queryBuilder->andWhere('e.type = :type')
                    ->setParameter('type', $type);
            }
        
            if ($date) {
                try {
                    $dateObj = new \DateTime($date);
                    $startOfDay = (clone $dateObj)->setTime(0, 0, 0);
                    $endOfDay = (clone $dateObj)->setTime(23, 59, 59);
                    $queryBuilder->andWhere('e.startDate BETWEEN :startOfDay AND :endOfDay')
                        ->setParameter('startOfDay', $startOfDay)
                        ->setParameter('endOfDay', $endOfDay);
                } catch (\Exception $e) {
                    // En cas d'erreur de format de date, ignorer ce filtre
                }
            }
        
            // Filtrer par club
            if ($clubId) {
                $queryBuilder->andWhere('e.club = :clubId')
                    ->setParameter('clubId', $clubId);
            }
        
            // Exécuter la requête
            $evenements = $queryBuilder->getQuery()->getResult();
        
            // Récupérer tous les clubs pour le menu déroulant de filtrage
            $clubs = $clubRepository->findAll();
        
            return $this->render('evenement/myevents.html.twig', [
                'evenements' => $evenements,
                'search' => $search,
                'type' => $type,
                'date' => $date,
                'club' => $clubId,
                'clubs' => $clubs,
            ]);
        }

    #[Route('/new', name: 'app_evenement_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager, SluggerInterface $slugger, ValidatorInterface $validator): Response
    {
        $evenement = new Evenement();
        $form = $this->createForm(EvenementType::class, $evenement);
        $form->handleRequest($request);
    
        // Initialize errors for GET request
        $errors = [];
    
        if ($form->isSubmitted() && $form->isValid()) {
  

    // Récupérer l’image
    $imageDescriptionFile = $form->get('imageDescription')->getData();
    
    if ($imageDescriptionFile) {
        $originalFilename = pathinfo($imageDescriptionFile->getClientOriginalName(), PATHINFO_FILENAME);
        $safeFilename = $slugger->slug($originalFilename);
        $newFilename = $safeFilename . '-' . uniqid() . '.' . $imageDescriptionFile->guessExtension();

        try {
            $imageDescriptionFile->move(
                $this->getParameter('event_images_directory'),
                $newFilename
            );
            $evenement->setImageDescription($newFilename);
        } catch (FileException $e) {
            $this->addFlash('error', 'Erreur lors de l\'upload de l\'image.');
        }
    }

    $entityManager->persist($evenement);
    $entityManager->flush();

    $this->addFlash('success', 'Événement créé avec succès !');
    return $this->redirectToRoute('event');
}

    
        return $this->render('evenement/newevent.html.twig', [
            'form' => $form->createView(),
            'evenement' => $evenement,
            'errors' => $errors,
        ]);
    }
    

    #[Route('/{id}/edit', name: 'app_evenement_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Evenement $evenement, EntityManagerInterface $entityManager): Response
    {
        if (!$evenement) {
            throw $this->createNotFoundException("Événement non trouvé.");
        }

        $form = $this->createForm(EvenementType::class, $evenement);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('event', ['id' => $evenement->getId()]);
        }

        return $this->render('evenement/editevent.html.twig', [
            'evenement' => $evenement,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/admin/{id}/delete', name: 'app_evenement_delete', methods: ['POST'])]
    public function delete(Request $request, EntityManagerInterface $entityManager, int $id): Response
    {
        $evenement = $entityManager->getRepository(Evenement::class)->find($id);

        if ($this->isCsrfTokenValid('delete' . $evenement->getId(), $request->request->get('_token'))) {
            $entityManager->remove($evenement);
            $entityManager->flush();
        }

        return $this->redirectToRoute('admin_page');
    }

    #[Route('/pres/{id}/delete', name: 'delete_pres', methods: ['POST'])]
    public function delete2(Request $request, EntityManagerInterface $entityManager, int $id): Response
    {
        $evenement = $entityManager->getRepository(Evenement::class)->find($id);

        if ($this->isCsrfTokenValid('delete' . $evenement->getId(), $request->request->get('_token'))) {
            $entityManager->remove($evenement);
            $entityManager->flush();
        }

        return $this->redirectToRoute('event');
    }

    #[Route("/api/events", name:"api_events", methods: ["GET"])]
public function getFilteredEvents(Request $request, EvenementRepository $evenementRepository): JsonResponse
{
    // Récupération des paramètres de la requête
    $search = $request->query->get('search');
    $type   = $request->query->get('type');
    $date   = $request->query->get('date');

    // Initialisation du QueryBuilder
    $queryBuilder = $evenementRepository->createQueryBuilder('e');

    // Filtrage par nom d'événement
    if ($search) {
        $queryBuilder->andWhere('e.nomEvent LIKE :search')
                     ->setParameter('search', '%' . $search . '%');
    }

    // Filtrage par type d'événement
    if ($type) {
        $queryBuilder->andWhere('e.type = :type')
                     ->setParameter('type', $type);
    }

    // Filtrage par date : récupération des événements dont la date de début se situe
    // entre le début et la fin de la journée passée en paramètre
    if ($date) {
        try {
            $dateObj = new \DateTime($date);
            $startOfDay = (clone $dateObj)->setTime(0, 0, 0);
            $endOfDay   = (clone $dateObj)->setTime(23, 59, 59);
            $queryBuilder->andWhere('e.startDate BETWEEN :startOfDay AND :endOfDay')
                         ->setParameter('startOfDay', $startOfDay)
                         ->setParameter('endOfDay', $endOfDay);
        } catch (\Exception $e) {
            return $this->json(['error' => 'Invalid date format'], 400);
        }
    }

    // Exécution de la requête
    $evenements = $queryBuilder->getQuery()->getResult();

    // Transformation des événements pour FullCalendar
    $events = [];
    foreach ($evenements as $evenement) {
        $events[] = [
            'id'       => $evenement->getId(),
            'title'    => $evenement->getNomEvent(),
            'start'    => $evenement->getStartDate()->format('Y-m-d H:i:s'),
            'end'      => $evenement->getEndDate() ? $evenement->getEndDate()->format('Y-m-d H:i:s') : null,
            'location' => $evenement->getLieux(),
        ];
    }

    return $this->json($events);
}


#[Route('/calendar', name: 'app_calendar')]
public function calendarView(): Response
{
    return $this->render('evenement/calendar.html.twig');
}

#[Route('/api/events', name: 'events_calendar')]
public function getEvents(EvenementRepository $eventRepository): JsonResponse
{
    $events = $eventRepository->findAll();
    $data = [];

    foreach ($events as $event) {
        $data[] = [
            'title' => $event->getNomEvent(),
            'start' => $event->getStartDate()->format('Y-m-d H:i:s'),
            'color' => '#007BFF'
        ];
    }

    return new JsonResponse($data);
}

}
