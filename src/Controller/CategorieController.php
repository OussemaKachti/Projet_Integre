<?php

namespace App\Controller;

use App\Entity\Categorie;
use App\Form\CategorieType;
use App\Repository\CategorieRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/categorie')]
class CategorieController extends AbstractController
{
    #[Route('/', name: 'app_categorie_index', methods: ['GET', 'POST'])]
public function index(Request $request, EntityManagerInterface $entityManager, CategorieRepository $categorieRepository): Response
{
    $categorie = new Categorie();
    $form = $this->createForm(CategorieType::class, $categorie);
    $form->handleRequest($request);

    if ($form->isSubmitted() && $form->isValid()) {
        $entityManager->persist($categorie);
        $entityManager->flush();

        // Rediriger vers la création d'événement avec la nouvelle catégorie sélectionnée
        return $this->redirectToRoute('app_evenement_new', ['newCategory' => $categorie->getId()]);
    }

    // Récupérer toutes les catégories existantes
    $categories = $categorieRepository->findAll();

    return $this->render('categorie/show.html.twig', [
        'categories' => $categories,
        'form' => $form->createView(),
    ]);
}


 

    #[Route('/{id}', name: 'app_categorie_show', methods: ['GET'])]
    public function show(Categorie $categorie): Response
    {
        return $this->render('categorie/show.html.twig', [
            'categorie' => $categorie,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_categorie_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Categorie $categorie, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(CategorieType::class, $categorie);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_categorie_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('categorie/edit.html.twig', [
            'categorie' => $categorie,
            'form' => $form,
        ]);
    }

 

#[Route('/admin/{id}/delete', name: 'app_categorie_delete', methods: ['POST'])]

public function delete(Request $request, EntityManagerInterface $entityManager, int $id): Response
{
    $categorie = $entityManager->getRepository(Categorie::class)->find($id);


    if ($this->isCsrfTokenValid('delete' . $categorie->getId(), $request->request->get('_token'))) {
        $entityManager->remove($categorie);
        $entityManager->flush();
        $this->addFlash('success', 'Categorie supprimé avec succès.');
    }

    return $this->redirectToRoute('admin_page'); // Redirection vers la liste des événements
}}
