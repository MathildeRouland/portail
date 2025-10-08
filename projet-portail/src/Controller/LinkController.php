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
    public function browse(LinkRepository $linkRepository): Response
    {
        // Récupérer tous les liens
        $links = $linkRepository->findAll();

        // Passer les liens au template
        return $this->render('link/browse.html.twig', [
            'links' => $links,
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
          return $this->redirectToRoute('app_login');  // Rediriger vers la page de login si l'utilisateur n'est pas connect�
      }

      $form = $this->createForm(LinkType::class, $link, [
        'current_user' => $this->getUser(),
    ]);
    $form->handleRequest($request);

    if ($form->isSubmitted() && $form->isValid()) {
        // Générer les 4 caractères aléatoires
        $code = substr(str_shuffle(str_repeat('ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789', 5)), 0, 4);
        $link->setFourRandomCharacters($code);

         // Créer l'URL
         $baseUrl = $request->getSchemeAndHttpHost(); // Ex: http://localhost
         $url = $baseUrl . "/" . urlencode($link->getCustomerName()) . '-' . $code;
         $link->setUrl($url);

        // Définir automatiquement creator/updater 
        if (!$link->getCreator()) {
            $link->setCreator($this->getUser());
        }
        if (!$link->getUpdater()) {
            $link->setUpdater($this->getUser());
        }

        $link->setCreatedAt(new \DateTime());
        $link->setUpdatedAt(new \DateTime());

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

//   // Fonction pour générer un lien basé sur le nom du client et 4 caractères aléatoires
//   protected function generateLinkUrl(Link $link): string
//   {
//       // Générer un lien unique basé sur le nom du client et 4 caractères aléatoires
//       $randomChars = substr(str_shuffle('0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ'), 0, 4);
//       return $link->getCustomerName() . '-' . $randomChars; // Exemple de génération de lien
//   }

#[Route('/{fullUrl}', name: 'link_open')]
public function openLink(string $fullUrl, Request $request, EntityManagerInterface $em): Response
{
    // Recréer l'URL complète attendue
    $currentUrl = $request->getSchemeAndHttpHost() . '/' . $fullUrl;

    // Chercher le lien en base via l'URL complète
    $link = $em->getRepository(Link::class)->findOneBy(['url' => $currentUrl]);

    if (!$link) {
        return $this->redirectToRoute('homepage'); // lien inexistant
    }

    $now = new \DateTime('now', new \DateTimeZone('Europe/Paris'));
    $status = false; // statut invalide
    $start = $link->getStartDate()->setTimezone(new \DateTimeZone('Europe/Paris'));
    $end = $link->getEndDate()->setTimezone(new \DateTimeZone('Europe/Paris'));
    if ($link->isStatus() || $start < $now || $end > $now) {
        $status = true; // tentative valide
    }
    

    // Enregistrer l'ouverture dans l'historique
    $history = new \App\Entity\OpeningHistory();
    $history->setStatus($status);
    $history->setOpeningDate($now);

    // Récupérer l'IP du client
    $clientIp = $request->getClientIp();
    if ($clientIp !== null) {
        $history->setIpAdress(ip2long($clientIp)); // convertir IP en int
    }

    $em->persist($history);
    $em->flush();

    // Si le lien est valide, ex�cuter le script Python
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
}


