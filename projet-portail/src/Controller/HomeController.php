<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use App\Entity\Settings;
use Doctrine\ORM\EntityManagerInterface;

final class HomeController extends AbstractController
{
    #[Route('/', name: 'homepage')]
    public function home(EntityManagerInterface $em): Response
    {
        $settings = $em->getRepository(Settings::class)->findOneBy([]);
        // Récupérer les paramètres du portail
        return $this->render('homepage.html.twig', [
            'settings' => $settings
        ]);
    }
}
