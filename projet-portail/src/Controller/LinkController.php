<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\Request;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use App\Service\LoggerHelper;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Bundle\SecurityBundle\Security;
use App\Repository\LinkRepository;
use App\Entity\Link;
use App\Form\LinkType;
use App\Form\LinkEditType;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use App\Entity\Settings;

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
    public function createLink(Request $request, EntityManagerInterface $em, Security $security, #[Autowire(service: 'monolog.logger.crud')] LoggerInterface $logger): Response
    {
        $loggerHelper = new LoggerHelper($logger);
        
        try {
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
                // Générer les 4 caractères aléatoires
                $code = substr(str_shuffle(str_repeat('ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789', 5)), 0, 4);
                $link->setFourRandomCharacters($code);
                
                $path = "/open/" . urlencode($link->getCustomerName()) . '-' . $code;
                $link->setUrl($path);
                $displayUrl = $request->getSchemeAndHttpHost() . $path;
                
                // Définir automatiquement creator/updater 
                if (!$link->getCreator()) {
                    $link->setCreator($this->getUser());
                }
                if (!$link->getUpdater()) {
                    $link->setUpdater($this->getUser());
                }
                $start = $form->get('startDate')->getData();
                $end   = $form->get('endDate')->getData();

                if ($start instanceof \DateTimeInterface) {
                    $start = (new \DateTimeImmutable($start->format('Y-m-d H:i:s'), new \DateTimeZone('Europe/Paris')));
                }

                if ($end instanceof \DateTimeInterface) {
                    $end = (new \DateTimeImmutable($end->format('Y-m-d H:i:s'), new \DateTimeZone('Europe/Paris')));
                }

                $link->setStartDate($start);
                $link->setEndDate($end);

                $em->persist($link);
                $em->flush();
                
                $loggerHelper->logInfo('Lien créé', ['linkName' => $link->getCustomerName(), 'code' => $code]);
                
                return $this->render('link/success.html.twig', [
                    'generated_url' => $displayUrl,
                    'is_permanent' => $link->isPermanent(),
                    'start_date' => $link->getStartDate(),
                    'end_date' => $link->getEndDate(),
                    'phone_number' => $link->getCustomerPhoneNumber(),
                    'email' => $link->getCustomerEmail(),
                ]);
            }

            return $this->render('link/create.html.twig', [
                'form' => $form->createView(),
                'generated_link' => $link->getUrl(),
            ]);
        } catch (\Exception $e) {
            $this->addFlash('error', 'Erreur lors de la création du lien');
            $loggerHelper->logError('Erreur lors de la création du lien', $e);
            return $this->redirectToRoute('link_browse');
        }
    }
    
    
    #[Route('/open/{fullUrl}', name: 'link_open', methods: ['GET'])]
    public function showOpenPage(string $fullUrl, Request $request, EntityManagerInterface $em): Response
    {
       //$currentUrl = $request->getSchemeAndHttpHost() . '/open/' . $fullUrl;

        //$link = $em->getRepository(Link::class)->findOneBy(['url' => $currentUrl]);
        $path = '/open/' . $fullUrl;
        $link = $em->getRepository(Link::class)->findOneBy(['url' => $path]);
        $settings = $em->getRepository(Settings::class)->findOneBy([]);

        if (!$link) {
            return $this->redirectToRoute('homepage');
        }
        return $this->render('link/confirm_open.html.twig', [
            'link' => $link,
            'fullUrl' => $fullUrl,
            'rgpdFile' => $settings?->getRgpdFile(),
            'portalText' => $settings?->getPortalText(),
            'error' => null,
        ]);
    }


    #[Route('/open/{fullUrl}/validate', name: 'link_open_validate', methods: ['POST'])]
    public function openLinkValidate(
        string $fullUrl, 
        Request $request, 
        EntityManagerInterface $em,
        #[Autowire(service: 'monolog.logger.crud')]
        LoggerInterface $logger
    ): Response {
        $loggerHelper = new LoggerHelper($logger);
        
        try {
            $path = '/open/' . $fullUrl;
             if (!$request->request->get('rgpdConsent')) {

                $settings = $em->getRepository(Settings::class)->findOneBy([]);
                $link = $em->getRepository(Link::class)->findOneBy(['url' => $path]);


                return $this->render('link/confirm_open.html.twig', [
                    'link' => $link,
                    'fullUrl' => $fullUrl,
                    'rgpdFile' => $settings?->getRgpdFile(),
                    'portalText' => $settings?->getPortalText(),
                    'error' => 'Vous devez accepter les conditions RGPD.',
                ]);
            }
            
            // Chercher le lien en base via l'URL complète
            $link = $em->getRepository(Link::class)->findOneBy(['url' => $path]);

            if (!$link) {
                return $this->redirectToRoute('homepage');
            }
            
           // utilisation de DateTimeImmutable pour comparaison non destructive
            $now = new \DateTimeImmutable('now', new \DateTimeZone('Europe/Paris'));

            $status = $link->isActiveAt($now);
            
            // Enregistrer l'ouverture dans l'historique
            $history = new \App\Entity\OpeningHistory();
            $history->setStatus($status);
            $history->setOpeningDate($now);
            $history->setLink($link);
            $history->setUrl($link->getUrl());
            $history->setCustomerName($link->getCustomerName());
            
            // Récupérer l'IP du client
            $clientIp = $request->getClientIp();
            if ($clientIp !== null) {
                $history->setIpAddress($clientIp);
            }
            
            $em->persist($history);
            $em->flush();
            
            $loggerHelper->logInfo('Ouverture de lien enregistrée', [
                'linkId' => $link->getId(),
                'status' => $status ? 'ACTIVE' : 'INACTIVE',
                'customerName' => $link->getCustomerName(),
                'ip' => $clientIp,
            ]);
            
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
        } catch (\Exception $e) {
            $loggerHelper->logError('Erreur lors de la validation d\'ouverture', $e, ['url' => $fullUrl]);
            return $this->redirectToRoute('homepage');
        }
    }

    
    #[Route('/links/{id}/edit', name: 'link_edit')]
    public function updateLink(Request $request, EntityManagerInterface $em, Security $security, #[Autowire(service: 'monolog.logger.crud')] LoggerInterface $logger, int $id): Response
    {
        $loggerHelper = new LoggerHelper($logger);
        
        try {
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
                
                // Enregistrer les modifications
                $em->persist($link);
                $em->flush();
                
                $this->addFlash('success', 'Le lien a été modifié avec succès.');
                $loggerHelper->logInfo('Lien modifié', ['id' => $id, 'customerName' => $link->getCustomerName()]);
                
                return $this->redirectToRoute('link_browse');
            }
            
            return $this->render('link/edit.html.twig', [
                'form' => $form->createView(),
                'link' => $link,
            ]);
        } catch (\Exception $e) {
            $this->addFlash('error', 'Erreur lors de la modification du lien');
            $loggerHelper->logError('Erreur lors de la modification du lien', $e, ['linkId' => $id]);
            return $this->redirectToRoute('link_browse');
        }
    }

    #[Route('/links/{id}/delete', name: 'link_delete', methods: ['POST'])]
    public function deleteLink(Request $request, EntityManagerInterface $em, CsrfTokenManagerInterface $csrfTokenManager, #[Autowire(service: 'monolog.logger.crud')] LoggerInterface $logger, int $id): Response
    {
        $loggerHelper = new LoggerHelper($logger);
        
        try {
            $link = $em->getRepository(Link::class)->find($id);
            if (!$link) {
                throw $this->createNotFoundException('Le lien demandé n\'existe pas');
            }

            if (!$this->isGranted('ROLE_SUPER_ADMIN')) {
                throw new AccessDeniedHttpException('Seuls les super administrateurs peuvent supprimer des liens.');
            }

            if ($this->isCsrfTokenValid('delete-link-' . $link->getId(), $request->request->get('_token'))) {
                $customerName = $link->getCustomerName();
                $em->remove($link);
                $em->flush();
                $this->addFlash('success', 'Le lien a été supprimé avec succès.');
                $loggerHelper->logInfo('Lien supprimé', ['id' => $id, 'customerName' => $customerName]);
            }
            return $this->redirectToRoute('link_browse');
        } catch (\Exception $e) {
            $this->addFlash('error', 'Erreur lors de la suppression du lien');
            $loggerHelper->logError('Erreur lors de la suppression du lien', $e, ['linkId' => $id]);
            return $this->redirectToRoute('link_browse');
        }
    }
}
