<?php

namespace App\Controller;
use App\Entity\ChoixSondage;
use App\Entity\Sondage;
use App\Entity\User;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Bundle\SecurityBundle\Security;

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

    #[Route('/supprimer/{id}', name: 'app_reponse_supprimer', methods: ['POST'])]
public function supprimer(int $id, EntityManagerInterface $entityManager): JsonResponse
{
    // Récupérer la réponse à supprimer
    $reponse = $entityManager->getRepository(Reponse::class)->find($id);
    
    if (!$reponse) {
        return new JsonResponse(['status' => 'error', 'message' => 'Réponse non trouvée.'], 404);
    }
    
    // Récupérer l'utilisateur connecté
    //$user = $security->getUser();
    $user = $entityManager->getRepository(User::class)->find(1);
    // Vérifier si l'utilisateur de la session est celui qui a créé la réponse
    if ($user !== $reponse->getUser()) {
        return new JsonResponse(['status' => 'error', 'message' => 'Vous n\'êtes pas autorisé à supprimer cette réponse.'], 403);
    }
    
    // Suppression de la réponse
    $entityManager->remove($reponse);
    $entityManager->flush();
    
    return new JsonResponse(['status' => 'success', 'message' => 'Réponse supprimée avec succès.'], 200);
}
#[Route('/sup/{sondageId}', name: 'delRep', methods: ['POST', 'DELETE'])]
public function supprimerVoteParSondage(int $sondageId, EntityManagerInterface $entityManager,        Security $security,
): RedirectResponse
{
    // Récupérer l'utilisateur connecté
    $user = $security->getUser();
    
    // Vérifier si l'utilisateur existe
    if (!$user) {
        // Retourner un message d'erreur sous forme de redirection si l'utilisateur n'est pas trouvé
        $this->addFlash('error', 'Utilisateur non trouvé.');
        return $this->redirectToRoute('app_sondages');
    }
    
    // Récupérer le sondage avec ses choix
    $sondage = $entityManager->getRepository(Sondage::class)->find($sondageId);
    
    // Vérifier si le sondage existe
    if (!$sondage) {
        // Retourner un message d'erreur sous forme de redirection si le sondage n'est pas trouvé
        $this->addFlash('error', 'Sondage non trouvé.');
        return $this->redirectToRoute('app_sondages');
    }
    
    // Vérifier si une réponse existe pour cet utilisateur et ce sondage
    foreach ($sondage->getChoix() as $choix) {
        $reponse = $entityManager->getRepository(Reponse::class)
                                 ->findOneBy([
                                     'choixSondage' => $choix,
                                     'user' => $user
                                 ]);
    
        // Si une réponse est trouvée, la supprimer
        if ($reponse) {
            $entityManager->remove($reponse);
            $entityManager->flush();
            
            // Ajout d'un message flash de succès avant de rediriger
            $this->addFlash('success', 'Vote supprimé avec succès.');
            return $this->redirectToRoute('app_sondage_index');
        }
    }
    
    // Si aucune réponse n'est trouvée, ajouter un message d'erreur
    $this->addFlash('error', 'Aucun vote trouvé pour cet utilisateur dans ce sondage.');
    return $this->redirectToRoute('app_sondage_index');
}




//mouch hedhii!!!

#[Route('/{sondageId}/voter', name: 'submit_vote', methods: ['POST'])]
public function submitVote(int $sondageId, Request $request, EntityManagerInterface $entityManager,        Security $security,
): JsonResponse
{
    // Récupérer le sondage par ID
    $sondage = $entityManager->getRepository(Sondage::class)->find($sondageId);
    $user = $security->getUser();
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

    return new JsonResponse(['status' => 'success', 'message' => 'Response added']);
}





#[Route('/ajouter/{id}', name: 'app_reponse_ajouter', methods: ['POST'])]
public function ajouter(int $id, Request $request, EntityManagerInterface $entityManager,        Security $security,
): JsonResponse 
{
    // Récupérer l'utilisateur (en attendant une authentification, on fixe l'ID à 1)
    $user = $security->getUser();

    if (!$user) {
        return new JsonResponse(['status' => 'error', 'message' => 'Utilisateur non trouvé.'], 404);
    }

    // Récupérer le sondage à partir de l'ID de la route
    $sondage = $entityManager->getRepository(Sondage::class)->find($id);
    
    if (!$sondage) {
        return new JsonResponse(['status' => 'error', 'message' => 'Sondage non trouvé.'], 404);
    }

    // Récupérer l'option choisie
    $choixId = $request->request->get('choixSondage');
    if (!$choixId) {
        return new JsonResponse(['status' => 'error', 'message' => 'L\'ID du choix est manquant.'], 400);
    }

    // Récupérer le choix du sondage
    $choixSondage = $entityManager->getRepository(ChoixSondage::class)->find($choixId);

    if (!$choixSondage || $choixSondage->getSondage()->getId() !== $id) {
        return new JsonResponse(['status' => 'error', 'message' => 'Choix invalide pour ce sondage.'], 404);
    }

    // Vérifier si l'utilisateur a déjà voté pour ce sondage
    $existingVote = $entityManager->getRepository(Reponse::class)->findOneBy([
        'user' => $user,
        'sondage' => $sondage
    ]);

    // Si un vote existe déjà, on le supprime et on crée un nouveau vote
    if ($existingVote) {
        $entityManager->remove($existingVote);  // Suppression de l'ancien vote
        $entityManager->flush();  // Sauvegarde de la suppression
    }

    // Enregistrer la nouvelle réponse
    $reponse = new Reponse();
    $reponse->setUser($user);
    $reponse->setChoixSondage($choixSondage);
    $reponse->setDateReponse(new \DateTime());
    $reponse->setSondage($sondage); // Associer la réponse au sondage

    $entityManager->persist($reponse);  // Sauvegarde du nouveau vote
    $entityManager->flush();

    return new JsonResponse(['status' => 'success', 'message' => ' Response added.'], 200);
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