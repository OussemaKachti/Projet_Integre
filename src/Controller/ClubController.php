<?php

namespace App\Controller;

use App\Entity\Club;
use App\Form\ClubType;
use App\Repository\ClubRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Enum\StatutClubEnum;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\String\Slugger\SluggerInterface;  

#[Route('/club')]
class ClubController extends AbstractController
{
    #[Route('/carte', name: 'app_club_index', methods: ['GET'])]
    public function index(ClubRepository $clubRepository): Response
    {
        return $this->render('club/index.html.twig', [
            'clubs' => $clubRepository->findAll(),
        ]);
    }

    #[Route('/', name: 'clubdetail', methods: ['GET'])]
    public function clubdetail(ClubRepository $clubRepository): Response
    {
        return $this->render('club/clubDetails.html.twig', [
            'clubs' => $clubRepository->findAll(),
        ]);
    }

    #[Route('/myclub', name: 'myclub', methods: ['GET'])]
    public function index23(ClubRepository $clubRepository): Response
    {
        return $this->render('club/myClub.html.twig', [
            'clubs' => $clubRepository->findAll(),
        ]);
    }

    #[Route('/member', name: 'member', methods: ['GET'])]
    public function member(ClubRepository $clubRepository): Response
    {
        return $this->render('club/member.html.twig', [
            'clubs' => $clubRepository->findAll(),
        ]);
    }

    #[Route('/listemember', name: 'listemember', methods: ['GET'])]
    public function listemember(ClubRepository $clubRepository): Response
    {
        return $this->render('club/listeMembre.html.twig', [
            'clubs' => $clubRepository->findAll(),
        ]);
    }

    #[Route('/joinclub', name: 'joinclub', methods: ['GET'])]
    public function joinclub(ClubRepository $clubRepository): Response
    {
        return $this->render('club/joinClub.html.twig', [
            'clubs' => $clubRepository->findAll(),
        ]);
    }

    #[Route('/frontpage', name: 'frontpage', methods: ['GET'])]
    public function frontpage(ClubRepository $clubRepository): Response
    {
        return $this->render('club/frontpage.html.twig', [
            'clubs' => $clubRepository->findAll(),
        ]);
    }

    //#[Route('/adminclub', name: 'adminclub', methods: ['GET'])]
    //public function adminclub(ClubRepository $clubRepository): Response
    //{
    //    return $this->render('club/adminClub.html.twig', [
    //        'clubs' => $clubRepository->findAll(),
    //    ]);
    //}

    //#[Route('/adminmember', name: 'adminmember', methods: ['GET'])]
    //public function adminmember(ClubRepository $clubRepository): Response
    //{
    //    return $this->render('club/adminMember.html.twig', [
    //        'clubs' => $clubRepository->findAll(),
    //    ]);
    //}

    #[Route('/index2', name: 'app_club_index2', methods: ['GET'])]
    public function index2(ClubRepository $clubRepository): Response
    {   
        return $this->render('club/index2.html.twig', [
            'clubs' => $clubRepository->findAll(),
        ]);
    }
    #[Route('/newClub', name: 'app_club_new', methods: ['GET', 'POST'])]
    public function newClub(Request $request, EntityManagerInterface $entityManager, SluggerInterface $slugger): Response
    {
        $club = new Club();
        $form = $this->createForm(ClubType::class, $club);
        $form->handleRequest($request);
    
        if ($form->isSubmitted() && $form->isValid()) { 
            $club->setStatus(StatutClubEnum::EN_ATTENTE);
            $club->setPoints(0);
    
            // Gestion de l'upload de l'image du club
            $imageFile = $form->get('image')->getData();
            if ($imageFile) {
                $originalFilename = pathinfo($imageFile->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = $slugger->slug($originalFilename);
                $newFilename = $safeFilename . '-' . uniqid() . '.' . $imageFile->guessExtension();
    
                try {
                    $imageFile->move(
                        $this->getParameter('uploads_directory'), // Assurez-vous de définir ce paramètre
                        $newFilename
                    );
                    $club->setImage($newFilename);
                } catch (FileException $e) {
                    $this->addFlash('error', 'Une erreur est survenue lors du téléchargement de l\'image.');
                    return $this->redirectToRoute('app_club_new');
                }
            }

            // Associer le club à l'utilisateur connecté (le président)
           // $president = $this->getUser(); // Utilisateur connecté
           // if (!$president) {
               // throw new \Exception("Aucun utilisateur connecté.");
           // }
           // $club->setPresident($president); // Définir le président du club
    
            $entityManager->persist($club); 
            $entityManager->flush(); 
    
            return $this->redirectToRoute('app_club_index', [], Response::HTTP_SEE_OTHER); 
        }
    //afficher le formulaire
        return $this->render('club/new.html.twig', [
            'club' => $club, 
            'form' => $form, 
        ]);
    }
    
    #[Route('/{id}', name: 'app_club_show', methods: ['GET'])] // show the club
    public function show($id, EntityManagerInterface $entityManager): Response

    {
        $club = $entityManager->getRepository(Club::class)->find($id);
        return $this->render('club/myClub.html.twig', [
            'club' => $club,
        ]);
    }

    #[Route('/myClub', name: 'myClub', methods: ['GET'])]
    public function myClub()
    {
        return $this->render('club/myClub.html.twig', [
            
        ]);
    }

    #[Route('/{id}/edit', name: 'app_club_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Club $club, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(ClubType::class, $club);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_club_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('club/edit.html.twig', [
            'club' => $club,
            'form' => $form,
        ]);
    }

    //#[Route('/{id}', name: 'app_club_delete', methods: ['POST'])]
   // public function delete(int $id, Request $request, EntityManagerInterface $entityManager): Response
    /////////

        //if ($this->isCsrfTokenValid('delete'.$club->getId(), $request->request->get('_token'))) {
            //$entityManager->remove($club);
            //$entityManager->flush();
            //$this->addFlash('success', 'Club supprimé avec succès.');
        //}

        //return $this->redirectToRoute('app_club_index2');
    //}


        //if ($this->isCsrfTokenValid('delete'.$club->getId(), $request->request->get('_token'))) {
            //$entityManager->remove($club);
            //$entityManager->flush();
            //$this->addFlash('success', 'Club supprimé avec succès.');
        //}

        //return $this->redirectToRoute('app_club_index2');
    //}

    #[Route('/clubdelete/{id}', name: 'app_club_delete')]
    public function supprimerPoste(int $id, EntityManagerInterface $entityManager, ClubRepository $clubRepository): Response
    {
       // $club = $entityManager->getRepository(Club::class)->find($id);
$club = $clubRepository->find($id);
        if (!$club) {
            // Post does not exist, redirect back
           // $this->addFlash('error', 'Le poste n\'existe pas.');
            return $this->redirectToRoute('app_club_index2');
        }

        // Remove the post from the database
        $entityManager->remove($club);
        $entityManager->flush();

        $this->addFlash('success', 'Le poste a été supprimé avec succès.');
        return $this->redirectToRoute('app_club_index2');
    }

    #[Route('/accepteclub/{id}', name: 'club_accepte')]
    public function acceptePoste(int $id,EntityManagerInterface $entityManager, ClubRepository $clubRepository): Response
    {
        $club = $entityManager->getRepository(Club::class)->find($id);

        if (!$club) {
            // Post does not exist, redirect back
            //$this->addFlash('error', 'Le poste n\'existe pas.');
            return $this->redirectToRoute('app_club_index2');
        }

        // accepte the post from the database
        $club->setStatus(StatutClubEnum::ACCEPTE);
        $entityManager->flush();

        //$this->addFlash('success', 'Le poste a été accepte avec succès.');
        return $this->redirectToRoute('app_club_index2');
    }

}


