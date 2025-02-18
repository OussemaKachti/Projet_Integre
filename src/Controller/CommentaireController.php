<?php

namespace App\Controller;


use App\Entity\User;
use App\Entity\Sondage;
use App\Entity\Commentaire;
use App\Form\CommentaireType;
use App\Repository\CommentaireRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use App\Repository\SondageRepository;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\HttpFoundation\RedirectResponse;





#[Route('/commentaire')]
class CommentaireController extends AbstractController
{



                                                //Admin




    
    #[Route('/adminComments', name: 'app_commentaire_index', methods: ['GET'])]
    public function afficherCommentaires(CommentaireRepository $commentaireRepository): Response
    {
        $commentairesAvecClub = [];

        // Récupérer tous les commentaires
        $commentaires = $commentaireRepository->findAll();

        foreach ($commentaires as $commentaire) {
            $sondage = $commentaire->getSondage();
            $club = $sondage->getClub(); // Assurez-vous que la relation existe
            $clubName = $club ? $club->getNomC() : 'Non défini'; // Vérifiez que le club n'est pas null

            $commentairesAvecClub[] = [
                'id' => $commentaire->getId(),
                'user' => $commentaire->getUser()->getNom() . ' ' . $commentaire->getUser()->getPrenom(),
                'contenu' => $commentaire->getContenuComment(),
                'club_name' => $clubName,
                'created_at' => $commentaire->getDateComment()->format('Y-m-d')
            ];
        }

        return $this->render('commentaire/adminComments.html.twig', [
            'commentaires' => $commentairesAvecClub,
        ]);
    }


    #[Route('/adminComments', name: 'app_commentaire_index', methods: ['GET'])]
    public function afficherCommentairesClub(CommentaireRepository $commentaireRepository, Request $request): Response
    {
        $clubFilter = $request->query->get('club'); // Récupération du filtre depuis l'URL
    
        if ($clubFilter && $clubFilter !== 'all') {
            // Filtrer les commentaires en fonction du club sélectionné
            $commentaires = $commentaireRepository->createQueryBuilder('c')
                ->join('c.sondage', 's')
                ->join('s.club', 'cl')
                ->where('cl.nomC = :clubName')
                ->setParameter('clubName', $clubFilter)
                ->getQuery()
                ->getResult();
        } else {
            // Sinon, récupérer tous les commentaires
            $commentaires = $commentaireRepository->findAll();
        }
    
        // Formater les commentaires
        $commentairesAvecClub = [];
        $clubs = []; // Liste pour stocker les noms des clubs disponibles
    
        foreach ($commentaires as $commentaire) {
            $sondage = $commentaire->getSondage();
            $club = $sondage->getClub(); // Assurez-vous que la relation existe
            $clubName = $club ? $club->getNomC() : 'Non défini'; 
    
            if ($club) {
                $clubs[$clubName] = $clubName; // Ajouter à la liste des clubs uniques
            }
    
            $commentairesAvecClub[] = [
                'id' => $commentaire->getId(),
                'user' => $commentaire->getUser()->getNom() . ' ' . $commentaire->getUser()->getPrenom(),
                'contenu' => $commentaire->getContenuComment(),
                'club_name' => $clubName,
                'created_at' => $commentaire->getDateComment()->format('Y-m-d')
            ];
        }
    
        return $this->render('commentaire/adminComments.html.twig', [
            'commentaires' => $commentairesAvecClub,
            'clubs' => $clubs, // Envoyer la liste des clubs pour le filtre
            'selectedClub' => $clubFilter ?? 'all' // Club actuellement sélectionné
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
    $this->addFlash('success', 'Commentaire supprimé avec succès.');

    // Rediriger vers la page d'index des commentaires après la suppression
    return $this->redirectToRoute('app_commentaire_index');
}
    



                                                    //Admin
    



    
    #[Route('/comment/add/{id}', name: 'add_comment', methods: ['POST'])]
    public function addComment(int $id, Request $request, SondageRepository $sondageRepository, EntityManagerInterface $em): JsonResponse
    {
       // $user = $this->getUser(); // 🔹 Récupérer l'utilisateur connecté
        $user = $em->getRepository(User::class)->find(1); // Remplace 1 par l'ID d'un utilisateur existant

        if (!$user) {
            return new JsonResponse(['error' => 'User not authenticated'], Response::HTTP_UNAUTHORIZED);
        }

        $sondage = $sondageRepository->find($id);
        if (!$sondage) {
            return new JsonResponse(['error' => 'Survey not found'], Response::HTTP_NOT_FOUND);
        }

        $data = json_decode($request->getContent(), true);
        if (!isset($data['contenuComment']) || empty($data['contenuComment'])) {
            return new JsonResponse(['error' => 'Comment content is required'], Response::HTTP_BAD_REQUEST);
        }

        // 🔹 Création du commentaire
        $comment = new Commentaire();
        $comment->setContenuComment($data['contenuComment']);
        $comment->setDateComment(new \DateTime());
        $comment->setUser($user);
        $comment->setSondage($sondage);

        $em->persist($comment);
        $em->flush();

        return new JsonResponse([
            'message' => 'Comment successfully added',
            'comment' => [
                'id' => $comment->getId(),
                'contenu' => $comment->getContenuComment(),
                'date' => $comment->getDateComment()->format('Y-m-d H:i:s'),
                'user' => $user->getNom() . ' ' . $user->getPrenom(),
                'sondage_id' => $sondage->getId()
            ]
        ], Response::HTTP_CREATED);
    }

    #[Route('/comment/list/{id}', name: 'list_comments', methods: ['GET'])]
public function listComments(int $id, EntityManagerInterface $em): Response
{
    // Simuler un utilisateur (remplace par getUser() une fois l'authentification implémentée)
    $user = $em->getRepository(User::class)->find(1); // Assure-toi que cet utilisateur existe

    // Récupérer le sondage depuis la base de données
    $sondages = $em->getRepository(Sondage::class)->find($id);

    if (!$sondages) {
        throw $this->createNotFoundException('Sondage non trouvé');
    }

    // Récupérer les commentaires associés au sondage
    $commentaires = $sondages->getCommentaires();

    // Vérifier que la variable est bien transmise
    return $this->render('sondage/ListPolls.html.twig', [
        'sondages' => $sondages,
        'commentaires' => $commentaires,
        'current_user' => $user, // Passe l'utilisateur simulé à la vue
    ]);
    
}




#[Route('/comment/edit/{id}', name: 'edit_comment', methods: ['POST', 'PUT'])]
public function editComment(int $id, Request $request, EntityManagerInterface $em): Response
{
    $comment = $em->getRepository(Commentaire::class)->find($id);

    if (!$comment) {
        return new JsonResponse(['error' => 'Comment not found'], Response::HTTP_NOT_FOUND);
    }

    // Récupérer l'utilisateur (ici simulé pour le test, remplace par $this->getUser() quand l'authentification est implémentée)
    $user = $em->getRepository(User::class)->find(1);

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
    public function deleteComment(int $id, EntityManagerInterface $em): Response
    {
        $comment = $em->getRepository(Commentaire::class)->find($id);
    
        if (!$comment) {
            return new JsonResponse(['error' => 'Comment not found'], Response::HTTP_NOT_FOUND);
        }
    
        // 🔹 Simuler un utilisateur (Remplace par `$this->getUser()` si authentification active)
        $user = $em->getRepository(User::class)->find(1); // Remplace par l'ID d'un utilisateur existant
    
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
    
}