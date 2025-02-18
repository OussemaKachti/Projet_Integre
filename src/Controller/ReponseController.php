<?php

namespace App\Controller;
use App\Entity\ChoixSondage;
use App\Entity\Sondage;
use App\Entity\User;
use Symfony\Component\HttpFoundation\JsonResponse;

use App\Entity\Reponse;
use App\Form\ReponseType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/reponse')]
class ReponseController extends AbstractController
{

// src/Controller/PollController.php

#[Route('/{sondageId}/voter', name: 'submit_vote', methods: ['POST'])]
public function submitVote(int $sondageId, Request $request, EntityManagerInterface $entityManager): JsonResponse
{
    // Récupérer le sondage par ID
    $sondage = $entityManager->getRepository(Sondage::class)->find($sondageId);
    $user = $entityManager->getRepository(User::class)->find(1); // Remplacez par l'utilisateur authentifié
    if (!$sondage) {
        return new JsonResponse(['status' => 'error', 'message' => 'Sondage non trouvé.'], 404);
    }

    // Récupérer l'ID du choix sélectionné
    $choixId = $request->request->get('choix');
    $choix = $entityManager->getRepository(ChoixSondage::class)->find($choixId);

    if (!$choix) {
        return new JsonResponse(['status' => 'error', 'message' => 'Choix invalide.'], 400);
    }

    // Vérifier si l'utilisateur a déjà répondu à ce sondage (quel que soit le choix)
    $existingReponse = $entityManager->getRepository(Reponse::class)
                                     ->findOneBy(['user' => $user, 'choixSondage' => $choix]);

    if ($existingReponse) {
        // Si une réponse existe déjà pour ce choix, on la supprime
        $entityManager->remove($existingReponse);
        $entityManager->flush();
    }

    // Vérifier s'il existe déjà une réponse pour l'utilisateur dans ce sondage (tous les choix)
    $existingReponseForSondage = $entityManager->getRepository(Reponse::class)
                                               ->findOneBy(['user' => $user, 'choixSondage' => $choix->getSondage()]);

    if ($existingReponseForSondage) {
        // Si une réponse existe déjà dans ce sondage (tous choix confondus), on la supprime
        $entityManager->remove($existingReponseForSondage);
        $entityManager->flush();
    }

    // Créer une nouvelle réponse avec le choix sélectionné
    $reponse = new Reponse();
    $reponse->setChoixSondage($choix);
    $reponse->setUser($user); // Assurez-vous que l'utilisateur est authentifié
    $reponse->setDateReponse(new \DateTime());

    // Sauvegarder la nouvelle réponse
    $entityManager->persist($reponse);
    $entityManager->flush();

    return new JsonResponse(['status' => 'success', 'message' => 'Votre vote a été enregistré.']);
}





    #[Route('/ajouter/{id}', name: 'app_reponse_ajouter', methods: ['POST'])]
public function ajouter( int $id, Request $request, EntityManagerInterface $entityManager ): JsonResponse 
{
    // Récupérer l'utilisateur (en attendant une authentification, on fixe l'ID à 1)
    $user = $entityManager->getRepository(User::class)->find(1);

    if (!$user) {
        return new JsonResponse(['status' => 'error', 'message' => 'Utilisateur non trouvé.'], 404);
    }

    // Récupérer l'option choisie
    $choixId = $request->request->get('choixSondage');
    if (!$choixId) {
        return new JsonResponse(['status' => 'error', 'message' => 'L\'ID du choix est manquant.'], 400);
    }

    // Debug : afficher l'ID du choix
    dump($choixId); // ou var_dump($choixId);

    $choixSondage = $entityManager->getRepository(ChoixSondage::class)->find($choixId);

    if (!$choixSondage) {
        return new JsonResponse(['status' => 'error', 'message' => 'Choix invalide.'], 404);
    }

    // Vérifier si l'utilisateur a déjà voté pour ce sondage
    $sondage = $choixSondage->getSondage();
    $existingVote = $entityManager->getRepository(Reponse::class)->findOneBy([
        'user' => $user,
        'choixSondage' => $choixSondage
    ]);

    if ($existingVote) {
        return new JsonResponse(['status' => 'warning', 'message' => 'Vous avez déjà voté pour ce sondage.'], 409);
    }

    // Enregistrer la réponse
    $reponse = new Reponse();
    $reponse->setUser($user);
    $reponse->setChoixSondage($choixSondage);
    $reponse->setDateReponse(new \DateTime());

    $entityManager->persist($reponse);
    $entityManager->flush();

    return new JsonResponse(['status' => 'success', 'message' => 'Votre vote a été enregistré.'], 200);
}


#[Route('/supprimer/{id}', name: 'app_reponse_supprimer', methods: ['DELETE'])]
public function supprimer(int $id, EntityManagerInterface $entityManager): JsonResponse
{
    // Récupérer la réponse à supprimer
    $reponse = $entityManager->getRepository(Reponse::class)->find($id);
    
    if (!$reponse) {
        return new JsonResponse(['status' => 'error', 'message' => 'Réponse non trouvée.'], 404);
    }
    
    // Suppression de la réponse
    $entityManager->remove($reponse);
    $entityManager->flush();
    
    return new JsonResponse(['status' => 'success', 'message' => 'Réponse supprimée avec succès.'], 200);
}

#[Route('/{sondageId}', name: 'app_consulter_reponses', methods: ['GET'])]
public function consulterReponses(int $sondageId, EntityManagerInterface $entityManager): JsonResponse
{
    // Récupérer les choix de sondage associés à ce sondage
    $choixSondages = $entityManager->getRepository(ChoixSondage::class)->findBy(['sondage' => $sondageId]);
    
    if (!$choixSondages) {
        return new JsonResponse(['status' => 'error', 'message' => 'Sondage ou choix non trouvé.'], 404);
    }

    // Récupérer toutes les réponses pour ces choix de sondage
    $reponses = $entityManager->getRepository(Reponse::class)->findBy(['choixSondage' => $choixSondages]);

    // Préparer les données à retourner
    $reponsesData = [];
    foreach ($reponses as $reponse) {
        $reponsesData[] = [
            'user' => $reponse->getUser()->getNom() . ' ' . $reponse->getUser()->getPrenom(),
            'choix' => $reponse->getChoixSondage()->getContenu(),
            'dateReponse' => $reponse->getDateReponse()->format('d M Y'),
        ];
    }

    return new JsonResponse([
        'status' => 'success',
        'reponses' => $reponsesData
    ]);
}















#[Route('/supprimer/vote/{sondageId}', name: 'app_reponse_supprimer_par_sondage', methods: ['DELETE'])]
public function supprimerVoteParSondage(int $sondageId, EntityManagerInterface $entityManager): JsonResponse
{
    // Récupérer l'utilisateur connecté
    $utilisateur = $entityManager->getRepository(User::class)->find(1);

    // Vérifier si l'utilisateur existe
    if (!$utilisateur) {
        return new JsonResponse(['status' => 'error', 'message' => 'Utilisateur non trouvé.'], 404);
    }

    // Récupérer le sondage avec ses choix
    $sondage = $entityManager->getRepository(Sondage::class)->find($sondageId);

    // Vérifier si le sondage existe
    if (!$sondage) {
        return new JsonResponse(['status' => 'error', 'message' => 'Sondage non trouvé.'], 404);
    }

    // Vérifier si une réponse existe pour cet utilisateur et ce sondage
    foreach ($sondage->getChoix() as $choix) {
        $reponse = $entityManager->getRepository(Reponse::class)
                                 ->findOneBy([
                                     'choixSondage' => $choix,
                                     'utilisateur' => $utilisateur
                                 ]);

        // Si une réponse est trouvée, la supprimer
        if ($reponse) {
            $entityManager->remove($reponse);
            $entityManager->flush();
            return new JsonResponse(['status' => 'success', 'message' => 'Vote supprimé avec succès.'], 200);
        }
    }

    // Si aucune réponse n'est trouvée
    return new JsonResponse(['status' => 'error', 'message' => 'Aucun vote trouvé pour cet utilisateur dans ce sondage.'], 404);
}
    
    #[Route('/', name: 'app_reponse_index', methods: ['GET'])]
    public function index(EntityManagerInterface $entityManager): Response
    {
        $reponses = $entityManager
            ->getRepository(Reponse::class)
            ->findAll();

        return $this->render('reponse/index.html.twig', [
            'reponses' => $reponses,
        ]);
    }

    #[Route('/new', name: 'app_reponse_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $reponse = new Reponse();
        $form = $this->createForm(ReponseType::class, $reponse);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($reponse);
            $entityManager->flush();

            return $this->redirectToRoute('app_reponse_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('reponse/new.html.twig', [
            'reponse' => $reponse,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_reponse_show', methods: ['GET'])]
    public function show(Reponse $reponse): Response
    {
        return $this->render('reponse/show.html.twig', [
            'reponse' => $reponse,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_reponse_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Reponse $reponse, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(ReponseType::class, $reponse);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_reponse_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('reponse/edit.html.twig', [
            'reponse' => $reponse,
            'form' => $form,
        ]);
    }

    

}