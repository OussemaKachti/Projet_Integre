<?php

namespace App\Controller;

use App\Entity\ParticipationMembre;
use App\Form\ParticipationMembreType;
use App\Repository\ParticipationMembreRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Entity\Club;
use App\Entity\User;
use App\Repository\ClubRepository;
use Knp\Component\Pager\PaginatorInterface;

#[Route('/participation/membre')]
class ParticipationMembreController extends AbstractController
{
    #[Route('/', name: 'app_participation_membre_index', methods: ['GET'])]
public function index(EntityManagerInterface $entityManager, PaginatorInterface $paginator, Request $request): Response
{
    // Create a query to fetch participation requests, ordered by ID
    $query = $entityManager->getRepository(ParticipationMembre::class)
        ->createQueryBuilder('p')
        ->orderBy('p.id', 'ASC') // Sort by ID
        ->getQuery();

    // Paginate results (2 items per page)
    $pagination = $paginator->paginate(
        $query,
        $request->query->getInt('page', 1), // Current page, default to 1
        2 // Number of items per page
    );

    // Render the template with paginated results
    return $this->render('participation_membre/index.html.twig', [
        'pagination' => $pagination, // Pass pagination to the template
    ]);
}

#[Route('/indexx', name: 'index2', methods: ['GET'])]
public function index2(EntityManagerInterface $entityManager, PaginatorInterface $paginator, Request $request): Response
{
    // Get search query if present
    $keyword = $request->query->get('query', '');

    // Create base query builder for ParticipationMembre
    $queryBuilder = $entityManager->getRepository(ParticipationMembre::class)
        ->createQueryBuilder('p')
        ->join('p.club', 'c') // Join the club entity
        ->join('p.user', 'u') // Join the user entity
        ->orderBy('p.id', 'ASC'); // Sort by ID

    // Apply search filter if keyword is provided
    if (!empty($keyword)) {
        $queryBuilder
            ->andWhere('c.nomC LIKE :keyword')
            ->setParameter('keyword', '%' . $keyword . '%');
    }

    // Paginate results
    $pagination = $paginator->paginate(
        $queryBuilder->getQuery(),
        $request->query->getInt('page', 1), // Current page
        2 // Items per page
    );

    return $this->render('participation_membre/index2.html.twig', [
        'pagination' => $pagination,
        'keyword' => $keyword, // Pass keyword for search input persistence
    ]);
}

    
    #[Route('/new/{clubId}', name: 'app_participation_membre_new', methods: ['GET', 'POST'])]
public function new(Request $request, EntityManagerInterface $entityManager, int $clubId, ClubRepository $clubRepository): Response
{
    // Récupérer le club à partir de l'ID passé dans l'URL
    $club = $entityManager->getRepository(Club::class)->find($clubId);

    // Vérifie si le club existe
    $clubs = $clubRepository->findAll();

    // Créer une nouvelle participation
    $participationMembre = new ParticipationMembre();
    $participationMembre->setClub($club); // Associer le club à la participation

    $form = $this->createForm(ParticipationMembreType::class, $participationMembre);
    $form->handleRequest($request);

    if ($form->isSubmitted() && $form->isValid()) {
        // Normalisation du statut
        $participationMembre->setStatut("enAttente");

        // Persist l'objet
        $entityManager->persist($participationMembre);
        $entityManager->flush();

        // Message flash pour informer l'utilisateur
        $this->addFlash('success', 'Votre demande de participation a été enregistrée avec succès.');

        // Redirige vers la route '/indexx'
        return $this->redirectToRoute('index2');
    }

    // Afficher le formulaire
    return $this->render('participation_membre/new.html.twig', [
        'participation_membre' => $participationMembre,
        'form' => $form->createView(),
    ]);
}

    

    
    
    #[Route('/{id}', name: 'app_participation_membre_show', methods: ['GET'])]
    public function show(ParticipationMembre $participationMembre): Response
    {
        return $this->render('participation_membre/show.html.twig', [
            'participation_membre' => $participationMembre,
        ]);
    }

    #[Route('/detailleParticipation/{id}', name: 'detaille_participation', methods: ['GET'])]
    public function showDetailleParticipation(int $id,ParticipationMembreRepository $participationMembreRepository): Response
    {
        
        return $this->render('participation_membre/show.html.twig', [
            'participation_membre' => $participationMembreRepository->findParticipationDetails($id)

        ]);
    }



   

    #[Route('/{id}/edit', name: 'app_participation_membre_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, ParticipationMembre $participationMembre, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(ParticipationMembreType::class, $participationMembre);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_participation_membre_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('participation_membre/edit.html.twig', [
            'participation_membre' => $participationMembre,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_participation_membre_delete', methods: ['POST'])]
    public function delete(Request $request, ParticipationMembre $participationMembre, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$participationMembre->getId(), $request->request->get('_token'))) {
            $entityManager->remove($participationMembre);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_participation_membre_index', [], Response::HTTP_SEE_OTHER);
    }
    

    #[Route('/delete/{id}', name: 'app_participation_membre_delete')]
    public function supprimerPoste(int $id, EntityManagerInterface $entityManager, ParticipationMembreRepository $participationMembreRepository): Response
    {
       // $club = $entityManager->getRepository(Club::class)->find($id);
         $participationMembre = $participationMembreRepository->find($id);
        if (!$participationMembre) {
            // Post does not exist, redirect back
           // $this->addFlash('error', 'Le poste n\'existe pas.');
            return $this->redirectToRoute('app_participation_membre_index');
        }

        // Remove the post from the database
        $entityManager->remove($participationMembre);
        $entityManager->flush();

        $this->addFlash('success', 'Le poste a été supprimé avec succès.');
        return $this->redirectToRoute('app_participation_membre_index');
    }

    #[Route('/accepte/{id}', name: 'accepte')]
    public function acceptePoste(int $id,EntityManagerInterface $entityManager, ParticipationMembreRepository $participationMembreRepository): Response
    {
        $participationMembre = $entityManager->getRepository(ParticipationMembre::class)->find($id);

        if (!$participationMembre) {
            // Post does not exist, redirect back
            //$this->addFlash('error', 'Le poste n\'existe pas.');
            return $this->redirectToRoute('app_participation_membre_index');
        }
         // Debugging: Check the current status
        dump($participationMembre->getStatut());
        // accepte the post from the database
        $participationMembre->setStatut("accepte");
        $entityManager->flush();

        //$this->addFlash('success', 'Le poste a été accepte avec succès.');
        return $this->redirectToRoute('app_participation_membre_index');
    }
}






















