<?php

namespace App\Service;

use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

class MailerService
{
    private const MAX_RETRIES = 3;
    private const RETRY_DELAY_MS = 1000; // 1 seconde

    public function __construct(
        private MailerInterface $mailer,
        #[Autowire(service: 'monolog.logger.crud')]
        private LoggerInterface $logger,
        #[\Symfony\Component\DependencyInjection\Attribute\Autowire('%env(MAILER_ERROR_RECIPIENT)%')]
        private string $errorEmailRecipient
    ) {}

    /**
     * Envoie un email avec retry automatique en cas d'erreur SMTP
     */
    public function sendEmailWithRetry(Email $email, int $retryCount = 0): bool
    {
        try {
            $this->mailer->send($email);
            $this->logger->info('Email sent successfully', [
                'to' => $email->getTo(),
                'subject' => $email->getSubject(),
            ]);
            return true;
        } catch (\Exception $e) {
            $retryCount++;
            
            if ($retryCount < self::MAX_RETRIES) {
                $this->logger->warning('Email send failed, retrying...', [
                    'attempt' => $retryCount,
                    'max_retries' => self::MAX_RETRIES,
                    'error' => $e->getMessage(),
                    'to' => $email->getTo(),
                ]);
                
                // Attendre avant de réessayer (backoff)
                usleep(self::RETRY_DELAY_MS * 1000 * $retryCount);
                
                return $this->sendEmailWithRetry($email, $retryCount);
            }
            
            // Tous les retries ont échoué
            $this->logger->error('Email send failed after retries', [
                'attempts' => $retryCount,
                'error' => $e->getMessage(),
                'error_code' => $e->getCode(),
                'error_class' => get_class($e),
                'to' => $email->getTo(),
                'subject' => $email->getSubject(),
            ]);
            
            return false;
        }
    }

    /**
     * Envoie les logs d'erreur par email
     */
    public function sendErrorLog(
        string $message,
        string $file,
        int $line,
        string $function,
        string $exceptionMessage,
        string $trace,
        ?string $recipient = null
    ): bool {
        $recipient = $recipient ?? $this->errorEmailRecipient;
        
        $htmlContent = $this->buildErrorEmailHtml(
            $message,
            $file,
            $line,
            $function,
            $exceptionMessage,
            $trace
        );
        
        $email = (new Email())
            ->from('noreply@portail.local')
            ->to($recipient)
            ->subject('[ERROR] ' . $message)
            ->html($htmlContent);
        
        return $this->sendEmailWithRetry($email);
    }

    /**
     * Construit le contenu HTML de l'email d'erreur
     */
    private function buildErrorEmailHtml(
        string $message,
        string $file,
        int $line,
        string $function,
        string $exceptionMessage,
        string $trace
    ): string {
        $timestamp = date('Y-m-d H:i:s');
        $hostname = gethostname();
        
        return <<<HTML
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: Arial, sans-serif; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background-color: #dc3545; color: white; padding: 15px; border-radius: 5px; margin-bottom: 20px; }
        .section { margin-bottom: 20px; border: 1px solid #ddd; padding: 15px; border-radius: 5px; }
        .section-title { font-weight: bold; background-color: #f8f9fa; padding: 10px; margin-bottom: 10px; }
        .label { font-weight: bold; color: #666; }
        .code { background-color: #f5f5f5; padding: 10px; border-left: 3px solid #dc3545; overflow-x: auto; }
        pre { margin: 0; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h2>⚠️ Erreur détectée</h2>
        </div>
        
        <div class="section">
            <div class="section-title">Informations de l'erreur</div>
            <p><span class="label">Message:</span> $message</p>
            <p><span class="label">Exception:</span> $exceptionMessage</p>
        </div>
        
        <div class="section">
            <div class="section-title">Localisation</div>
            <p><span class="label">Fichier:</span> $file</p>
            <p><span class="label">Ligne:</span> $line</p>
            <p><span class="label">Fonction:</span> $function</p>
        </div>
        
        <div class="section">
            <div class="section-title">Stack Trace</div>
            <div class="code"><pre>$trace</pre></div>
        </div>
        
        <div class="section">
            <div class="section-title">Contexte</div>
            <p><span class="label">Serveur:</span> $hostname</p>
            <p><span class="label">Timestamp:</span> $timestamp</p>
        </div>
    </div>
</body>
</html>
HTML;
    }

    /**
     * Défini le destinataire par défaut pour les erreurs
     */
    public function setErrorEmailRecipient(string $recipient): void
    {
        $this->errorEmailRecipient = $recipient;
    }
}
