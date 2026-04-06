<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use App\Entity\Settings;
use App\Form\SettingsContentType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use App\Form\SettingsAppearanceType;

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
    public function content(Request $request, EntityManagerInterface $em)
    {
       $settings = $em->getRepository(Settings::class)->findOneBy([]) ?? new Settings();
        // Ici tu pourras récupérer le texte du portail, RGPD, etc.
       $form = $this->createForm(SettingsContentType::class, $settings);
    $form->handleRequest($request);

    if ($form->isSubmitted() && $form->isValid()) {

         if ($form->get('removePortalText')->getData()) {
            $settings->setPortalText(null); // ou '' si tu préfères vide
        }

        $rgpdFile = $form->get('rgpdFile')->getData();

        if ($rgpdFile) {
            $filename = 'rgpd_'.uniqid().'.'.$rgpdFile->guessExtension();
            $rgpdFile->move($this->getParameter('uploads_directory'), $filename);
            $settings->setRgpdFile($filename);
        }
        $uploadsDir = $this->getParameter('uploads_directory');

        // suppression demandée
        if ($form->get('removeRgpd')->getData() && $settings->getRgpdFile()) {
            $oldFile = $uploadsDir.'/'.$settings->getRgpdFile();

            if (file_exists($oldFile)) {
                unlink($oldFile);
            }

            $settings->setRgpdFile(null);
        }

        // upload nouveau fichier
        $rgpdFile = $form->get('rgpdFile')->getData();

        if ($rgpdFile) {

            // supprimer ancien fichier si existe
            if ($settings->getRgpdFile()) {
                $oldFile = $uploadsDir.'/'.$settings->getRgpdFile();
                if (file_exists($oldFile)) {
                    unlink($oldFile);
                }
            }

            $filename = 'rgpd_'.uniqid().'.'.$rgpdFile->guessExtension();
            $rgpdFile->move($uploadsDir, $filename);

            $settings->setRgpdFile($filename);
        }
       

        $em->persist($settings);
        $em->flush();
    }

    return $this->render('settings/content.html.twig', [
        'form' => $form->createView(),
        'page_title' => 'Paramètres de contenu',
    ]);
}
    

    #[Route('/appearance', name: 'appearance')]
    public function appearance(Request $request, EntityManagerInterface $em)
{
    $settings = $em->getRepository(Settings::class)->findOneBy([]) ?? new Settings();

    $form = $this->createForm(SettingsAppearanceType::class, $settings);
    $form->handleRequest($request);

    if ($form->isSubmitted() && $form->isValid()) {
        $uploadsDir = $this->getParameter('uploads_directory');

        // 1️⃣ Supprimer l'ancienne image si demandé
        if ($form->get('removeBackground')->getData() && $settings->getBackgroundImage()) {
            $oldFile = $uploadsDir . '/' . $settings->getBackgroundImage();
            if (file_exists($oldFile)) {
                unlink($oldFile);
            }
            $settings->setBackgroundImage(null);
        }

        // 2️⃣ Uploader la nouvelle image
        $file = $form->get('backgroundImage')->getData();
        if ($file) {
            $filename = uniqid() . '.' . $file->guessExtension();
            $file->move($uploadsDir, $filename);

            // Supprimer l'ancienne image si elle existe
            if ($settings->getBackgroundImage() && file_exists($uploadsDir.'/'.$settings->getBackgroundImage())) {
                unlink($uploadsDir.'/'.$settings->getBackgroundImage());
            }

            $settings->setBackgroundImage($filename);
        }

        $em->persist($settings);
        $em->flush();
    }
    

    return $this->render('settings/appearance.html.twig', [
        'form' => $form->createView(),
        'page_title' => 'Paramètres d\'apparence',
    ]);
}
}
