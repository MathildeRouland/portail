<?php

namespace App\Controller;

use App\Repository\OpeningHistoryRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class OpeningHistoryController extends AbstractController
{
    #[Route('/opening/history', name: 'opening_history_browse')]
    public function browse(OpeningHistoryRepository $openingHistoryRepository): Response
    {
        $openingHistories = $openingHistoryRepository->findAll();
        return $this->render('opening_history/browse.html.twig', [
            'openingHistories' => $openingHistories,
        ]);
    }
}