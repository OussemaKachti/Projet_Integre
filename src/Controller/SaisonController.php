<?php

namespace App\Controller;

use App\Entity\Saison;
use App\Form\SaisonType;
use App\Form\EditSaisonType;

use App\Repository\SaisonRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/saison')]
class SaisonController extends AbstractController
{
    #[Route('/', name: 'app_saison_index', methods: ['GET'])]
    public function index(Request $request ,SaisonRepository $saisonRepository ,EntityManagerInterface $entityManager): Response
    {
        $saison = new saison();
        $form = $this->createForm(SaisonType::class, $saison);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($saison);
            $entityManager->flush();
    
            return $this->redirectToRoute('app_saison_index'); 
        }

        return $this->render('saison/index.html.twig', [
            'saisons' => $saisonRepository->findAll(),
            'form' => $form->createView(),
        ]);
    }

    #[Route('/new', name: 'app_saison_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $saison = new Saison();
        $form = $this->createForm(SaisonType::class, $saison);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($saison);
            $entityManager->flush();

            return $this->redirectToRoute('app_saison_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('saison/new.html.twig', [
            'saison' => $saison,
            'form' => $form,

        ]);
        
    }

    #[Route('/{id}', name: 'app_saison_show', methods: ['GET'])]
    public function show(Saison $saison): Response
    {
        return $this->render('saison/show.html.twig', [
            'saison' => $saison,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_saison_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Saison $saison, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(EditSaisonType::class, $saison);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();
            $this->addFlash('success', 'Season updated successfully!');

            return $this->redirectToRoute('app_saison_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('saison/edit.html.twig', [
            'saison' => $saison,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_saison_delete', methods: ['POST'])]
    public function delete(Request $request, Saison $saison, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$saison->getId(), $request->request->get('_token'))) {
            // Remove competitions first if needed
            foreach ($saison->getCompetitions() as $competition) 
            {
                $entityManager->remove($competition);
            }
            $entityManager->remove($saison);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_saison_index', [], Response::HTTP_SEE_OTHER);
    }
}
