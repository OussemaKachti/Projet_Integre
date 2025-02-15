<?php

namespace App\Controller;

use App\Entity\ChoixSondage;
use App\Form\ChoixSondageType;
use App\Repository\ChoixSondageRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/choix/sondage')]
class ChoixSondageController extends AbstractController
{
    #[Route('/', name: 'app_choix_sondage_index', methods: ['GET'])]
    public function index(ChoixSondageRepository $choixSondageRepository): Response
    {
        return $this->render('choix_sondage/index.html.twig', [
            'choix_sondages' => $choixSondageRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'app_choix_sondage_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $choixSondage = new ChoixSondage();
        $form = $this->createForm(ChoixSondageType::class, $choixSondage);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($choixSondage);
            $entityManager->flush();

            return $this->redirectToRoute('app_choix_sondage_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('choix_sondage/new.html.twig', [
            'choix_sondage' => $choixSondage,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_choix_sondage_show', methods: ['GET'])]
    public function show(ChoixSondage $choixSondage): Response
    {
        return $this->render('choix_sondage/show.html.twig', [
            'choix_sondage' => $choixSondage,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_choix_sondage_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, ChoixSondage $choixSondage, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(ChoixSondageType::class, $choixSondage);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_choix_sondage_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('choix_sondage/edit.html.twig', [
            'choix_sondage' => $choixSondage,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_choix_sondage_delete', methods: ['POST'])]
    public function delete(Request $request, ChoixSondage $choixSondage, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$choixSondage->getId(), $request->request->get('_token'))) {
            $entityManager->remove($choixSondage);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_choix_sondage_index', [], Response::HTTP_SEE_OTHER);
    }
}
