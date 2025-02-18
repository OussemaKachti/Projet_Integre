<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class ComHomeController extends AbstractController
{
    #[Route('/comHome', name: 'app_com_home')]
    public function index(): Response
    {
        
        
        $saisons = $saisonRepository->findAll();
        // Dummy Data for Testing
    $missions = [
        ['name' => 'Organize an Event', 'description' => 'Host at least one event this season.', 'points' => 50],
        ['name' => 'Recruit Members', 'description' => 'Gain 5 new members.', 'points' => 30],
    ];

    $leaderboard = [
        ['club' => 'Club A', 'points' => 200],
        ['club' => 'Club B', 'points' => 150],
        ['club' => 'Club C', 'points' => 100],
    ];
    
        return $this->render('compititionFront/indexCOM.html.twig', [
            'controller_name' => 'ComHomeController',
            'saison' => $saisons,  // ✅ Passing seasons
            'missions' => $missions,  // ✅ Passing missions
            'leaderboard' => $leaderboard,  // ✅ Passing leaderboard
        ]);
    }
}
