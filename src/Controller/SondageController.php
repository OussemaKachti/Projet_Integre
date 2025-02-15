<?php

namespace App\Controller;
use App\Enum\RoleEnum;

use App\Entity\Sondage;
use App\Form\SondageType;
use App\Entity\Reponse;
use App\Repository\ClubRepository;

use App\Repository\SondageRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Entity\ChoixSondage;
use Symfony\Component\Security\Core\User\UserInterface;
use App\Entity\User;  // Assurez-vous d'importer votre entité User
use App\Entity\ParticipationMembre;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\HttpFoundation\JsonResponse;




#[Route('/sondage')]
class SondageController extends AbstractController
{

    private $entityManager;

    // Injection de dépendance du EntityManagerInterface
    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }
    #[Route('/ListPolls', name: 'app_sondage_index', methods: ['GET'])]
    public function index(SondageRepository $sondageRepository): Response
    {
        return $this->render('sondage/ListPolls.html.twig', [
            'sondages' => $sondageRepository->findAll(),
        ]);
    }
/*
public function create(Request $request, EntityManagerInterface $em): Response
{
    // Récupérer l'utilisateur authentifié via la session
    $user = $this->getUser();

    if (!$user) {
        // Si l'utilisateur n'est pas connecté, simuler un utilisateur avec l'ID 1
        $user = $this->getDoctrine()->getRepository(User::class)->find(1);
        $this->get('security.token_storage')->setToken($this->get('security.authentication.manager')->createToken($user));  // Simuler la connexion
    }

    if (!$user || !$user instanceof User) {
        return $this->render('sondage/error.html.twig', [
            'message' => 'User not authenticated'
        ]);
    }

    // Créer un nouveau sondage
    $sondage = new Sondage();
    $form = $this->createForm(SondageType::class, $sondage);
    $form->handleRequest($request);

    if ($form->isSubmitted() && $form->isValid()) {
        // Associer l'utilisateur authentifié au sondage
        $sondage->setUser($user);

        // Persister le sondage et ses choix
        foreach ($sondage->getChoix() as $choix) {
            $choix->setSondage($sondage);
        }

        $em->persist($sondage);
        $em->flush();

        // Rediriger vers la liste des sondages
        return $this->redirectToRoute('poll_list');
    }

    return $this->render('sondage/create.html.twig', [
        'form' => $form->createView(),
    ]);
} 
*/
// Récupérer l'utilisateur authentifié via la session
    /*$user = $this->getUser();

    if (!$user || !$user instanceof User) {
        // Si l'utilisateur n'est pas authentifié ou n'est pas une instance de User, retourner une erreur
        return $this->render('sondage/error.html.twig', [
            'message' => 'User not authenticated or invalid user type'
        ]);
    }*/    
    #[Route('/api/poll/new', name: 'api_poll_new', methods: ['POST'])]
    public function createPoll(Request $request, EntityManagerInterface $em): JsonResponse
    {
        // Décoder le JSON envoyé dans le corps de la requête
        $data = json_decode($request->getContent(), true);
        
        if (!$data || !isset($data['question']) || empty($data['choix'])) {
            return new JsonResponse(['status' => 'error', 'message' => 'Données invalides'], 400);
        }
    
        // Créer un nouvel objet Sondage
        $sondage = new Sondage();
        $sondage->setQuestion($data['question']);
        $sondage->setCreatedAt(new \DateTime());
        
        // Récupérer l'utilisateur avec l'ID 1
        $user = $em->getRepository(User::class)->find(1);
        if (!$user) {
            return new JsonResponse(['status' => 'error', 'message' => 'Utilisateur avec ID 1 non trouvé'], 404);
        }
        $sondage->setUser($user);
    
        // Ajouter les choix
        foreach ($data['choix'] as $choixData) {
            if (!isset($choixData['contenu']) || empty($choixData['contenu'])) {
                return new JsonResponse(['status' => 'error', 'message' => 'Un choix est vide'], 400);
            }
            $choix = new ChoixSondage();
            $choix->setContenu($choixData['contenu']);
            $choix->setSondage($sondage);
            $em->persist($choix);
        }
    
        $em->persist($sondage);
        $em->flush();
    
        return new JsonResponse(['status' => 'success', 'message' => 'Sondage créé avec succès'], 201);
    }


    

    #[Route('/sondages', name: 'app_sondages')]
    public function getPollsByClub(EntityManagerInterface $em, SondageRepository $sondageRepository, ClubRepository $clubRepository): Response
    {
        // 🔹 Récupérer l'utilisateur connecté (Mettre en dur pour test uniquement)
        $user = $em->getRepository(User::class)->find(2); // ⚠️ À retirer en production et remplacer par `$this->getUser()`

        if (!$user) {
            throw $this->createAccessDeniedException('You should connect to see all polls');
        }

        // 🔹 Trouver le club dont il est membre (via une requête explicite)
        $club = $clubRepository->createQueryBuilder('c')
            ->join('c.membres', 'm') // Jointure sur les membres du club
            ->where('m.id = :userId') // Vérifier si l'utilisateur est un membre
            ->setParameter('userId', $user->getId())
            ->getQuery()
            ->getOneOrNullResult();

        if (!$club) {
            return $this->render('sondage/listePolls.html.twig', ['sondages' => []]);
        }

        // 🔹 Vérifier l'existence d'un président du club
        $president = $club->getPresident();

        if (!$president) {
            return $this->render('sondage/listePolls.html.twig', ['sondages' => []]);
        }

        // 🔹 Récupérer uniquement les sondages créés par le président du club
        $sondages = $sondageRepository->findBy(
            ['user' => $president], 
            ['createdAt' => 'DESC']
        );

        return $this->render('sondage/listePolls.html.twig', [
            'sondages' => $sondages,
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

    #[Route('/delete/{id}', name: 'delete_survey', methods: ['DELETE'])]
    public function deleteSurvey(int $id, Request $request, EntityManagerInterface $em): Response
    {
        $s = $em->getRepository(Sondage::class)->find($id);
    
        if (!$s) {
            return new JsonResponse(['error' => 'Survey not found'], Response::HTTP_NOT_FOUND);
        }
    
        // 🔹 Lire l'ID utilisateur depuis la requête JSON
        $data = json_decode($request->getContent(), true);
        $userId = $data['user_id'] ?? null;
    
        if (!$userId) {
            return new JsonResponse(['error' => 'User ID is required'], Response::HTTP_BAD_REQUEST);
        }
    
        // 🔹 Récupérer l'utilisateur depuis la base de données
        $user = $em->getRepository(User::class)->find($userId);
    
        if (!$user) {
            return new JsonResponse(['error' => 'User not found'], Response::HTTP_NOT_FOUND);
        }
    
        // 🔹 Supprimer le sondage
        $em->remove($s);
        $em->flush();
    
        return new JsonResponse(['message' => 'Survey successfully deleted'], Response::HTTP_OK);
    }
    
    
    


}