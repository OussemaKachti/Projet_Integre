<?php

namespace App\Controller;

use App\Entity\User;

use App\Entity\Sondage;
use App\Entity\Commentaire;
use App\Form\CommentaireType;
use App\Repository\SondageRepository;
use Doctrine\ORM\EntityManagerInterface;
use App\Repository\CommentaireRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use App\Service\ToxicityDetector;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Knp\Component\Pager\PaginatorInterface;





#[Route('/commentaire')]
class CommentaireController extends AbstractController
{
    private $toxicityDetector;
    private $mailer;

    public function __construct(ToxicityDetector $toxicityDetector, MailerInterface $mailer)
    {
        $this->toxicityDetector = $toxicityDetector;
        $this->mailer = $mailer;
    }



                                                //Admin




   
    




                                                #[Route('/adminComments', name: 'app_commentaire_index', methods: ['GET'])]
                                                public function afficherCommentairesClub(
                                                    CommentaireRepository $commentaireRepository, 
                                                    Request $request,
                                                    PaginatorInterface $paginator
                                                ): Response {
                                                    // Récupérer le filtre depuis la requête GET
                                                    $clubFilter = $request->query->get('club', 'all');
                                                    
                                                    // Créer le QueryBuilder de base
                                                    $queryBuilder = $commentaireRepository->createQueryBuilder('c')
                                                        ->leftJoin('c.sondage', 's')
                                                        ->leftJoin('s.club', 'cl')
                                                        ->leftJoin('c.user', 'u')
                                                        ->orderBy('c.dateComment', 'DESC');

                                                    // Appliquer le filtre si un club spécifique est sélectionné
                                                    if ($clubFilter && $clubFilter !== 'all') {
                                                        $queryBuilder->andWhere('cl.nomC = :clubName')
                                                            ->setParameter('clubName', $clubFilter);
                                                    }

                                                    // Pagination
                                                    $pagination = $paginator->paginate(
                                                        $queryBuilder->getQuery(),
                                                        $request->query->getInt('page', 1),
                                                        6 // Nombre d'éléments par page
                                                    );

                                                    // Statistiques des commentaires
                                                    $stats = [
                                                        'total_comments' => $commentaireRepository->count([]),
                                                        'today_comments' => $commentaireRepository->countTodayComments(),
                                                        'flagged_comments' => $commentaireRepository->countFlaggedComments(),
                                                        'clubs_activity' => $commentaireRepository->getClubsActivity()
                                                    ];

                                                    // Récupérer la liste des clubs pour le filtre
                                                    $clubs = $commentaireRepository->getAvailableClubs();

                                                    return $this->render('commentaire/adminComments.html.twig', [
                                                        'pagination' => $pagination,
                                                        'stats' => $stats,
                                                        'clubs' => $clubs,
                                                        'selectedClub' => $clubFilter
                                                    ]);
                                                }
                                                

    #[Route('/{id}/delete', name: 'app_commentaire_delete', methods: ['POST'])]
public function delete($id, EntityManagerInterface $entityManager): RedirectResponse
{
    // Récupérer le commentaire à supprimer par son ID
    $commentaire = $entityManager->getRepository(Commentaire::class)->find($id);

    // Vérifier si le commentaire existe
    if (!$commentaire) {
        $this->addFlash('error', 'Commentaire non trouvé.');
        return $this->redirectToRoute('app_commentaire_index'); // Rediriger en cas d'erreur
    }

    // Suppression du commentaire
    $entityManager->remove($commentaire);
    $entityManager->flush(); // Appliquer les changements à la base de données

    // Message de succès
    $this->addFlash('success', 'Comment successfully deleted!');

    // Rediriger vers la page d'index des commentaires après la suppression
    return $this->redirectToRoute('app_commentaire_index');
}
    



                                                    //Admin
    



    
                                                    // #[Route('/comment/add/{id}', name: 'add_comment', methods: ['POST'])]
                                                    // public function addComment(
                                                    //     int $id, 
                                                    //     Request $request, 
                                                    //     SondageRepository $sondageRepository, 
                                                    //     EntityManagerInterface $em,
                                                    //     Security $security,

                                                    //     ValidatorInterface $validator
                                                    // ): JsonResponse 
                                                    // {
                                                    //     // $user = $this->getUser(); // �� Récupérer l'utilisateur connecté
                                                    //     $user = $security->getUser();
                                                        
                                                    //     if (!$user) {
                                                    //         return new JsonResponse(['error' => 'User not authenticated'], Response::HTTP_UNAUTHORIZED);
                                                    //     }
                                                
                                                    //     $sondage = $sondageRepository->find($id);
                                                    //     if (!$sondage) {
                                                    //         return new JsonResponse(['error' => 'Survey not found'], Response::HTTP_NOT_FOUND);
                                                    //     }
                                                
                                                    //     $data = json_decode($request->getContent(), true);
                                                        
                                                    //     $comment = new Commentaire();
                                                    //     $comment->setContenuComment($data['contenuComment'] ?? null);
                                                    //     $comment->setDateComment(new \DateTime());
                                                    //     $comment->setUser($user);
                                                    //     $comment->setSondage($sondage);
                                                
                                                    //     // Validation
                                                    //     $errors = $validator->validate($comment);
                                                    //     if (count($errors) > 0) {
                                                    //         $errorMessages = [];
                                                    //         foreach ($errors as $error) {
                                                    //             $errorMessages[] = $error->getMessage();
                                                    //         }
                                                    //         return new JsonResponse(['errors' => $errorMessages], Response::HTTP_BAD_REQUEST);
                                                    //     }
                                                
                                                    //     $em->persist($comment);
                                                    //     $em->flush();
                                                
                                                    //     return new JsonResponse([
                                                    //         'message' => 'Comment successfully added',
                                                    //         'comment' => [
                                                    //             'id' => $comment->getId(),
                                                    //             'contenu' => $comment->getContenuComment(),
                                                    //             'date' => $comment->getDateComment()->format('Y-m-d H:i:s'),
                                                    //             'user' => $user->getNom() . ' ' . $user->getPrenom(),
                                                    //             'sondage_id' => $sondage->getId()
                                                    //         ]
                                                    //     ], Response::HTTP_CREATED);
                                                    // }

    #[Route('/comment/list/{id}', name: 'list_comments', methods: ['GET'])]
    public function listComments(int $id, EntityManagerInterface $em, Security $security): JsonResponse
    {
        // Simuler un utilisateur (remplace par getUser() une fois l'authentification implémentée)
        //$user = $em->getRepository(User::class)->find(1); // Assure-toi que cet utilisateur existe
        $user = $security->getUser();

        // Récupérer le sondage depuis la base de données
        $sondage = $em->getRepository(Sondage::class)->find($id);
    
        if (!$sondage) {
            throw $this->createNotFoundException('Sondage non trouvé');
        }
    
        // Récupérer les commentaires associés au sondage
        $commentaires = $sondage->getCommentaires();
    
        // Créer un tableau avec les données à envoyer en réponse JSON
        $commentsData = [];
        foreach ($commentaires as $commentaire) {
            $commentsData[] = [
                'id' => $commentaire->getId(),
                'user' => $commentaire->getUser()->getNom() . ' ' . $commentaire->getUser()->getPrenom(),
                'date' => $commentaire->getDateComment()->format('d M Y'),
                'content' => $commentaire->getContenuComment(),
            ];
        }
    
        // Retourner une réponse JSON avec les commentaires et les autres informations
        return new JsonResponse([
            'comments' => $commentsData,
            'user' => [
                'id' => $user->getId(),
                'name' => $user->getNom() . ' ' . $user->getPrenom(),
            ]
        ]);
    }
    




#[Route('/comment/edit/{id}', name: 'edit_comment', methods: ['POST', 'PUT'])]
public function editComment(int $id, Request $request, EntityManagerInterface $em,        Security $security,
): Response
{
    $comment = $em->getRepository(Commentaire::class)->find($id);

    if (!$comment) {
        return new JsonResponse(['error' => 'Comment not found'], Response::HTTP_NOT_FOUND);
    }

    // Récupérer l'utilisateur (ici simulé pour le test, remplace par $this->getUser() quand l'authentification est implémentée)
    $user = $security->getUser();

    if (!$user) {
        return new JsonResponse(['error' => 'User not found'], Response::HTTP_NOT_FOUND);
    }

    // Vérification si l'utilisateur est celui qui a posté le commentaire
    if ($user !== $comment->getUser()) {
        return new JsonResponse(['error' => 'Access denied: You can only edit your own comments'], Response::HTTP_FORBIDDEN);
    }

    // Récupérer les données envoyées via le formulaire
    $content = $request->request->get('content');
    if ($content) {
        $comment->setContenuComment($content);
        $comment->setDateComment(new \DateTime()); // Mettre à jour la date de modification
    }

    $em->flush();

    return $this->redirectToRoute('app_sondage_index');
}




    #[Route('/comment/delete/{id}', name: 'delete_comment', methods: ['POST', 'DELETE'])]
    public function deleteComment(int $id, EntityManagerInterface $em,        Security $security,
    ): Response
    {
        $comment = $em->getRepository(Commentaire::class)->find($id);
    
        if (!$comment) {
            return new JsonResponse(['error' => 'Comment not found'], Response::HTTP_NOT_FOUND);
        }
    
        // 🔹 Simuler un utilisateur (Remplace par `$this->getUser()` si authentification active)
        $user = $security->getUser();
    
        if (!$user) {
            return new JsonResponse(['error' => 'User not found'], Response::HTTP_NOT_FOUND);
        }
    
        // 🔹 Vérifier si l'utilisateur est l'auteur du commentaire
        if ($user !== $comment->getUser()) {
            return new JsonResponse(['error' => 'Access denied: You can only delete your own comments'], Response::HTTP_FORBIDDEN);
        }
    
        $em->remove($comment);
        $em->flush();
    
        return $this->redirectToRoute('app_sondage_index'); // Redirection après suppression
    }
    

    #[Route('/comment/add/{id}', name: 'add_comment', methods: ['POST'])]
public function addComment(
    int $id, 
    Request $request, 
    SondageRepository $sondageRepository, 
    EntityManagerInterface $em,
    Security $security,
    ValidatorInterface $validator
): JsonResponse 
{
   $user = $security->getUser();
  // $user = $em->getRepository(User::class)->find(30);

    if (!$user) {
        return new JsonResponse(['error' => 'User not authenticated'], Response::HTTP_UNAUTHORIZED);
    }

    $sondage = $sondageRepository->find($id);
    if (!$sondage) {
        return new JsonResponse(['error' => 'Survey not found'], Response::HTTP_NOT_FOUND);
    }

    $data = json_decode($request->getContent(), true);
    $commentContent = $data['contenuComment'] ?? null;

    // Analyze comment for toxicity
    $toxicityAnalysis = $this->toxicityDetector->analyzeToxicity($commentContent);

    $comment = new Commentaire();
    $comment->setDateComment(new \DateTime());
    $comment->setUser($user);
    $comment->setSondage($sondage);

    if ($toxicityAnalysis['isToxic']) {
        // Send warning email to user
        $this->sendWarningEmail(
            $user->getEmail(),
            $commentContent,
            $toxicityAnalysis
        );

        // Replace toxic content with warning message
        $warningMessage = "⚠️ Comment hidden: This content was flagged by our AI moderation system for potentially inappropriate language. " .
                         "We encourage respectful and constructive discussions. " .
                         "If you believe this is an error, please contact our support team.";
        
        $comment->setContenuComment($warningMessage);
    } else {
        $comment->setContenuComment($commentContent);
    }

    // Validation
    $errors = $validator->validate($comment);
    if (count($errors) > 0) {
        $errorMessages = [];
        foreach ($errors as $error) {
            $errorMessages[] = $error->getMessage();
        }
        return new JsonResponse(['errors' => $errorMessages], Response::HTTP_BAD_REQUEST);
    }

    $em->persist($comment);
    $em->flush();

    return new JsonResponse([
        'message' => $toxicityAnalysis['isToxic'] ? 'Comment was modified due to inappropriate content' : 'Comment successfully added',
        'comment' => [
            'id' => $comment->getId(),
            'contenu' => $comment->getContenuComment(),
            'date' => $comment->getDateComment()->format('Y-m-d H:i:s'),
            'user' => $user->getNom() . ' ' . $user->getPrenom(),
            'sondage_id' => $sondage->getId()
        ]
    ], Response::HTTP_CREATED);
}

private function sendWarningEmail(string $userEmail, string $commentContent, array $toxicityAnalysis): void
{
    $email = (new Email())
        ->from('oussemakachti17@gmail.com')
        ->to($userEmail)
        ->subject('Warning: Inappropriate Comment Detected')
        ->html($this->renderView('emails/toxic_comment_warning.html.twig', [
            'commentContent' => $commentContent,
            'toxicWords' => $toxicityAnalysis['toxicWords'],
            'reason' => $toxicityAnalysis['reason']
        ]));

    try {
        $this->mailer->send($email);
    } catch (\Exception $e) {
        // Log l'erreur d'envoi d'email si nécessaire
    }
}
}