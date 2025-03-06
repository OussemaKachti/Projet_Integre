<?php

namespace App\Controller;
use App\Enum\RoleEnum;
use App\Entity\Club;
use App\Entity\Commentaire;

use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Psr\Log\LoggerInterface;

use App\Entity\Sondage;
use App\Form\SondageType;
use App\Entity\Reponse;
use App\Repository\ClubRepository;
use App\Repository\UserRepository;
use Symfony\Component\Mime\Email;

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
use Knp\Component\Pager\PaginatorInterface;
use App\Form\CommentaireType;




#[Route('/sondage')]
class SondageController extends AbstractController
{
/*
    #[Route('/{userId}', name: 'get_user_sondages', methods: ['GET'])]
    public function getUserSondages1(int $userId, EntityManagerInterface $em): JsonResponse
    {
        // Récupérer l'utilisateur
        $user = $em->getRepository(User::class)->find($userId);
    
        if (!$user) {
            return new JsonResponse(['message' => 'Utilisateur non trouvé'], 404);
        }
    
        // Vérifier si l'utilisateur a une participation dans un club
        $participation = $em->getRepository(ParticipationMembre::class)
            ->findOneBy(['user' => $user]);
    
        if (!$participation) {
            return new JsonResponse(['message' => 'Utilisateur non inscrit dans un club'], 404);
        }
 
    
        // Récupérer les sondages créés par cet utilisateur dans ce club
        $sondages = $em->getRepository(Sondage::class)->findBy([
            'club' => $participation->getClub(),
            'user' => $user // Ajout du filtre par user_id
        ]);
    
        // Préparer la réponse
        $sondagesData = array_map(fn($sondage) => [
            'question' => $sondage->getQuestion(),
            'createdAt' => $sondage->getCreatedAt()->format('Y-m-d H:i:s'),
            'user_id' => $sondage->getUser()->getId(),
            'club_id' => $sondage->getClub()->getId(),
        ], $sondages);
    
        return new JsonResponse($sondagesData);
    }
*/


    #[Route('/deleteAdmin/{id}', name: 'delete_admin', methods: ['POST'])]
    public function deletePollAdmin(int $id, Request $request, EntityManagerInterface $em, Security $security): Response
    {
        try {
            // Vérifier si le sondage existe
            $sondage = $em->getRepository(Sondage::class)->find($id);
            
            // Si le sondage n'est pas trouvé, renvoyer une erreur 404
            if (!$sondage) {
                return new JsonResponse(['error' => 'Sondage not found'], Response::HTTP_NOT_FOUND);
            }
    
            
           // $user = $em->getRepository(User::class)->find(1);  // Utilisateur statique pour tester
           $user = $security->getUser();

           
    
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

    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

            
    #[Route('/ListPolls', name: 'app_sondage_index', methods: ['GET'])]
    public function index(SondageRepository $sondageRepository, EntityManagerInterface $entityManager, Security $security): Response
    {
        $user = $security->getUser();
        $sondages = [];
        
        if ($user) {
            // Vérifier si l'utilisateur est président d'un club
            $clubPresident = $entityManager->getRepository(Club::class)->findOneBy(['president' => $user]);
            $isClubPresident = ($clubPresident !== null);

            // Vérifier si l'utilisateur est membre d'un club
            $participation = $entityManager->getRepository(ParticipationMembre::class)->findOneBy(['user' => $user, 'statut' => 'accepte']);
            $clubMembre = $participation ? $participation->getClub() : null;
            
            // Récupérer les sondages selon les conditions
            if ($clubPresident) {
                $sondages = $sondageRepository->findBy(['club' => $clubPresident]);
            } elseif ($clubMembre) {
                $sondages = $sondageRepository->findBy(['club' => $clubMembre]);
            }
        }
        
        $reponses = [];
        $sondageResults = [];
        
        foreach ($sondages as $sondage) {
            $reponse = $entityManager->getRepository(Reponse::class)->findOneBy([
                'sondage' => $sondage,
                'user' => $user
            ]);
            
            if ($reponse) {
                $reponses[$sondage->getId()] = $reponse->getChoixSondage()->getContenu();
            }
            
            $sondageResults[$sondage->getId()] = $this->getPollResults($sondage);
        }
        
        // Create the comment form
        $commentaire = new Commentaire();
        $form = $this->createForm(CommentaireType::class, $commentaire);
        
        return $this->render('sondage/ListPolls.html.twig', [
            'sondages' => $sondages,
            'reponses' => $reponses, 
            'sondageResults' => $sondageResults,
            'isClubPresident' => $isClubPresident,
            'form' => $form->createView()
        ]);
    }
    public function getPollResults(Sondage $sondage): array
{
    $totalVotes = count($sondage->getReponses());
    $results = [];

    foreach ($sondage->getChoix() as $choix) {
        $choixVotes = count(array_filter($sondage->getReponses()->toArray(), function ($reponse) use ($choix) {
            return $reponse->getChoixSondage() === $choix;
        }));

        $percentage = $totalVotes > 0 ? ($choixVotes / $totalVotes) * 100 : 0;
        $color = $this->getColorByPercentage($percentage);

        $results[] = [
            'choix' => $choix->getContenu(),
            'percentage' => round($percentage, 2),
            'color' => $color
        ];
    }

    return $results;
}


public function getColorByPercentage(float $percentage): string
{
    if ($percentage <= 20) {
        return '#FFC0CB'; // Pink Light - faible participation
    } elseif ($percentage <= 40) {
        return '#98FB98'; // Pale Green - participation modérée basse
    } elseif ($percentage <= 60) {
        return '#87CEEB'; // Sky Blue - participation moyenne
    } elseif ($percentage <= 80) {
        return '#32CD32'; // Lime Green - bonne participation
    } else {
        return '#228B22'; // Forest Green - excellente participation
    }
}




                                        // ADMINNNN


                                        #[Route('/adminPolls', name: 'app_sondage_index2')]
                                        public function index2(Request $request, SondageRepository $sondageRepository, PaginatorInterface $paginator): Response 
                                        {
                                            $query = trim($request->query->get('q', ''));
                                            
                                            $qb = $sondageRepository->createQueryBuilder('s')
                                                ->leftJoin('s.club', 'c')
                                                ->select('s', 'c', 'ch')
                                                ->leftJoin('s.choix', 'ch');
                                        
                                            if ($query !== '') {
                                                $qb->where('s.question LIKE :query OR c.nomC LIKE :query')
                                                   ->setParameter('query', '%' . $query . '%');
                                            }
                                        
                                            $qb->orderBy('s.createdAt', 'DESC');
                                            $pagination = $paginator->paginate(
                                                $qb,
                                                $request->query->getInt('page', 1),
                                                2 // Nombre d'éléments par page
                                            );
                                            
                                            // Calcul des statistiques
                                            $totalPolls = $sondageRepository->count([]);
                                            
                                            // Trouver le club le plus actif
                                            $mostActiveClub = $sondageRepository->createQueryBuilder('s')
                                                ->select('c.nomC as club_name, COUNT(s.id) as poll_count')
                                                ->leftJoin('s.club', 'c')
                                                ->groupBy('c.id')
                                                ->orderBy('poll_count', 'DESC')
                                                ->setMaxResults(1)
                                                ->getQuery()
                                                ->getOneOrNullResult();

                                            // Compter les sondages actifs (créés dans les 30 derniers jours)
                                            $activePolls = $sondageRepository->createQueryBuilder('s')
                                                ->select('COUNT(s.id)')
                                                ->where('s.createdAt >= :thirtyDaysAgo')
                                                ->setParameter('thirtyDaysAgo', new \DateTime('-30 days'))
                                                ->getQuery()
                                                ->getSingleScalarResult();

                                            // Compter le nombre total de réponses
                                            $totalVotes = $sondageRepository->createQueryBuilder('s')
                                                ->select('COUNT(r.id)')
                                                ->leftJoin('s.reponses', 'r')
                                                ->getQuery()
                                                ->getSingleScalarResult();

                                            // Récupérer les statistiques des 7 derniers jours
                                            $weekly_stats = $sondageRepository->getWeeklyStats();

                                            if ($request->isXmlHttpRequest()) {
                                                $sondagesFormatted = array_map(function($sondage) {
                                                    return [
                                                        'id' => $sondage->getId(),
                                                        'question' => $sondage->getQuestion(),
                                                        'club_name' => $sondage->getClub() ? $sondage->getClub()->getNomC() : 'Non défini',
                                                        'created_at' => $sondage->getCreatedAt() ? $sondage->getCreatedAt()->format('Y-m-d') : 'Non défini',
                                                        'choix' => $sondage->getChoix()->map(function($choix) {
                                                            return $choix->getContenu();
                                                        })->toArray(),
                                                    ];
                                                }, $pagination->getItems());
                                        
                                                // Rendre la pagination en HTML
                                                $paginationHtml = $this->renderView('sondage/_pagination.html.twig', [
                                                    'pagination' => $pagination
                                                ]);
                                        
                                                return new JsonResponse([
                                                    'sondages' => $sondagesFormatted,
                                                    'pagination' => $paginationHtml,
                                                    'count' => count($sondagesFormatted)
                                                ]);
                                            }
                                        
                                            return $this->render('sondage/adminPolls.html.twig', [
                                                'pagination' => $pagination,
                                                'sondages' => $pagination->getItems(),
                                                'total_polls' => $totalPolls,
                                                'total_votes' => $totalVotes,
                                                'active_polls' => $activePolls,
                                                'most_active_club' => $mostActiveClub ? $mostActiveClub['club_name'] : 'No club',
                                                'most_active_club_polls' => $mostActiveClub ? $mostActiveClub['poll_count'] : 0,
                                                'weekly_polls' => $weekly_stats['polls'],
                                                'weekly_votes' => $weekly_stats['votes'],
                                            ]);
                                            
                                        }
                                        
                                        
                                        

    // #[Route('/admin/polls', name: 'admin_search_poll', methods: ['GET'])]
    // public function searchPolls(Request $request, SondageRepository $sondageRepository): Response
    // {
    //     $query = $request->query->get('q', '');
    //     $startDate = $request->query->get('start_date', null);
    //     $endDate = $request->query->get('end_date', null);
    //     $clubName = $request->query->get('club_name', null);
    
    //     // Validation des critères de dates
    //     $dateFilter = [];
    //     if ($startDate) {
    //         $dateFilter['start'] = \DateTime::createFromFormat('Y-m-d', $startDate);
    //     }
    //     if ($endDate) {
    //         $dateFilter['end'] = \DateTime::createFromFormat('Y-m-d', $endDate);
    //     }
    
    //     // Recherche des sondages avec des critères avancés
    //     $results = $sondageRepository->advancedSearch($query, $dateFilter, $clubName);
    
    //     // Formatage des résultats pour l'affichage
    //     $formattedResults = array_map(function($sondage) {
    //         try {
    //             return [
    //                 'id' => $sondage->getId(),
    //                 'question' => $sondage->getQuestion(),
    //                 'createdAt' => $sondage->getCreatedAt()->format('d/m/Y'),
    //                 'club' => $sondage->getClub()->getNomC(),
    //                 'url' => $this->generateUrl('app_sondage_show', ['id' => $sondage->getId()])
    //             ];
    //         } catch (\Exception $e) {
    //             return null;
    //         }
    //     }, $results);
    
    //     // Filtrer les résultats null
    //     $formattedResults = array_filter($formattedResults);
    
    //     // Retourner la vue avec les résultats de recherche
    //     return $this->render('sondage/adminPolls.html.twig', [
    //         'sondages' => $formattedResults,
    //         'query' => $query,
    //         'startDate' => $startDate,
    //         'endDate' => $endDate,
    //         'clubName' => $clubName,
    //     ]);
        
    // // }
    
    

    
    
//partie chart admin 
#[Route('/poll/details/{id}', name: 'app_poll_details')]
public function pollDetails(
    int $id, 
    SondageRepository $sondageRepository,
    Request $request,
    PaginatorInterface $paginator
): Response {
    $sondage = $sondageRepository->find($id);

    if (!$sondage) {
        throw $this->createNotFoundException('Poll not found');
    }

    // Pagination des commentaires
    $pagination = $paginator->paginate(
        $sondage->getCommentaires(),
        $request->query->getInt('page', 1),
        5 // Nombre de commentaires par page
    );

    // Calcul des résultats du sondage
    $totalVotes = 0;
    $results = [];
    
    foreach ($sondage->getChoix() as $choix) {
        $count = count($choix->getReponses());
        $totalVotes += $count;
        $results[] = [
            'choix' => $choix->getContenu(),
            'count' => $count,
            'percentage' => 0 // Sera calculé ci-dessous
        ];
    }

    // Calcul des pourcentages
    if ($totalVotes > 0) {
        foreach ($results as &$result) {
            $result['percentage'] = round(($result['count'] / $totalVotes) * 100);
        }
    }

    return $this->render('sondage/pollsDetails.html.twig', [
        'sondage' => $sondage,
        'pagination' => $pagination,
        'poll_results' => $results
    ]);
}

private function getColorForPercentage(float $percentage): string
{
    if ($percentage <= 20) {
        return '#e74c3c'; // Rouge
    } elseif ($percentage <= 40) {
        return '#4682B4'; // blue
    } elseif ($percentage <= 60) {
        return '#f1c40f'; // Jaune
    } elseif ($percentage <= 80) {
        return '#2ecc71'; // Vert
    } else {
        return '#3498db'; // Bleu
    }
}

    



                                            // FIN ADMINN



      
                                            


#[Route('/{sondageId}/reponse', name: 'get_user_response_for_sondage', methods: ['GET'])]
public function getUserResponseForSondage(int $sondageId, EntityManagerInterface $entityManager,Security $security,
)
                                        {
                                                $user = $security->getUser();
                                        
                                                if (!$user) {
                                                    return new JsonResponse(['status' => 'error', 'message' => 'Utilisateur non trouvé.'], 404);
                                                }
                                        
                                                $sondage = $entityManager->getRepository(Sondage::class)->find($sondageId);
                                        
                                                if (!$sondage) {
                                                    throw $this->createNotFoundException('Sondage non trouvé.');
                                                }
                                        
                                                $reponse = $entityManager->getRepository(Reponse::class)->findOneBy([
                                                    'sondage' => $sondage,
                                                    'user' => $user
                                                ]);
                                        
                                                if ($reponse) {
                                                    return new JsonResponse([
                                                        'status' => 'success',
                                                        'reponse' => [
                                                            'choix' => [
                                                                'contenu' => $reponse->getChoixSondage()->getContenu()
                                                            ]
                                                        ]
                                                    ]);
                                                }
                                        
                                                return new JsonResponse(['status' => 'error', 'message' => 'Aucune réponse trouvée pour cet utilisateur.'], 404);
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
 */    


 #[Route('/api/poll/new', name: 'api_poll_new', methods: ['POST'])]
 public function createPoll(
     Request $request, 
     EntityManagerInterface $em, 
     ValidatorInterface $validator,
     MailerInterface $mailer,
     LoggerInterface $logger,
     Security $security
 ): JsonResponse {
     $data = json_decode($request->getContent(), true);
     
     if (!$data) {
         return new JsonResponse(['status' => 'error', 'message' => 'Invalid JSON data'], 400);
     }

     $user = $security->getUser();
     if (!$user) {
         return new JsonResponse(['status' => 'error', 'message' => 'User not authenticated'], 401);
     }

     // Trouver le club du président
     $club = $em->getRepository(Club::class)->findOneBy(['president' => $user]);
     if (!$club) {
         return new JsonResponse(['status' => 'error', 'message' => 'User is not a club president'], 403);
     }
     
     $sondage = new Sondage();
     $sondage->setQuestion($data['question'] ?? '');
     $sondage->setCreatedAt(new \DateTime());
     $sondage->setUser($user);
     $sondage->setClub($club);
     
     if (isset($data['choix']) && is_array($data['choix'])) {
         foreach ($data['choix'] as $choixData) {
             $choix = new ChoixSondage();
             $choix->setContenu($choixData['contenu'] ?? '');
             $choix->setSondage($sondage);
             $sondage->addChoix($choix);
             $em->persist($choix);
         }
     }
     
     $errors = $validator->validate($sondage);
     if (count($errors) > 0) {
         $errorMessages = [];
         foreach ($errors as $error) {
             $path = $error->getPropertyPath();
             if (str_contains($path, 'choix')) {
                 $errorMessages['choices'][] = $error->getMessage();
             } else {
                 $errorMessages[$error->getPropertyPath()] = $error->getMessage();
             }
         }
         return new JsonResponse([
             'status' => 'error',
             'message' => 'Validation failed',
             'errors' => $errorMessages
         ], 400);
     }
     
     try {
         $em->persist($sondage);
         $em->flush();
         
         $clubParticipations = $em->getRepository(ParticipationMembre::class)->findBy([
             'club' => $club->getId(),
             'statut' => 'accepte'
         ]);
         
         $emailsSent = 0;
         $emailErrors = [];
         
         foreach ($clubParticipations as $participation) {
             $member = $participation->getUser();
             
             if ($member && method_exists($member, 'getEmail') && $member->getEmail()) {
                 try {
                     $logger->info('Tentative d\'envoi d\'email à: ' . $member->getEmail());
                     
                     $email = (new Email())
                         ->from("admin@gmail.com")  
                         ->to($member->getEmail())            
                         ->subject('Nouveau sondage dans votre club: ' . $club->getNomC())
                         ->html($this->renderView(
                             'emails/new_poll.html.twig',
                             [
                                 'club' => $club,
                                 'sondage' => $sondage,
                                 'member' => $member
                             ]
                         ));
                     
                     $mailer->send($email);
                     $emailsSent++;
                     
                 } catch (TransportExceptionInterface $e) {
                     $logger->error('Échec d\'envoi d\'email à ' . $member->getEmail() . ': ' . $e->getMessage());
                     $emailErrors[] = 'Échec d\'envoi à ' . $member->getEmail() . ': ' . $e->getMessage();
                 }
             } else {
                 $logger->warning('Membre sans email valide trouvé');
             }
         }
         
         return new JsonResponse([
             'status' => 'success',
             'emailSender' => $user->getEmail(),
             'message' => 'Poll created successfully',
             'club_name' => $club->getNomC(),
             'emails_sent' => $emailsSent,
             'email_errors' => $emailErrors,
             'debug_info' => [
                 'total_members' => count($clubParticipations),
                 'mailer_dsn' => $_ENV['MAILER_DSN'] ?? 'Non configuré'
             ]
         ], 201);
         
     } catch (\Exception $e) {
         $logger->error('Erreur générale: ' . $e->getMessage());
         return new JsonResponse([
             'status' => 'error',
             'message' => 'Error: ' . $e->getMessage()
         ], 500);
     }
 }
 

    #[Route('/email', name: 'email', methods: ['POST'])]

    public function sendTestEmail(MailerInterface $mailer): Response
{
    $email = (new Email())
        ->from('oussemakachti17@gmail.com')  
        ->to('kachtioussema@gmail.com')  
        ->subject('Test Envoi Mail depuis Gmail')
        ->text('Ceci est un e-mail de test envoyé depuis mon compte Gmail via Symfony.');

    $mailer->send($email);

    return new Response('E-mail envoyé avec succès !');
}


// #[Route('/api/poll/new', name: 'api_poll_new', methods: ['POST'])]
// public function createPoll(Request $request, EntityManagerInterface $em, ValidatorInterface $validator,        Security $security,
// ): JsonResponse
// {
//     $data = json_decode($request->getContent(), true);

//     if (!$data) {
//         return new JsonResponse(['status' => 'error', 'message' => 'Invalid JSON data'], 400);
//     }

//     // Récupérer l'utilisateur et le club
//     $user = $security->getUser();
//     if (!$user) {
//         return new JsonResponse(['status' => 'error', 'message' => 'User not found'], 404);
//     }

//     $club = $em->getRepository(Club::class)->findOneBy(['president' => $user->getId()]);
//     if (!$club) {
//         return new JsonResponse(['status' => 'error', 'message' => 'You must be a club president to create polls'], 403);
//     }

//     // Créer et configurer le sondage
//     $sondage = new Sondage();
//     $sondage->setQuestion($data['question'] ?? '');
//     $sondage->setCreatedAt(new \DateTime());
//     $sondage->setUser($user);
//     $sondage->setClub($club);

//     // Ajouter les choix
//     if (isset($data['choix']) && is_array($data['choix'])) {
//         foreach ($data['choix'] as $choixData) {
//             $choix = new ChoixSondage();
//             $choix->setContenu($choixData['contenu'] ?? '');
//             $choix->setSondage($sondage);
//             $sondage->addChoix($choix);
//             $em->persist($choix);        }
//     }

//     // Validation
//     $errors = $validator->validate($sondage);
//     if (count($errors) > 0) {
//         $errorMessages = [];
//         foreach ($errors as $error) {
//             $path = $error->getPropertyPath();
//             if (str_contains($path, 'choix')) {
//                 $errorMessages['choices'][] = $error->getMessage();
//             } else {
//                 $errorMessages[$error->getPropertyPath()] = $error->getMessage();
//             }
//         }
//         return new JsonResponse([
//             'status' => 'error',
//             'message' => 'Validation failed',
//             'errors' => $errorMessages
//         ], 400);
//     }

//     try {
//         $em->persist($sondage);
//         $em->flush();
//         return new JsonResponse([
//             'status' => 'success',
//             'message' => 'Poll created successfully',
//             'club_name' => $club->getNomC()
//         ], 201);
//     } catch (\Exception $e) {
//         return new JsonResponse([
//             'status' => 'error',
//             'message' => 'Database error occurred'
//         ], 500);
//     }
// }

    
//ghalta dhaherli
    #[Route('/sondages', name: 'app_sondages')]
    public function getPollsByClub(EntityManagerInterface $em, SondageRepository $sondageRepository, ClubRepository $clubRepository, Security $security): Response
    {
        $user = $security->getUser();

        if (!$user) {
            throw $this->createAccessDeniedException('You should connect to see all polls');
        }

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

    //jdida
    #[Route('/AllPolls', name: 'api_user_polls', methods: ['GET'])]
    public function getUserPolls(EntityManagerInterface $em,        Security $security
    ): Response
    {
        $user = $security->getUser();

    
        if (!$user) {
            return $this->redirectToRoute('login'); 
        }
    
        $sondages = $em->getRepository(Sondage::class)->findBy(['user' => $user]);
    
        if (empty($sondages)) {
            $this->addFlash('error', 'Aucun sondage trouvé pour cet utilisateur');
        }
    
        return $this->render('sondage/allPolls.html.twig', [
            'sondages' => $sondages,
        ]);
    }



    //tekhdemch
    #[Route('/mes-sondages', name: 'app_sondage_user', methods: ['GET'])]
public function getUserSondages(EntityManagerInterface $em, SondageRepository $sondageRepository,        Security $security): Response
{
    $user = $security->getUser();

    $sondages = $em->getRepository(Sondage::class)->findAll();
    if (!$user) {
        return $this->render('sondage/allPolls.html.twig', [
            'sondages' => $sondages,

            'error' => 'Utilisateur non connecté.'
        ]);
    }

    $sondages = $sondageRepository->findSondagesByUser($user);

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
public function deleteSurvey(int $id, Request $request, EntityManagerInterface $em,      Security $security): Response
{
    try {
        $sondage = $em->getRepository(Sondage::class)->find($id);
        
        if (!$sondage) {
            return new JsonResponse(['error' => 'Sondage not found'], Response::HTTP_NOT_FOUND);
        }

        $user = $security->getUser();
        
        if (!$user) {
            return new JsonResponse(['error' => 'User not found'], Response::HTTP_NOT_FOUND);
        }

        if ($sondage->getUser()->getId() !== $user->getId()) {
            return new JsonResponse(['error' => 'You are not authorized to delete this survey'], Response::HTTP_FORBIDDEN);
        }

        $reponses = $em->getRepository(Reponse::class)->findBy(['sondage' => $sondage]);
        foreach ($reponses as $reponse) {
            $em->remove($reponse);
        }
        
        $em->remove($sondage);
        $em->flush();

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
    $sondage = $em->getRepository(Sondage::class)->find($id);

    if (!$sondage) {
        return new JsonResponse(['status' => 'error', 'message' => 'Sondage non trouvé'], 404);
    }

    $choix = $sondage->getChoix();

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
    if ($poll->getUser() !== $this->getUser()) {
        return new JsonResponse(['status' => 'error', 'message' => 'Unauthorized'], Response::HTTP_FORBIDDEN);
    }

    $data = json_decode($request->getContent(), true);
    $poll->setQuestion($data['question']); 

    foreach ($poll->getChoix() as $choice) {
        $em->remove($choice); 

    foreach ($data['choix'] as $choiceData) {
        $choice = new ChoixSondage();
        $choice->setContenu($choiceData['contenu']);
        $poll->addChoix($choice);
        $em->persist($choice);
    }

    $em->flush(); 

    return new JsonResponse(['status' => 'success', 'message' => 'Poll updated successfully']);
}
}
  

#[Route('/api/poll/{id}', name: 'api_poll_edit', methods: ['POST'])]
public function editPoll($id, Request $request, EntityManagerInterface $em,  Security $security): JsonResponse
{
    $data = json_decode($request->getContent(), true);

    if (!$data || !isset($data['question']) || empty($data['choix'])) {
        return new JsonResponse(['status' => 'error', 'message' => 'Données invalides'], 400);
    }

    $sondage = $em->getRepository(Sondage::class)->find($id);
    if (!$sondage) {
        return new JsonResponse(['status' => 'error', 'message' => 'Sondage non trouvé'], 404);
    }

    $user = $security->getUser();
    if (!$user) {
        return new JsonResponse(['status' => 'error', 'message' => 'Utilisateur non trouvé'], 404);
    }

    $club = $em->getRepository(Club::class)->findOneBy(['president' => $user]);
    if (!$club) {
        return new JsonResponse(['status' => 'error', 'message' => 'L\'utilisateur n\'est pas président d\'un club'], 403);
    }

    $sondage->setQuestion($data['question']);

    $existingChoices = $sondage->getChoix();

    $newChoices = $data['choix'];

    $existingChoicesIds = [];
    foreach ($newChoices as $index => $choixData) {
        if (!isset($choixData['contenu']) || empty($choixData['contenu'])) {
            return new JsonResponse(['status' => 'error', 'message' => 'Un choix est vide'], 400);
        }

        $existingChoice = isset($existingChoices[$index]) ? $existingChoices[$index] : null;

        if ($existingChoice) {
            $existingChoice->setContenu($choixData['contenu']);
            $em->persist($existingChoice); 
        } else {
            $choix = new ChoixSondage();
            $choix->setContenu($choixData['contenu']);
            $choix->setSondage($sondage);
            $em->persist($choix);
        }

        $existingChoicesIds[] = $choixData['contenu'];
    }

    foreach ($existingChoices as $choix) {
        if (!in_array($choix->getContenu(), $existingChoicesIds)) {
            $em->remove($choix); // Supprimer les choix non présents dans les nouveaux choix
        }
    }

    $em->flush();

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

    $sondage->setQuestion($data['question']);

    $existingChoix = $sondage->getChoix()->toArray();
    foreach ($data['choix'] as $choixData) {
        $choix = $em->getRepository(ChoixSondage::class)->find($choixData['id']);
        
        if ($choix) {
            $choix->setContenu($choixData['contenu']);
            $em->persist($choix);
        } else {
            if ($choixData['id'] === 'new') {
                $newChoix = new ChoixSondage();
                $newChoix->setContenu($choixData['contenu']);
                $sondage->addChoix($newChoix);
                $em->persist($newChoix);
            }
        }
    }

    $em->flush();

    return new JsonResponse(['status' => 'success', 'message' => 'Poll updated successfully']);
}




    
    
    //jdida
    // #[Route('/api/search-sondages', name: 'api_search_sondages', methods: ['GET'])]
    // public function searchSondages(Request $request, SondageRepository $sondageRepository): Response
    // {
    //     try {
    //         $query = $request->query->get('q', '');
            
    //         if (strlen($query) < 2) {
    //             // Rediriger ou afficher un message vide dans le template
    //             return $this->render('sondage/allPolls.html.twig', [
    //                 'sondages' => [],
    //                 'query' => $query
    //             ]);
    //         }
    
    //         $results = $sondageRepository->searchByQuestion($query);
            
    //         $formattedResults = array_map(function($sondage) {
    //             try {
    //                 return [
    //                     'id' => $sondage->getId(),
    //                     'question' => $sondage->getQuestion(),
    //                     'createdAt' => $sondage->getCreatedAt()->format('d/m/Y'),
    //                     'club' => $sondage->getClub()->getNomC(),
    //                     'url' => $this->generateUrl('app_sondage_show', ['id' => $sondage->getId()])
    //                 ];
    //             } catch (\Exception $e) {
    //                 return null;
    //             }
    //         }, $results);
    
    //         // Filtrer les résultats null
    //         $formattedResults = array_filter($formattedResults);
            
    //         // Rendre la page 'sondage/allPolls.html.twig' avec les résultats formatés
    //         return $this->render('sondage/allPolls.html.twig', [
    //             'sondages' => $formattedResults,
    //             'query' => $query
    //         ]);
            
    //     } catch (\Exception $e) {
    //         return $this->render('sondage/allPolls.html.twig', [
    //             'sondages' => [],
    //             'query' => $query,
    //             'error' => 'Une erreur est survenue lors de la recherche'
    //         ]);
    //     }
    // }
    

   
    #[Route('/api/polls/search', name: 'api_polls_search', methods: ['GET'])]
    public function search(Request $request, SondageRepository $sondageRepository): Response
    {
        $query = $request->query->get('q', '');
    
        $sondages = $sondageRepository->createQueryBuilder('s')
            ->where('s.question LIKE :query')
            ->setParameter('query', '%' . $query . '%')
            ->orderBy('s.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    
        // Affichage des résultats dans la même page ou une autre page de résultats
        return $this->render('sondage/allPolls.html.twig', [
            'sondages' => $sondages,
            'search_query' => $query,
        ]);
    }



}