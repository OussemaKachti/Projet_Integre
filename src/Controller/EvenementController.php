<?php

namespace App\Controller;

use App\Entity\Evenement;
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

    #[Route('/', name: 'event', methods: ['GET'])]
    public function index(EvenementRepository $evenementRepository): Response
    {
        $evenements = $evenementRepository->findAll();
        return $this->render('evenement/event.html.twig', [
            'evenements' => $evenements,
        ]);
    }

    #[Route('/event/{id}', name: 'eventdetails', methods: ['GET'])]
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

    #[Route('/new', name: 'app_evenement_new', methods: ['GET', 'POST'])]
   // src/Controller/EvenementController.php

public function new(Request $request, EntityManagerInterface $entityManager, SluggerInterface $slugger): Response
{
    $evenement = new Evenement();
    $form = $this->createForm(EvenementType::class, $evenement);
    $form->handleRequest($request);

    if ($form->isSubmitted() && $form->isValid()) {
        if ($evenement->getClub() === null) {
            $this->addFlash('error', 'Le club doit être sélectionné.');
            return $this->redirectToRoute('app_evenement_new');
        }

      

        // Gestion de l'upload de l'image pour la description
        $imageDescriptionFile = $form->get('imageDescription')->getData();
        if ($imageDescriptionFile) {
            $originalFilename = pathinfo($imageDescriptionFile->getClientOriginalName(), PATHINFO_FILENAME);
            $safeFilename = $slugger->slug($originalFilename);
            $newFilename = $safeFilename . '-' . uniqid() . '.' . $imageDescriptionFile->guessExtension();

            try {
                $imageDescriptionFile->move(
                    $this->getParameter('event_images_directory'), // Assurez-vous de spécifier le bon répertoire
                    $newFilename
                );
                $evenement->setImageDescription($newFilename); // Enregistrez le nom de l'image dans la base de données
            } catch (FileException $e) {
                // Gérer l'erreur si l'upload échoue
            }
        }

        // Sauvegarde en base de données
        $entityManager->persist($evenement);
        $entityManager->flush();

        $this->addFlash('success', 'Événement créé avec succès !');
        return $this->redirectToRoute('event');
    }

    return $this->render('evenement/newevent.html.twig', [
        'form' => $form->createView(),
        'evenement' => $evenement,
    ]);
}


    #[Route('/{id}/edit', name: 'app_evenement_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Evenement $evenement, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(EvenementType::class, $evenement);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();
            return $this->redirectToRoute('event');
        }

        return $this->render('evenement/edit.html.twig', [
            'evenement' => $evenement,
            'form' => $form,
        ]);
    }

    #[Route('/admin/{id}/delete', name: 'app_evenement_delete', methods: ['POST'])]

public function delete(Request $request, EntityManagerInterface $entityManager, int $id): Response
{
    $evenement = $entityManager->getRepository(Evenement::class)->find($id);


    if ($this->isCsrfTokenValid('delete' . $evenement->getId(), $request->request->get('_token'))) {
        $entityManager->remove($evenement);
        $entityManager->flush();
        $this->addFlash('success', 'Événement supprimé avec succès.');
    }

    return $this->redirectToRoute('admin_page'); // Redirection vers la liste des événements
}}


   

