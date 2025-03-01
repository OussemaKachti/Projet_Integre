<?php

namespace App\Controller;

use App\Repository\CompetitionRepository;
use App\Repository\SaisonRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class ComHomeController extends AbstractController
{
    #[Route('/comHome', name: 'app_com_home')]
    public function index(SaisonRepository $saisonRepository,CompetitionRepository $competitionRepository): Response
    {
        
        
        $saisons = $saisonRepository->findAll();
        $missions = $competitionRepository->findAll();



        // Dummy Data for Testing
    $leaderboard = [
        ['club' => 'Club A', 'points' => 200],
        ['club' => 'Club B', 'points' => 150],
        ['club' => 'Club C', 'points' => 100],
    ];
    
        return $this->render('compititionFront/indexCOM.html.twig', [
            'controller_name' => 'ComHomeController',
            'saisons' => $saisons,  // ✅ Passing seasons
            'missions' => $missions,  // ✅ Passing missions
            'leaderboard' => $leaderboard,  // ✅ Passing leaderboard
        ]);
    }
}
