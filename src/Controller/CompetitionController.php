<?php

namespace App\Controller;

use App\Entity\Competition;
use App\Form\CompetitionType;
use App\Form\EditCompetitionType;
use App\Repository\CompetitionRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/competition')]
class CompetitionController extends AbstractController
{
    #[Route('/', name: 'app_competition_index', methods: ['GET'])]
    public function index(Request $request,CompetitionRepository $competitionRepository ,EntityManagerInterface $entityManager): Response
    {
        $competition = new Competition();
    $form = $this->createForm(CompetitionType::class, $competition);
    $form->handleRequest($request);

    if ($form->isSubmitted() && $form->isValid()) {
        $entityManager->persist($competition);
        $entityManager->flush();

        return $this->redirectToRoute('app_competition_index'); 
    }
    

    return $this->render('competition/index.html.twig', [
        'missions' => $competitionRepository->findAll(),
        'form' => $form->createView(), // ✅ Pass the form to Twig
    ]);
    }

    #[Route('/new', name: 'app_competition_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $competition = new Competition();
        $form = $this->createForm(CompetitionType::class, $competition);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($competition);
            $entityManager->flush();

            return $this->redirectToRoute('app_competition_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('competition/new.html.twig', [
            'competition' => $competition,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_competition_show', methods: ['GET'])]
    public function show(Competition $competition): Response
    {
        return $this->render('competition/show.html.twig', [
            'competition' => $competition,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_competition_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Competition $competition, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(EditCompetitionType::class, $competition);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();
            $this->addFlash('success', 'Mission updated successfully!');

            return $this->redirectToRoute('app_competition_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('competition/edit.html.twig', [
            'competition' => $competition,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}', name: 'app_competition_delete', methods: ['POST'])]
    public function delete(Request $request, Competition $competition, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$competition->getId(), $request->request->get('_token'))) {
            $entityManager->remove($competition);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_competition_index', [], Response::HTTP_SEE_OTHER);
    }
}