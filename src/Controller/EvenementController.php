<?php

namespace App\Controller;

use App\Entity\Evenement;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use App\Form\EvenementType;
use App\Repository\CategorieRepository;
use App\Repository\EvenementRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\String\Slugger\SluggerInterface;
use App\Repository\ClubRepository;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Knp\Component\Pager\PaginatorInterface;

use Symfony\Component\HttpFoundation\JsonResponse;


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
    public function admincatPage(CategorieRepository $categorieRepository): Response
    {
        // Retrieve categories
        $categories = $categorieRepository->findAll();

        return $this->render('evenement/admincat.html.twig', [
            'categories' => $categories,
        ]);
    }
    #[Route('/', name: 'event', methods: ['GET'])]
    public function index(
        Request $request, 
        EvenementRepository $evenementRepository, 
        PaginatorInterface $paginator
    ): Response {
        // Récupération des paramètres de recherche et de filtre
        $search = $request->query->get('search');
        $type   = $request->query->get('type');
        $date   = $request->query->get('date');
    
        // Construction du QueryBuilder avec filtrage dynamique
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
    
        // Filtrage par date (entre le début et la fin de la journée)
        if ($date) {
            try {
                $dateObj = new \DateTime($date);
                $startOfDay = (clone $dateObj)->setTime(0, 0, 0);
                $endOfDay   = (clone $dateObj)->setTime(23, 59, 59);
                $queryBuilder->andWhere('e.startDate BETWEEN :startOfDay AND :endOfDay')
                             ->setParameter('startOfDay', $startOfDay)
                             ->setParameter('endOfDay', $endOfDay);
            } catch (\Exception $e) {
                if ($request->isXmlHttpRequest()) {
                    return $this->json(['error' => 'Invalid date format'], 400);
                }
                // Vous pouvez également ajouter un message flash pour les requêtes non-AJAX.
            }
        }
    
        // Si la requête est AJAX, on renvoie un JSON pour le filtrage dynamique
        if ($request->isXmlHttpRequest()) {
            $evenements = $queryBuilder->getQuery()->getResult();
    
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
    
        // Pour une requête classique (non-AJAX), on effectue la pagination
        $query = $queryBuilder->getQuery();
        $evenements = $paginator->paginate(
            $query,
            $request->query->getInt('page', 1),
            4  // Nombre d'éléments par page (ajustez selon vos besoins)
        );
    
        return $this->render('evenement/event.html.twig', [
            'evenements' => $evenements,
        ]);
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
    #[Route('/club/{clubId}/events', name: 'club_events')]
    public function showEvents(int $clubId, ClubRepository $clubRepository, EvenementRepository $evenementRepository): Response
    {
        // Retrieve club by ID
        $club = $clubRepository->find($clubId);

        if (!$club) {
            throw $this->createNotFoundException('Le club n\'existe pas.');
        }

        // Retrieve club events
        $evenements = $evenementRepository->findBy(['club' => $club]);

        return $this->render('evenement/eventClub.html.twig', [
            'club' => $club,
            'evenements' => $evenements,
        ]);
    }

    #[Route('/event/details/{id}', name: 'eventdetails', methods: ['GET'])]
    public function show(EvenementRepository $evenementRepository, int $id): Response
    {
        $evenement = $evenementRepository->find($id);

        if (!$evenement) {
            throw $this->createNotFoundException('Événement non trouvé');
        }

        return $this->render('evenement/eventdetails.html.twig', [
            'evenement' => $evenement,
        ]);
    }

    #[Route('/pres/{id}', name: 'show2', methods: ['GET'])]
    public function show2(EvenementRepository $evenementRepository, int $id): Response
    {
        $evenement = $evenementRepository->find($id);

        if (!$evenement) {
            throw $this->createNotFoundException('Événement non trouvé');
        }

        return $this->render('evenement/president.html.twig', [
            'evenement' => $evenement,
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
            'color' => '#2C2C2C'
        ];
    }

    return new JsonResponse($data);
}

}
