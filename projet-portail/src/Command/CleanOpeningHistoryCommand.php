<?php

namespace App\Command;

use App\Entity\OpeningHistory;
use App\Repository\OpeningHistoryRepository;
use App\Service\LoggerHelper;
use Doctrine\ORM\EntityManagerInterface;
use App\Service\MailerService;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
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
        #[Autowire(service: 'monolog.logger.cron')]
        private LoggerInterface $logger,
        private MailerService $mailerService,
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
        $loggerHelper = new LoggerHelper($this->logger, $this->mailerService);

        try {
            // Récupérer le nombre de mois depuis l'option
            $months = (int) $input->getOption('months');

            // Calculer la date limite
            $dateLimit = new \DateTime('now');
            $dateLimit->modify("-$months months");

            $message = "Suppression des événements antérieurs au " . $dateLimit->format('Y-m-d H:i:s') . " ($months mois en arrière)";
            $io->info($message);
            $loggerHelper->logInfo($message);

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
                $loggerHelper->logInfo('Nettoyage historique: aucun événement à supprimer.');
                return Command::SUCCESS;
            }

            // Supprimer les événements
            foreach ($oldEvents as $event) {
                $this->entityManager->remove($event);
            }

            $this->entityManager->flush();

            $successMessage = "$count événement(s) supprimé(s) avec succès.";
            $io->success($successMessage);
            $loggerHelper->logInfo("Nettoyage historique réussi: $successMessage", ['monthsKept' => $months, 'eventsDeleted' => $count]);

            return Command::SUCCESS;

        } catch (\Exception $e) {
            $io->error("Erreur lors de la suppression de l'historique d'ouverture");
            $loggerHelper->logError("Erreur lors de la suppression de l'historique d'ouverture", $e);

            return Command::FAILURE;
        }
    }
}
