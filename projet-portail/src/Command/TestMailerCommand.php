<?php

namespace App\Command;

use App\Service\MailerService;
use App\Service\LoggerHelper;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class TestMailerCommand extends Command
{
    public function __construct(
        private MailerService $mailerService,
        #[Autowire(service: 'monolog.logger.cron')]
        private LoggerInterface $logger
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setName('app:test-mailer')
            ->setDescription('Test du mailer avec envoi d\'erreur');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln('Test du mailer avec retry...');
        
        // Créer LoggerHelper avec le bon logger
        $loggerHelper = new LoggerHelper($this->logger, $this->mailerService);

        try {
            // Simuler une erreur pour tester l'envoi d'email
            throw new \Exception('Erreur de test pour le mailer');
        } catch (\Exception $e) {
            $output->writeln('Erreur capturée, envoi par email...');
            $loggerHelper->logError('Erreur de test', $e);
            $output->writeln('Email envoyé (ou en retry)');
        }

        return Command::SUCCESS;
    }
}
