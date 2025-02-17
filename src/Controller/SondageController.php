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
    

    #[Route('/{id}/delete', name: 'sondage_delete', methods: ['POST'])]
    public function deletePoll(int $id, EntityManagerInterface $em, UserRepository $userRepository): JsonResponse
    {
        // Dump des informations pour vérifier la requête
        dump($id);  // Afficher l'ID du sondage reçu
        $sondage = $em->getRepository(Sondage::class)->find($id);
    
        if (!$sondage) {
            return new JsonResponse(['status' => 'error', 'message' => 'Sondage non trouvé'], 404);
        }
    
        dump($sondage);  // Afficher les informations du sondage récupéré
    
        // Supprimer les choix associés si nécessaire
        foreach ($sondage->getChoix() as $choix) {
            $em->remove($choix);
        }
    
        // Supprimer le sondage lui-même
        $em->remove($sondage);
        $em->flush();
    
        return new JsonResponse(['status' => 'success', 'message' => 'Sondage supprimé avec succès'], 200);
    }
    
    
    




                                            // FIN ADMINN






    
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
        // Décoder le JSON envoyé dans le corps de la requête
        $data = json_decode($request->getContent(), true);
    
        if (!$data || !isset($data['question']) || empty($data['choix'])) {
            return new JsonResponse(['status' => 'error', 'message' => 'Données invalides'], 400);
        }
    
        // Créer un nouvel objet Sondage
        $sondage = new Sondage();
        $sondage->setQuestion($data['question']);
        $sondage->setCreatedAt(new \DateTime());
    
        // Récupérer l'utilisateur connecté (prenez l'ID de l'utilisateur actuel)
        $user = $em->getRepository(User::class)->find(1); // Utilisez l'ID dynamique de l'utilisateur connecté
        if (!$user) {
            return new JsonResponse(['status' => 'error', 'message' => 'Utilisateur non trouvé'], 404);
        }
    
        // Vérifiez si l'utilisateur est président d'un club
        $club = $em->getRepository(Club::class)->findOneBy(['president' => $user]);
        if (!$club) {
            return new JsonResponse(['status' => 'error', 'message' => 'L\'utilisateur n\'est pas président d\'un club'], 403);
        }
    
        // Associez le club au sondage
        $sondage->setClub($club);  // Assurez-vous que la méthode `setClub` existe dans l'entité Sondage
    
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
    
        // Récupérer le nom du club
        $clubName = $club->getNomC();
    
        return new JsonResponse([
            'status' => 'success', 
            'message' => 'Sondage créé avec succès',
            'club_name' => $clubName  // Vous pouvez envoyer le nom du club avec la réponse
        ], 201);
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





    #[Route('/delete/{id}', name: 'delete_survey', methods: ['DELETE'])]
public function deleteSurvey(int $id, Request $request, EntityManagerInterface $em): Response
{
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

    // Supprimer le sondage
    $em->remove($sondage);
    $em->flush();

    // Retourner une réponse simple après la suppression
    return new JsonResponse(['message' => 'Survey successfully deleted'], Response::HTTP_OK);
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

    
    
    
#[Route('/api/poll/{id}', name: 'api_poll_edit', methods: ['PUT'])]
public function editPoll(int $id, Request $request, EntityManagerInterface $em): JsonResponse
{
    $sondage = $em->getRepository(Sondage::class)->find($id);

    if (!$sondage) {
        return new JsonResponse(['status' => 'error', 'message' => 'Sondage non trouvé'], 404);
    }

    $data = json_decode($request->getContent(), true);
    
    if (!$data || !isset($data['question']) || empty($data['choix'])) {
        return new JsonResponse(['status' => 'error', 'message' => 'Données invalides'], 400);
    }

    // Mettre à jour la question du sondage
    $sondage->setQuestion($data['question']);

    // Récupérer les choix existants et créer une liste pour comparer
    $choixExistants = $sondage->getChoix();
    $nouveauxChoix = [];

    foreach ($data['choix'] as $choixData) {
        if (!isset($choixData['id'])) {
            // Nouveau choix ajouté par l'utilisateur
            $choix = new ChoixSondage();
            $choix->setContenu($choixData['contenu']);
            $choix->setSondage($sondage);
            $em->persist($choix);
            $nouveauxChoix[] = $choix;
        } else {
            // Vérifier si le choix existe déjà
            $choix = $em->getRepository(ChoixSondage::class)->find($choixData['id']);
            if ($choix && $choix->getSondage() === $sondage) {
                $choix->setContenu($choixData['contenu']);
                $nouveauxChoix[] = $choix;
            }
        }
    }

    // Supprimer les choix qui ne sont plus dans la nouvelle liste
    foreach ($choixExistants as $choix) {
        if (!in_array($choix, $nouveauxChoix, true)) {
            $em->remove($choix);
        }
    }

    $em->flush();

    return new JsonResponse(['status' => 'success', 'message' => 'Sondage mis à jour avec succès'], 200);
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