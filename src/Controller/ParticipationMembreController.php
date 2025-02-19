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

#[Route('/participation/membre')]
class ParticipationMembreController extends AbstractController
{
    #[Route('/', name: 'app_participation_membre_index', methods: ['GET'])]
    public function index(ParticipationMembreRepository $participationMembreRepository): Response
    {
        return $this->render('participation_membre/index.html.twig', [
            'participation_membres' => $participationMembreRepository->findAll(),
        ]);
    }
    

    #[Route('/new', name: 'app_participation_membre_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $participationMembre = new ParticipationMembre();
        $form = $this->createForm(ParticipationMembreType::class, $participationMembre);
        $form->handleRequest($request);

        
        if ($form->isSubmitted() && $form->isValid()) {
            
            $entityManager->persist($participationMembre);
            $entityManager->flush();

            return $this->redirectToRoute('app_participation_membre_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('participation_membre/new.html.twig', [
            'participation_membre' => $participationMembre,
            'form' => $form,
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
}
