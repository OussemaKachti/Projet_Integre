<?php

namespace App\Controller;
use App\Enum\RoleEnum;
use App\Entity\Club;

use App\Entity\Sondage;
use App\Form\SondageType;
use App\Entity\Reponse;
use App\Repository\ClubRepository;
use App\Repository\UserRepository;

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
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Security\Core\Security;




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
    public function index(SondageRepository $sondageRepository,EntityManagerInterface $entityManager): Response
    {
        $sondages = $sondageRepository->findAll();
        $user = $entityManager->getRepository(User::class)->find(1); // Utilisateur fictif pour le test
    
        $reponses = [];
    
        if ($user) {
            foreach ($sondages as $sondage) {
                $reponse = $entityManager->getRepository(Reponse::class)->findOneBy([
                    'sondage' => $sondage,
                    'user' => $user
                ]);
    
                if ($reponse) {
                    $reponses[$sondage->getId()] = $reponse->getChoixSondage()->getContenu();
                }
            }
        }
    
        return $this->render('sondage/ListPolls.html.twig', [
            'sondages' => $sondages,
            'reponses' => $reponses // On passe les réponses à Twig
        ]);
    }



                                        // ADMINNNN



    
    #[Route('/adminPolls', name: 'app_sondage_index2', methods: ['GET'])]
    public function index2(SondageRepository $sondageRepository, EntityManagerInterface $em): Response
    {
        // Récupérer tous les sondages
        $sondages = $sondageRepository->findAll();
    
        // Tableau pour stocker les sondages avec le nom du club
        $sondagesAvecClub = [];
    
        // Pour chaque sondage, récupérer le club du président
        foreach ($sondages as $sondage) {
            // Récupérer l'utilisateur qui a créé le sondage (président du club)
            $user = $sondage->getUser();  // Assurez-vous que 'getUser' récupère bien l'utilisateur qui a créé le sondage
    
            // Récupérer le club du président (user)
            $club = $em->getRepository(Club::class)->findOneBy(['president' => $user]);
    
            if ($club) {
                // Ajouter le nom du club au sondage
                $sondagesAvecClub[] = [
                    'sondage' => $sondage,
                    'club_name' => $club->getNomC() // Assurez-vous que 'getNomC' existe pour obtenir le nom du club
                ];
            }
        }
    
        // Passer les sondages avec le nom du club à la vue
        return $this->render('sondage/adminPolls.html.twig', [
            'sondages' => $sondagesAvecClub,
        ]);
    }
    

    #[Route('/{id}/delete', name: 'app_sondage_delete', methods: ['POST'])]
    public function deleteSondage($id, EntityManagerInterface $entityManager): JsonResponse
    {
        try {
            // Récupérer le sondage à supprimer avec ses relations
            $sondage = $entityManager->getRepository(Sondage::class)->find($id);
    
            // Vérifier si le sondage existe
            if (!$sondage) {
                return new JsonResponse([
                    'success' => false,
                    'message' => 'Sondage non trouvé.'
                ], 404);
            }
    
            // Stocker les informations avant suppression
            $sondageId = $sondage->getId();
            $sondageQuestion = $sondage->getQuestion();
    
            // Charger explicitement les collections
            $sondage->getChoix()->initialize();
            $sondage->getCommentaires()->initialize();
            $sondage->getReponses()->initialize();
    
            // Supprimer le sondage
            $entityManager->remove($sondage);
            $entityManager->flush();
    
            return new JsonResponse([
                'success' => true,
                'message' => 'Sondage supprimé avec succès.',
                'data' => [
                    'id' => $sondageId,
                    'question' => $sondageQuestion
                ]
            ], 200);
    
        } catch (\Exception $e) {
            // Log l'erreur pour le débogage
            error_log($e->getMessage());
            error_log($e->getTraceAsString());
    
            return new JsonResponse([
                'success' => false,
                'message' => 'Erreur lors de la suppression du sondage: ' . $e->getMessage(),
                'details' => $e->getTraceAsString()
            ], 500);
        }
    }




    
    
    /*
    #[Route('/admin/polls', name: 'app_poll_index4')]
public function filterByClub(Request $request, EntityManagerInterface $entityManager): Response
{
    // Récupérer tous les clubs pour le filtre
    $clubs = $entityManager->getRepository(Club::class)->findAll();

    // Récupérer le nom du club sélectionné depuis la requête
    $clubFilter = $request->query->get('club'); // nom du club

    // Construire la requête pour récupérer les sondages
    $queryBuilder = $entityManager->getRepository(Sondage::class)->createQueryBuilder('s')
        ->join('s.club', 'cl')  // Utilisation de 'join' pour relier Sondage à Club
        ->addSelect('cl');       // Ajout de 'cl' pour récupérer les informations sur le club

    // Appliquer le filtre si un club est sélectionné
    if ($clubFilter && $clubFilter !== 'all') {
        $queryBuilder->where('cl.nomC = :clubName')  // Utiliser 'nomC' pour la propriété correcte
            ->setParameter('clubName', $clubFilter);
    }

    // Exécuter la requête
    $sondages = $queryBuilder->getQuery()->getResult();

    // Formater les sondages avec les informations nécessaires
    $sondagesAvecClub = [];
    foreach ($sondages as $sondage) {
        $club = $sondage->getClub();
        $clubName = $club ? $club->getNomC() : 'Non défini'; // Utiliser getNomC() pour obtenir le nom du club

        // Ajouter le sondage au tableau avec les informations nécessaires
        $sondagesAvecClub[] = [
            'id' => $sondage->getId(),
            'question' => $sondage->getQuestion(),
            'club_name' => $clubName,
            'created_at' => $sondage->getCreatedAt()->format('Y-m-d'),
        ];
    }

    // Retourner la vue avec les sondages et la liste des clubs
    return $this->render('sondage/adminPolls.html.twig', [
        'sondages' => $sondagesAvecClub,
        'clubs' => $clubs,  // Liste des clubs pour le filtre
        'selectedClub' => $clubFilter ?? 'all', // Club actuellement sélectionné
    ]);
}
*/
    
    




    



                                            // FIN ADMINN


                                            #[Route('/{sondageId}/reponse', name: 'get_user_response_for_sondage', methods: ['GET'])]
                                            public function getUserResponseForSondage(int $sondageId, EntityManagerInterface $entityManager)
                                            {
                                                // Créer un utilisateur fictif pour le test
                                                $user = $entityManager->getRepository(User::class)->find(1); // Par exemple, récupérer un utilisateur avec l'ID 1
                                        
                                                // Vérifier si l'utilisateur existe
                                                if (!$user) {
                                                    return new JsonResponse(['status' => 'error', 'message' => 'Utilisateur non trouvé.'], 404);
                                                }
                                        
                                                // Récupérer le sondage
                                                $sondage = $entityManager->getRepository(Sondage::class)->find($sondageId);
                                        
                                                // Vérifier si le sondage existe
                                                if (!$sondage) {
                                                    throw $this->createNotFoundException('Sondage non trouvé.');
                                                }
                                        
                                                // Récupérer la réponse de l'utilisateur pour ce sondage
                                                $reponse = $entityManager->getRepository(Reponse::class)->findOneBy([
                                                    'sondage' => $sondage,
                                                    'user' => $user
                                                ]);
                                        
                                                // Si une réponse est trouvée, renvoyer l'objet réponse
                                                if ($reponse) {
                                                    return new JsonResponse([
                                                        'status' => 'success',
                                                        'reponse' => [
                                                            'id' => $reponse->getId(),
                                                            'choix' => [
                                                                'id' => $reponse->getChoixSondage()->getId(),
                                                                'contenu' => $reponse->getChoixSondage()->getContenu()
                                                            ]
                                                        ]
                                                    ]);
                                                }
                                        
                                                // Si aucune réponse n'est trouvée
                                                return new JsonResponse(['status' => 'error', 'message' => 'Aucune réponse trouvée pour cet utilisateur.'], 404);
                                            }



    
    #[Route('/allPolls', name: 'allPolls', methods: ['GET'])]
    public function index3(SondageRepository $sondageRepository): Response
    {
        // Envoi de la liste des sondages à la vue
        return $this->render('sondage/allPolls.html.twig', [
            'polls' => $sondageRepository->findAll(), // Correction du nom de la variable ici
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
    // Décoder le JSON
    $data = json_decode($request->getContent(), true);
    
    if (!$data || !isset($data['question']) || empty($data['choix'])) {
        return new JsonResponse(['status' => 'error', 'message' => 'Données invalides'], 400);
    }

    // Récupérer l'utilisateur
    $user = $em->getRepository(User::class)->find(1);
    if (!$user) {
        return new JsonResponse(['status' => 'error', 'message' => 'Utilisateur avec ID 1 non trouvé'], 404);
    }

    // Récupérer le club où l'utilisateur est président
    $club = $em->getRepository(Club::class)->findOneBy(['president' => $user->getId()]);
    if (!$club) {
        return new JsonResponse(['status' => 'error', 'message' => 'Aucun club trouvé pour cet utilisateur en tant que président'], 403);
    }

    // Créer le sondage
    $sondage = new Sondage();
    $sondage->setQuestion($data['question']);
    $sondage->setCreatedAt(new \DateTime());
    $sondage->setUser($user);
    $sondage->setClub($club);

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

    try {
        $em->persist($sondage);
        $em->flush();
        return new JsonResponse([
            'status' => 'success', 
            'message' => 'Sondage créé avec succès',
            'club_name' => $club->getNomC()
        ], 201);
    } catch (\Exception $e) {
        return new JsonResponse([
            'status' => 'error', 
            'message' => 'Erreur lors de la création du sondage',
            'error' => $e->getMessage()
        ], 500);
    }
}
    


#[Route('/delete1/{id}', name: 'app_sondage_delete', methods: ['POST', 'DELETE'])]
public function deleteSondage1(int $id, EntityManagerInterface $entityManager): JsonResponse
{
    try {
        $sondage = $entityManager->getRepository(Sondage::class)->find($id);

        if (!$sondage) {
            return new JsonResponse([
                'status' => 'error',
                'message' => 'Sondage non trouvé'
            ], 404);
        }

        // Vérifier si l'utilisateur actuel est le créateur du sondage
        $user = $entityManager->getRepository(User::class)->find(1); // À remplacer par l'utilisateur connecté
        if ($sondage->getUser() !== $user) {
            return new JsonResponse([
                'status' => 'error',
                'message' => 'Vous n\'êtes pas autorisé à supprimer ce sondage'
            ], 403);
        }

        $entityManager->remove($sondage);
        $entityManager->flush();

        return new JsonResponse([
            'status' => 'success',
            'message' => 'Sondage supprimé avec succès'
        ]);

    } catch (\Exception $e) {
        return new JsonResponse([
            'status' => 'error',
            'message' => 'Erreur lors de la suppression du sondage : ' . $e->getMessage()
        ], 500);
    }
}

    

    #[Route('/sondages', name: 'app_sondages')]
    public function getPollsByClub(EntityManagerInterface $em, SondageRepository $sondageRepository, ClubRepository $clubRepository): Response
    {
        // 🔹 Récupérer l'utilisateur connecté (Mettre en dur pour test uniquement)
        $user = $em->getRepository(User::class)->find(1); // ⚠️ À retirer en production et remplacer par `$this->getUser()`

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
            return $this->render('sondage/listPolls.html.twig', ['sondages' => []]);
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

    
    #[Route('/AllPolls', name: 'api_user_polls', methods: ['GET'])]
    public function getUserPolls(EntityManagerInterface $em): Response
    {
        // Récupérer l'utilisateur connecté
        $user = $em->getRepository(User::class)->find(1);

    
        // Vérifier si l'utilisateur est connecté
        if (!$user) {
            return $this->redirectToRoute('login'); // Rediriger si l'utilisateur n'est pas connecté
        }
    
        // Récupérer les sondages créés par l'utilisateur
        $sondages = $em->getRepository(Sondage::class)->findBy(['user' => $user]);
    
        // Vérifier si l'utilisateur a créé des sondages
        if (empty($sondages)) {
            $this->addFlash('error', 'Aucun sondage trouvé pour cet utilisateur');
        }
    
        // Renvoyer la vue avec les sondages
        return $this->render('sondage/AllPolls.html.twig', [
            'sondages' => $sondages,
        ]);
    }



    //tekhdemch
    #[Route('/mes-sondages', name: 'app_sondage_user', methods: ['GET'])]
public function getUserSondages(EntityManagerInterface $em, SondageRepository $sondageRepository): Response
{
    // Récupérer l'utilisateur connecté
    //$user = $this->getUser();
    $user = $em->getRepository(User::class)->find(1);

    // Vérifier si l'utilisateur est bien connecté
    if (!$user) {
        return $this->render('sondage/allPolls.html.twig', [
            'error' => 'Utilisateur non connecté.'
        ]);
    }

    // Récupérer les sondages de cet utilisateur
    $sondages = $sondageRepository->findSondagesByUser($user);

    // Transformer les sondages en tableau pour Twig
    $sondageData = array_map(function (Sondage $sondage) {
        return [
            'id' => $sondage->getId(),
            'question' => $sondage->getQuestion(),
            'date_creation' => $sondage->getCreatedAt()->format('Y-m-d H:i:s'),
            'choix' => array_map(fn($choix) => [
                'id' => $choix->getId(),
                'contenu' => $choix->getContenu()
            ], $sondage->getChoix()->toArray())
        ];
    }, $sondages);

    // Passer les données à la vue
    return $this->render('sondage/allPolls.html.twig', [
        'sondageData' => $sondageData
    ]);
}


    


    #[Route('/{id}', name: 'app_sondage_show', methods: ['GET'])]
    public function show(Sondage $sondage): Response
    {
        return $this->render('sondage/show.html.twig', [
            'sondage' => $sondage,
        ]);
    }





    #[Route('/delete/{id}', name: 'delete_survey', methods: ['POST'])]
public function deleteSurvey(int $id, Request $request, EntityManagerInterface $em): Response
{
    try {
        // Vérifier si le sondage existe
        $sondage = $em->getRepository(Sondage::class)->find($id);
        
        // Si le sondage n'est pas trouvé, renvoyer une erreur 404
        if (!$sondage) {
            return new JsonResponse(['error' => 'Sondage not found'], Response::HTTP_NOT_FOUND);
        }

        // Utiliser l'utilisateur avec l'ID 1 pour tester
        $user = $em->getRepository(User::class)->find(1);  // Utilisateur statique pour tester
        
        // Vérifier si l'utilisateur existe
        if (!$user) {
            return new JsonResponse(['error' => 'User not found'], Response::HTTP_NOT_FOUND);
        }

        // Vérifier que l'utilisateur est bien le propriétaire du sondage
        if ($sondage->getUser()->getId() !== $user->getId()) {
            return new JsonResponse(['error' => 'You are not authorized to delete this survey'], Response::HTTP_FORBIDDEN);
        }

        // Supprimer manuellement les réponses liées à ce sondage
        $reponses = $em->getRepository(Reponse::class)->findBy(['sondage' => $sondage]);
        foreach ($reponses as $reponse) {
            $em->remove($reponse);
        }
        
        // Supprimer le sondage
        $em->remove($sondage);
        $em->flush();

        // Retourner une réponse simple après la suppression
        return new JsonResponse(['message' => 'Survey successfully deleted'], Response::HTTP_OK);
    } catch (\Exception $e) {
        return new JsonResponse([
            'error' => 'An error occurred: ' . $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ], Response::HTTP_INTERNAL_SERVER_ERROR);
    }
}

    

#[Route('/api/poll/{id}', name: 'api_poll_show', methods: ['GET'])]
public function showPoll(int $id, EntityManagerInterface $em): JsonResponse
{
    // Récupérer le sondage par ID
    $sondage = $em->getRepository(Sondage::class)->find($id);

    // Vérifier si le sondage existe
    if (!$sondage) {
        return new JsonResponse(['status' => 'error', 'message' => 'Sondage non trouvé'], 404);
    }

    // Récupérer les choix du sondage
    $choix = $sondage->getChoix();

    // Retourner les données du sondage (question et choix)
    return new JsonResponse([
        'status' => 'success',
        'sondage' => [
            'id' => $sondage->getId(),
            'question' => $sondage->getQuestion(),
            'choix' => array_map(fn($choix) => ['id' => $choix->getId(), 'contenu' => $choix->getContenu()], $choix->toArray())
        ]
    ]);
}

    
    
  
#[Route('/api/poll/{id}', name: 'api_poll_update', methods: ['PUT'])]
public function updatePoll(Sondage $poll, Request $request, EntityManagerInterface $em): JsonResponse
{
    // Assurez-vous que l'utilisateur est bien celui qui a créé le sondage
    if ($poll->getUser() !== $this->getUser()) {
        return new JsonResponse(['status' => 'error', 'message' => 'Unauthorized'], Response::HTTP_FORBIDDEN);
    }

    // Récupérer les nouvelles données envoyées par le formulaire
    $data = json_decode($request->getContent(), true);
    $poll->setQuestion($data['question']); // Mettre à jour la question

    // Mettre à jour les choix
    foreach ($poll->getChoix() as $choice) {
        $em->remove($choice); // Supprimer les anciens choix
    }

    foreach ($data['choix'] as $choiceData) {
        $choice = new ChoixSondage();
        $choice->setContenu($choiceData['contenu']);
        $poll->addChoix($choice);
        $em->persist($choice); // Ajouter les nouveaux choix
    }

    $em->flush(); // Sauvegarder les changements

    return new JsonResponse(['status' => 'success', 'message' => 'Poll updated successfully']);
}

  

#[Route('/api/poll/{id}', name: 'api_poll_edit', methods: ['POST'])]
public function editPoll($id, Request $request, EntityManagerInterface $em): JsonResponse
{
    // Décoder le JSON envoyé dans le corps de la requête
    $data = json_decode($request->getContent(), true);

    if (!$data || !isset($data['question']) || empty($data['choix'])) {
        return new JsonResponse(['status' => 'error', 'message' => 'Données invalides'], 400);
    }

    // Récupérer le sondage existant par ID
    $sondage = $em->getRepository(Sondage::class)->find($id);
    if (!$sondage) {
        return new JsonResponse(['status' => 'error', 'message' => 'Sondage non trouvé'], 404);
    }

    // Récupérer l'utilisateur connecté
    $user = $em->getRepository(User::class)->find(1); // Utilisez l'ID dynamique de l'utilisateur connecté
    if (!$user) {
        return new JsonResponse(['status' => 'error', 'message' => 'Utilisateur non trouvé'], 404);
    }

    // Vérifiez si l'utilisateur est président d'un club
    $club = $em->getRepository(Club::class)->findOneBy(['president' => $user]);
    if (!$club) {
        return new JsonResponse(['status' => 'error', 'message' => 'L\'utilisateur n\'est pas président d\'un club'], 403);
    }

    // Mettre à jour la question du sondage
    $sondage->setQuestion($data['question']);

    // Récupérer les anciens choix
    $existingChoices = $sondage->getChoix();

    // Traiter les nouveaux choix (ajouts et modifications)
    $newChoices = $data['choix'];

    // Gérer la mise à jour des choix existants ou l'ajout de nouveaux choix
    $existingChoicesIds = [];
    foreach ($newChoices as $index => $choixData) {
        if (!isset($choixData['contenu']) || empty($choixData['contenu'])) {
            return new JsonResponse(['status' => 'error', 'message' => 'Un choix est vide'], 400);
        }

        // Vérifier si ce choix existe déjà dans les choix existants
        $existingChoice = isset($existingChoices[$index]) ? $existingChoices[$index] : null;

        if ($existingChoice) {
            // Si le choix existe déjà, mettre à jour son contenu
            $existingChoice->setContenu($choixData['contenu']);
            $em->persist($existingChoice); // Mettre à jour le choix existant
        } else {
            // Si le choix n'existe pas, on en crée un nouveau
            $choix = new ChoixSondage();
            $choix->setContenu($choixData['contenu']);
            $choix->setSondage($sondage);
            $em->persist($choix); // Ajouter le nouveau choix
        }

        $existingChoicesIds[] = $choixData['contenu'];
    }

    // Supprimer les choix qui ne sont plus dans la nouvelle liste
    foreach ($existingChoices as $choix) {
        if (!in_array($choix->getContenu(), $existingChoicesIds)) {
            $em->remove($choix); // Supprimer les choix non présents dans les nouveaux choix
        }
    }

    $em->flush();

    // Récupérer le nom du club
    $clubName = $club->getNomC();

    return new JsonResponse([
        'status' => 'success',
        'message' => 'Sondage mis à jour avec succès',
        'club_name' => $clubName
    ], 200);
}


    
#[Route('/poll/{id}', name: 'poll_edit', methods: ['PUT'])]
public function editPoll2(int $id, Request $request, EntityManagerInterface $em): JsonResponse
{
    $sondage = $em->getRepository(Sondage::class)->find($id);

    if (!$sondage) {
        return new JsonResponse(['status' => 'error', 'message' => 'Sondage non trouvé'], 404);
    }

    $data = json_decode($request->getContent(), true);

    // Mettre à jour la question
    $sondage->setQuestion($data['question']);

    // Mettre à jour les choix
    $existingChoix = $sondage->getChoix()->toArray();
    foreach ($data['choix'] as $choixData) {
        $choix = $em->getRepository(ChoixSondage::class)->find($choixData['id']);
        
        if ($choix) {
            $choix->setContenu($choixData['contenu']);
            $em->persist($choix);
        } else {
            // Ajouter un nouveau choix si l'ID est "new"
            if ($choixData['id'] === 'new') {
                $newChoix = new ChoixSondage();
                $newChoix->setContenu($choixData['contenu']);
                $sondage->addChoix($newChoix);
                $em->persist($newChoix);
            }
        }
    }

    // Enregistrer les changements
    $em->flush();

    return new JsonResponse(['status' => 'success', 'message' => 'Poll updated successfully']);
}



   /* #[Route('/{id}/edit', name: 'app_sondage_edit', methods: ['GET', 'POST'])]
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

    
    */
    
    
    
    

}