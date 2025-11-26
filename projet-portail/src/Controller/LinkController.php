<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\Request;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Bundle\SecurityBundle\Security;
use App\Repository\LinkRepository;
use App\Entity\Link;
use App\Form\LinkType;
use App\Form\LinkEditType;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

final class LinkController extends AbstractController
{
    //     #[Route('/link', name: 'app_link')]
    //     public function index(): Response
    //     {
    //         return $this->render('link/index.html.twig', [
    //             'controller_name' => 'LinkController',
    //         ]);
    //     }
    
    #[Route('/links', name: 'link_browse')]
    public function browse(LinkRepository $linkRepository, Security $security): Response
    {
        $user = $security->getUser();
        
        // Si c'est un super admin, on peut afficher tous les liens
        if ($this->isGranted('ROLE_SUPER_ADMIN')) {
            $links = $linkRepository->findAll();
        } else {
            // Sinon, on ne récupère que les liens créés par l'utilisateur connecté
            $links = $linkRepository->findBy(['creator' => $user]);
        }
        
        // Passer les liens et l'utilisateur au template
        return $this->render('link/browse.html.twig', [
            'links' => $links,
            'user' => $user
        ]);
    }
    
    #[Route('/links/create', name: 'link_create')]
    public function createLink(Request $request, EntityManagerInterface $em, Security $security): Response
    {
        // Créer une nouvelle instance de Link
        $link = new Link();
        
        // Assigner l'utilisateur connecté comme créateur
        $user = $security->getUser();
        if ($user) {
            $link->setCreator($user);
        } else {
            // Rediriger vers la page de login si l'utilisateur n'est pas connecté
            return $this->redirectToRoute('app_login');  
        }
        
        $form = $this->createForm(LinkType::class, $link, [
            'current_user' => $this->getUser(),
            'validation_groups' => ['Default', 'create'],
        ]);
        $form->handleRequest($request);
        
        if ($form->isSubmitted() && $form->isValid()) {
            // Générer les 8 caractères aléatoires
            $code = substr(str_shuffle(str_repeat('ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789', 5)), 0, 8);
            $link->setEightRandomCharacters($code);
            
            // Créer l'URL
            $baseUrl = $request->getSchemeAndHttpHost(); // Ex: http://localhost
            $url = $baseUrl . "/open/" . urlencode($link->getCustomerName()) . '-' . $code;
            $link->setUrl($url);
            
            // Définir automatiquement creator/updater 
            if (!$link->getCreator()) {
                $link->setCreator($this->getUser());
            }
            if (!$link->getUpdater()) {
                $link->setUpdater($this->getUser());
            }
            
            // createdAt/updatedAt are set automatically by the Link entity lifecycle callbacks
            
            $em->persist($link);
            $em->flush();
            
            return $this->render('link/success.html.twig', [
                'generated_url' => $url,
            ]);
        }
        
        // Retourner le formulaire et afficher la génération du lien avant soumission
        return $this->render('link/create.html.twig', [
            'form' => $form->createView(),
            'generated_link' => $link->getUrl(), // Affichage du lien généré dans la vue
        ]);
    }
    
    
    #[Route('/open/{fullUrl}', name: 'link_open', methods: ['GET'])]
    public function showOpenPage(string $fullUrl, Request $request, EntityManagerInterface $em): Response
    {
        $currentUrl = $request->getSchemeAndHttpHost() . '/open/' . $fullUrl;

        $link = $em->getRepository(Link::class)->findOneBy(['url' => $currentUrl]);

        if (!$link) {
            return $this->redirectToRoute('homepage');
        }

        return $this->render('link/confirm_open.html.twig', [
            'link' => $link,
            'fullUrl' => $fullUrl,
        ]);
    }


    #[Route('/open/{fullUrl}/validate', name: 'link_open_validate', methods: ['POST'])]
    public function openLinkValidate(
        string $fullUrl, 
        Request $request, 
        EntityManagerInterface $em
    ): Response {
        // Recréer l'URL complète attendue
        $currentUrl = $request->getSchemeAndHttpHost() . '/open/' . $fullUrl;
        
        // Chercher le lien en base via l'URL complète
        $link = $em->getRepository(Link::class)->findOneBy(['url' => $currentUrl]);
        
        if (!$link) {
            return $this->redirectToRoute('homepage'); // lien inexistant
        }
        
        $now = new \DateTime('now', new \DateTimeZone('Europe/Paris'));
            $status = false; // statut invalide
        if ($link->isPermanent()) {
            // Lien permanent, toujours valide
            $status = true;
       } else {
           $tz = new \DateTimeZone('Europe/Paris');

        $start = $link->getStartDate();
        $end = $link->getEndDate();

        $status = false;

        if ($start !== null && $end !== null) {
            // Ici PHP sait que $start et $end sont des objets DateTime
            /** @var \DateTime $start */
            /** @var \DateTime $end */
            $start->setTimezone($tz);
            $end->setTimezone($tz);

            if ($link->isStatus() && $start < $now && $end > $now) {
                $status = true;
            }
        }
    }
        // Enregistrer l'ouverture dans l'historique
        $history = new \App\Entity\OpeningHistory();
        $history->setStatus($status);
        $history->setOpeningDate($now);
        $history->setLink($link);
        $history->setUrl($link->getUrl());
        $history->setCustomerName($link->getCustomerName());
        
        // Récupérer l'IP du client (stocker en clair pour conserver la forme d'origine)
        $clientIp = $request->getClientIp();
        if ($clientIp !== null) {
            $history->setIpAddress($clientIp);
        }
        
        $em->persist($history);
        $em->flush();
        
        // Si le lien est valide, exécuter le script Python
        if ($status) {
            $duration = 2; // secondes
            $command = escapeshellcmd('python3 ' . $this->getParameter('kernel.project_dir') . '/test_gpio.py ' . $duration);
            exec($command);
        }
        
        // Affichage de la page
        if ($status) {
            return $this->render('link/open.html.twig', [
                'message' => 'Portail ouvert !',
            ]);
        } else {
            return $this->render('link/expired.html.twig');
        }
    }

    
    #[Route('/links/{id}/edit', name: 'link_edit')]
    public function updateLink(Request $request, EntityManagerInterface $em, Security $security, int $id): Response
    {
        // Assigner l'utilisateur connecté comme modificateur
        $user = $security->getUser();
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }
        
        // Récupérer le lien existant
        $link = $em->getRepository(Link::class)->find($id);
        
        // Vérifier si le lien existe
        if (!$link) {
            throw $this->createNotFoundException('Le lien demandé n\'existe pas');
        }
        
        // Vérifier que l'utilisateur est autorisé à modifier ce lien
        // Les super admins peuvent modifier tous les liens, les autres uniquement leurs liens
        if (!$this->isGranted('ROLE_SUPER_ADMIN') && $link->getCreator() !== $user) {
            throw $this->createAccessDeniedException('Vous n\'êtes pas autorisé à modifier ce lien');
        }
        
        // Créer le formulaire spécifique pour l'édition
        $form = $this->createForm(LinkEditType::class, $link, [
            'current_user' => $user,
        ]);
        
        // Traiter le formulaire
        $form->handleRequest($request);
        
        if ($form->isSubmitted() && $form->isValid()) {
            // Mettre à jour l'utilisateur qui modifie le lien
            $link->setUpdater($user);
            
            // updatedAt is set automatically by the Link entity lifecycle callbacks
            
            // Enregistrer les modifications
            $em->persist($link);
            $em->flush();
            
            $this->addFlash('success', 'Le lien a été modifié avec succès.');
            
            return $this->redirectToRoute('link_browse');
            
            $this->addFlash('success', 'Le lien a été mis à jour avec succès');
            return $this->redirectToRoute('link_browse');
        }
        
        return $this->render('link/edit.html.twig', [
            'form' => $form->createView(),
            'link' => $link,
        ]);
    }

    #[Route('/links/{id}/delete', name: 'link_delete', methods: ['POST'])]
    public function deleteLink(Request $request, EntityManagerInterface $em, CsrfTokenManagerInterface $csrfTokenManager, int $id): Response
    {
        $link = $em->getRepository(Link::class)->find($id);
        if (!$link) {
            throw $this->createNotFoundException('Le lien demandé n\'existe pas');
        }

        if (!$this->isGranted('ROLE_SUPER_ADMIN')) {
            throw new AccessDeniedHttpException('Seuls les super administrateurs peuvent supprimer des liens.');
        }

        if ($this->isCsrfTokenValid('delete-link-' . $link->getId(), $request->request->get('_token'))) {
            $em->remove($link);
            $em->flush();
            $this->addFlash('success', 'Le lien a été supprimé avec succès.');
        }
        return $this->redirectToRoute('link_browse');
    }
}
