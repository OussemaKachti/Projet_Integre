<?php

namespace App\Controller;

use App\Repository\CompetitionRepository;
use App\Repository\SaisonRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use App\Repository\ClubRepository;

final class ComHomeController extends AbstractController
{
    #[Route('/comHome', name: 'app_com_home')]
    public function index(
        SaisonRepository $saisonRepository,
        CompetitionRepository $competitionRepository,
        ClubRepository $clubRepository
    ): Response {
        $saisons = $saisonRepository->findAll();
        $missions = $competitionRepository->findAll();
        $topClubs = $clubRepository->getTopThreeClubs();
        
        return $this->render('compititionFront/indexCOM.html.twig', [
            'controller_name' => 'ComHomeController',
            'saisons' => $saisons,
            'missions' => $missions,
            'leaderboard' => $topClubs,
        ]);
    }
}
