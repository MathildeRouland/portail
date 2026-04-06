<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use App\Entity\Settings;
use Doctrine\ORM\EntityManagerInterface;

final class HomeController extends AbstractController
{
   // #[Route('/home', name: 'app_home')]
    //public function index(): Response
    //{
      //  return $this->render('home/index.html.twig', [
      //      'controller_name' => 'HomeController',
       // ]);
   // }

    #[Route('/', name: 'homepage')]
    public function home (EntityManagerInterface $em): Response
{
    $settings = $em->getRepository(Settings::class)->findOneBy([]);
     // Passer les utilisateurs au template
     return $this->render('homepage.html.twig', [
         'settings' => $settings
     ]);
}
}
