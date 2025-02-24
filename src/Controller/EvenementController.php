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

#[Route('/evenement')]
class EvenementController extends AbstractController
{

    #[Route('/admin', name: 'admin_page')]
public function adminPage(EvenementRepository $evenementRepository , CategorieRepository $categorieRepository): Response
{
    // Récupérer les événements ou d'autres données nécessaires
    $evenements = $evenementRepository->findAll();
    $categories =$categorieRepository->findAll();
 
    return $this->render('evenement/admin.html.twig', [
        'evenements' => $evenements,
        'categories'=>$categories,
        
    ]);
}
#[Route('/admincat', name: 'admincat_page')]
public function admincatPage(  CategorieRepository $categorieRepository): Response
{
    // Récupérer les événements ou d'autres données nécessaires
   
    $categories =$categorieRepository->findAll();
 
    return $this->render('evenement/admincat.html.twig', [
        
        'categories'=>$categories,
        
    ]);
}

    #[Route('/', name: 'event', methods: ['GET'])]
    public function index(EvenementRepository $evenementRepository): Response
    {
        $evenements = $evenementRepository->findAll();
        return $this->render('evenement/event.html.twig', [
            'evenements' => $evenements,
        ]);
    }


    #[Route('/club/{clubId}/events', name: 'club_events')]
    public function showEvents(int $clubId, ClubRepository $clubRepository, EvenementRepository $evenementRepository): Response
    {
        // Récupérer le club via son ID
        $club = $clubRepository->find($clubId);

        if (!$club) {
            throw $this->createNotFoundException('Le club n\'existe pas.');
        }

        // Récupérer les événements du club
        $evenements = $evenementRepository->findBy(['club' => $club]);

        // Rendre la vue en passant le club et ses événements
        return $this->render('evenement/eventClub.html.twig', [
            'club' => $club,
            'evenements' => $evenements,
        ]);
    }


    #[Route('/event/details/{id}', name: 'eventdetails', methods: ['GET'])]
    public function show(EvenementRepository $evenementRepository, $id): Response
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
    public function show2(EvenementRepository $evenementRepository, $id): Response
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
public function new(
    Request $request,
    EntityManagerInterface $entityManager,
    SluggerInterface $slugger,
    ValidatorInterface $validator
): Response {
    $evenement = new Evenement();
    $form = $this->createForm(EvenementType::class, $evenement);
    $form->handleRequest($request);

    // On initialise $errors à un tableau vide pour la requête GET
    $errors = [];

    if ($form->isSubmitted()) {
        // Valider l'entité avec les contraintes définies dans l'entité Evenement
        $errors = $validator->validate($evenement);

        if ($form->isValid() && count($errors) === 0) {
            // Gestion de l'image
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
        
        // Message flash pour informer l'utilisateur
      

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

    return $this->redirectToRoute('admin_page'); // Redirection vers la liste des événements
}

#[Route('/pres/{id}/delete', name: 'delete_pres', methods: ['POST'])]

public function delete2(Request $request, EntityManagerInterface $entityManager, int $id): Response
{
    $evenement = $entityManager->getRepository(Evenement::class)->find($id);


    if ($this->isCsrfTokenValid('delete' . $evenement->getId(), $request->request->get('_token'))) {
        $entityManager->remove($evenement);
        $entityManager->flush();
      
    }

    return $this->redirectToRoute('event'); // Redirection vers la liste des événements
}
}


   

