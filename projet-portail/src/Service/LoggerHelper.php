<?php

namespace App\Service;

use Psr\Log\LoggerInterface;

class LoggerHelper
{
    public function __construct(
        private LoggerInterface $logger,
        private ?MailerService $mailerService = null
    ) {}

    /**
     * Enregistre une erreur avec les détails du fichier, ligne et fonction
     */
    public function logError(string $message, \Exception $exception, array $context = []): void
    {
        $trace = $exception->getTrace();
        
        // Récupérer le premier appel de la stack trace (celui qui a déclenché l'erreur)
        $caller = $trace[0] ?? null;
        
        $file = $caller['file'] ?? 'unknown';
        $line = $caller['line'] ?? 'unknown';
        $function = $caller['function'] ?? 'unknown';
        $class = $caller['class'] ?? '';
        
        // Formater le nom de la fonction/méthode
        $functionName = $class ? $class . '::' . $function : $function;
        
        // Créer un message clair avec localisation
        $detailedMessage = "$message ($functionName en ligne $line)";
        
        // Enrichir le contexte
        $enrichedContext = array_merge($context, [
            'file' => $file,
            'line' => $line,
            'function' => $functionName,
            'exception_message' => $exception->getMessage(),
            'exception_code' => $exception->getCode(),
            'trace' => $exception->getTraceAsString(),
        ]);
        
        $this->logger->error($detailedMessage, $enrichedContext);
        
        // Envoyer l'erreur par email
        if ($this->mailerService) {
            $this->mailerService->sendErrorLog(
                $message,
                $file,
                $line,
                $functionName,
                $exception->getMessage(),
                $exception->getTraceAsString()
            );
        }
    }

    /**
     * Enregistre une info avec détails de la fonction appelante
     */
    public function logInfo(string $message, array $context = []): void
    {
        // Récupérer la stack trace pour trouver la fonction qui a appelé logInfo
        $trace = debug_backtrace();
        
        // trace[0] = logInfo
        // trace[1] = fonction qui appelle logInfo (ce qu'on cherche)
        $caller = $trace[1] ?? null;
        
        $file = $caller['file'] ?? 'unknown';
        $line = $caller['line'] ?? 'unknown';
        $function = $caller['function'] ?? 'unknown';
        $class = $caller['class'] ?? '';
        
        // Formater le nom de la fonction/méthode
        $functionName = $class ? $class . '::' . $function : $function;
        
        // Créer un message clair avec localisation
        $detailedMessage = "$message ($functionName en ligne $line)";
        
        // Enrichir le contexte
        $enrichedContext = array_merge($context, [
            'file' => $file,
            'line' => $line,
            'function' => $functionName,
        ]);
        
        $this->logger->info($detailedMessage, $enrichedContext);
    }
}
