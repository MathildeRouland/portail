<?php

namespace App\Command;

use App\Entity\OpeningHistory;
use App\Repository\OpeningHistoryRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:clean-opening-history',
    description: 'Supprime les événements d\'historique d\'ouverture de plus de 13 mois',
    hidden: false,
)]
class CleanOpeningHistoryCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private OpeningHistoryRepository $openingHistoryRepository,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption(
                'months',
                'm',
                InputOption::VALUE_OPTIONAL,
                'Nombre de mois à garder (par défaut: 13)',
                13
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        // Récupérer le nombre de mois depuis l'option
        $months = (int) $input->getOption('months');

        // Calculer la date limite
        $dateLimit = new \DateTime('now');
        $dateLimit->modify("-$months months");

        $io->info("Suppression des événements antérieurs au " . $dateLimit->format('Y-m-d H:i:s') . " ($months mois en arrière)");

        // Récupérer les événements à supprimer
        $oldEvents = $this->entityManager
            ->createQuery('
                SELECT oh FROM App\Entity\OpeningHistory oh 
                WHERE oh.openingDate < :dateLimit
            ')
            ->setParameter('dateLimit', $dateLimit)
            ->getResult();

        $count = count($oldEvents);

        if ($count === 0) {
            $io->success('Aucun événement à supprimer.');
            return Command::SUCCESS;
        }

        // Supprimer les événements
        foreach ($oldEvents as $event) {
            $this->entityManager->remove($event);
        }

        $this->entityManager->flush();

        $io->success("$count événement(s) supprimé(s) avec succès.");

        return Command::SUCCESS;
    }
}
