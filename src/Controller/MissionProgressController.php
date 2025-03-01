<?php

namespace App\Controller;

use App\Entity\MissionProgress;
use App\Form\MissionProgress1Type;
use App\Repository\MissionProgressRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/mission/progress')]
final class MissionProgressController extends AbstractController
{
    #[Route(name: 'app_mission_progress_index', methods: ['GET'])]
    public function index(MissionProgressRepository $missionProgressRepository): Response
    {
        return $this->render('mission_progress/index.html.twig', [
            'mission_progresses' => $missionProgressRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'app_mission_progress_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $missionProgress = new MissionProgress();
        $form = $this->createForm(MissionProgress1Type::class, $missionProgress);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($missionProgress);
            $entityManager->flush();

            return $this->redirectToRoute('app_mission_progress_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('mission_progress/new.html.twig', [
            'mission_progress' => $missionProgress,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_mission_progress_show', methods: ['GET'])]
    public function show(MissionProgress $missionProgress): Response
    {
        return $this->render('mission_progress/show.html.twig', [
            'mission_progress' => $missionProgress,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_mission_progress_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, MissionProgress $missionProgress, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(MissionProgress1Type::class, $missionProgress);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_mission_progress_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('mission_progress/edit.html.twig', [
            'mission_progress' => $missionProgress,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_mission_progress_delete', methods: ['POST'])]
    public function delete(Request $request, MissionProgress $missionProgress, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$missionProgress->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($missionProgress);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_mission_progress_index', [], Response::HTTP_SEE_OTHER);
    }
}
