<?php

namespace App\Controller;

use App\Entity\Sondage;
use App\Form\SondageType;
use App\Repository\SondageRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/sondage')]
class SondageController extends AbstractController
{
    #[Route('/', name: 'app_sondage_index', methods: ['GET'])]
    public function index(SondageRepository $sondageRepository): Response
    {
        return $this->render('sondage/index.html.twig', [
            'sondages' => $sondageRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'app_sondage_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $sondage = new Sondage();
        $form = $this->createForm(SondageType::class, $sondage);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($sondage);
            $entityManager->flush();

            return $this->redirectToRoute('app_sondage_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('sondage/new.html.twig', [
            'sondage' => $sondage,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_sondage_show', methods: ['GET'])]
    public function show(Sondage $sondage): Response
    {
        return $this->render('sondage/show.html.twig', [
            'sondage' => $sondage,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_sondage_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Sondage $sondage, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(SondageType::class, $sondage);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_sondage_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('sondage/edit.html.twig', [
            'sondage' => $sondage,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_sondage_delete', methods: ['POST'])]
    public function delete(Request $request, Sondage $sondage, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$sondage->getId(), $request->request->get('_token'))) {
            $entityManager->remove($sondage);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_sondage_index', [], Response::HTTP_SEE_OTHER);
    }
}
