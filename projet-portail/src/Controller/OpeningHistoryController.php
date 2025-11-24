<?php

namespace App\Controller;

use App\Repository\OpeningHistoryRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class OpeningHistoryController extends AbstractController
{
    //#[Route('/opening/history', name: 'app_opening_history')]
    //public function index(): Response
    //{
      //  return $this->render('opening_history/index.html.twig', [
        //    'controller_name' => 'OpeningHistoryController',
        //]);
    //}

    #[Route('/opening/history', name: 'opening_history_browse')]
    public function browse(OpeningHistoryRepository $openingHistoryRepository): Response
{
     // Récupérer tous les historiques d'ouverture
     $openingHistories = $openingHistoryRepository->findAll();

     // Passer les historiques d'ouverture au template
     return $this->render('opening_history/browse.html.twig', [
         'openingHistories' => $openingHistories,
    ]);
}
}
