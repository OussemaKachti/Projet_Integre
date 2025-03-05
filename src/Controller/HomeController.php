<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class HomeController extends AbstractController
{
    #[Route('/home', name: 'app_home')]
    public function index(): Response
    {
        // Example: Pass some data to the template
        $message = 'Welcome to the Home Page!';

        return $this->render('user/home.html.twig', [
            'message' => $message,
        ]);
    }
}