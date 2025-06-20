<?php

namespace App\Controller;

use App\Entity\Competition;
use App\Entity\Club;
use App\Form\CompetitionType;
use App\Form\EditCompetitionType;
use App\Repository\CompetitionRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Service\MissionCompletionChecker;
use App\Repository\ClubRepository;
use App\Repository\MissionProgressRepository;
use App\Entity\MissionProgress;
use App\Enum\GoalTypeEnum;


#[Route('/competition')]
class CompetitionController extends AbstractController
{
    #[Route('/', name: 'app_competition_index', methods: ['GET'])]
    public function index(Request $request,ClubRepository $clubRepository ,CompetitionRepository $competitionRepository ,EntityManagerInterface $entityManager): Response
    {
        $competition = new Competition();
    $form = $this->createForm(CompetitionType::class, $competition);
    $form->handleRequest($request);

    if ($form->isSubmitted() && $form->isValid()) {
        $entityManager->persist($competition);
        $entityManager->flush();

        return $this->redirectToRoute('app_competition_index'); 
    }


    
    $leaderboard = $clubRepository->getLeaderboardData();




    return $this->render('competition/index.html.twig', [
        'missions' => $competitionRepository->findAll(),
        'form' => $form->createView(), // ✅ Pass the form to Twig
        'leaderboard' => $leaderboard,
        
    ]);
    }

    #[Route('/new', name: 'app_competition_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $competition = new Competition();
        $form = $this->createForm(CompetitionType::class, $competition);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($competition);
            $entityManager->flush();

             // Only create MissionProgress if competition is activated
        if ($competition->getStatus() === 'activated') {
            $this->initializeMissionProgress($competition,$entityManager);
        }
            return $this->redirectToRoute('app_competition_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('competition/new.html.twig', [
            'competition' => $competition,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_competition_show', methods: ['GET'])]
    public function show(Competition $competition): Response
    {
        return $this->render('competition/show.html.twig', [
            'competition' => $competition,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_competition_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Competition $competition, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(EditCompetitionType::class, $competition);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();
            $this->addFlash('success', 'Mission updated successfully!');
            
            // Re-activate MissionProgress if status changed to activated
            if ($competition->getStatus() === 'activated') {
                $this->initializeMissionProgress($competition, $entityManager);
            }

            return $this->redirectToRoute('app_competition_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('competition/edit.html.twig', [
            'competition' => $competition,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}', name: 'app_competition_delete', methods: ['POST'])]
    public function delete(Request $request, Competition $competition, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$competition->getId(), $request->request->get('_token'))) {
            $entityManager->remove($competition);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_competition_index', [], Response::HTTP_SEE_OTHER);
    }


    #[Route('/admin/update-missions', name: 'update_missions')]
public function updateMissions(MissionCompletionChecker $checker): Response
{
    $checker->checkAndUpdateMissionStatus();
    
    $this->addFlash('success', 'Mission statuses have been checked and updated.');
    
    return $this->redirectToRoute('app_competition_index');
}

   // Method to create MissionProgress for all clubs
   public function initializeMissionProgress(Competition $competition, EntityManagerInterface $entityManager): void
   {
    $clubRepository = $entityManager->getRepository(Club::class);
    $missionProgressRepository = $entityManager->getRepository(MissionProgress::class);
       $clubs = $clubRepository->findAll();

       foreach ($clubs as $club) {
           $existingProgress = $missionProgressRepository->findOneBy([
               'competition' => $competition,
               'club' => $club
           ]);

           if (!$existingProgress) {
               $progress = new MissionProgress();
               $progress->setCompetition($competition);
               $progress->setClub($club);
               $progress->setProgress(0, $entityManager);
               $progress->setIsCompleted(false);
               $entityManager->persist($progress);
           }
       }

       $entityManager->flush();
   }

   // Activate competition and create missing MissionProgress entries
   public function activateCompetition(
       Competition $competition, 
       EntityManagerInterface $entityManager, 
       ClubRepository $clubRepository,
       MissionProgressRepository $missionProgressRepository
   ): Response {
       if ($competition->getStatus() !== 'activated') {
           $competition->setStatus('activated');
           $clubs = $clubRepository->findAll();
   
           foreach ($clubs as $club) {
               $progress = $missionProgressRepository->findOneBy([
                   'club' => $club,
                   'competition' => $competition
               ]);
   
               if (!$progress) {
                   $progress = new MissionProgress();
                   $progress->setClub($club);
                   $progress->setCompetition($competition);
                   $entityManager->persist($progress);
               } else {
                   $progress->setProgress(0); // Reset progress if reactivating
               }
           }
   
           $entityManager->flush();
       }
   
       return $this->redirectToRoute('app_competition_index');
   }
    

#[Route('/admin/check-expired-competitions', name: 'check_expired_competitions')]
public function checkExpiredCompetitions(EntityManagerInterface $entityManager, CompetitionRepository $competitionRepository): Response
{
    $today = new \DateTime();
    $expiredCompetitions = $competitionRepository->createQueryBuilder('c')
        ->where('c.endDate < :today')
        ->andWhere('c.status = :status')
        ->setParameter('today', $today)
        ->setParameter('status', 'activated')
        ->getQuery()
        ->getResult();

    foreach ($expiredCompetitions as $competition) {
        $competition->setStatus('expired'); // ✅ Mark as expired

        // Reset progress for all clubs in this competition
        foreach ($competition->getMissionProgresses() as $progress) {
            $progress->setProgress(0);
            $progress->setIsCompleted(false);
            $entityManager->persist($progress);
        }

    }

    $entityManager->flush();

    return new Response("Expired competitions updated.");
}

 

}