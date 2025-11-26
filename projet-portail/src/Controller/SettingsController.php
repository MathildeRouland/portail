<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_SUPER_ADMIN')]
#[Route('/superadmin/settings', name: 'superadmin_settings_')]
class SettingsController extends AbstractController
{
    #[Route('/', name: 'menu')]
    public function settingsMenu(): Response
    {
        // Ici tu pourras gérer l'image de fond, couleurs, etc.
        return $this->render('settings/menu.html.twig', [
            'page_title' => 'Menu des paramètres',
        ]);
    }

    #[Route('/system', name: 'system')]
    public function system(): Response
    {
        // Ici tu pourras récupérer les infos système depuis la base ou config
        return $this->render('settings/system.html.twig', [
            'page_title' => 'Paramètres Système',
        ]);
    }

    #[Route('/content', name: 'content')]
    public function content(): Response
    {
        // Ici tu pourras récupérer le texte du portail, RGPD, etc.
        return $this->render('settings/content.html.twig', [
            'page_title' => 'Paramètres de Contenu',
        ]);
    }

    #[Route('/appearance', name: 'appearance')]
    public function appearance(): Response
    {
        // Ici tu pourras gérer l'image de fond, couleurs, etc.
        return $this->render('settings/appearance.html.twig', [
            'page_title' => 'Paramètres d’Apparence',
        ]);
    }
}
