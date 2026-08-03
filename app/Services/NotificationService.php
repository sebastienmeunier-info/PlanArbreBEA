<?php

declare(strict_types=1);

namespace Plantons\Services;

use Plantons\Core\Logger;
use RuntimeException;

/** Sends configured transactional notifications without disrupting user actions. */
final class NotificationService
{
    /** @param array<string,mixed> $smtpConfig @param array<string,mixed> $notifications */
    public function __construct(
        private readonly array $smtpConfig,
        private readonly array $notifications,
        private readonly Logger $logger,
    ) {}

    /** @param array<string,string> $variables */
    public function send(string $event, string $recipient, array $variables = []): bool
    {
        if (filter_var($recipient, FILTER_VALIDATE_EMAIL) === false) {
            return false;
        }

        $template = $this->notifications['messages'][$event] ?? null;
        if (!is_array($template)) {
            $this->logger->warning('Notification inconnue.', ['event' => $event]);
            return false;
        }

        try {
            (new SmtpMailer($this->smtpConfig))->send(
                $recipient,
                $this->replace((string) ($template['subject'] ?? ''), $variables),
                $this->replace((string) ($template['body'] ?? ''), $variables),
                (string) ($this->notifications['notification_cci'] ?? ''),
            );
            $this->logger->info('Notification envoyée.', ['event' => $event, 'recipient' => $recipient]);
            return true;
        } catch (RuntimeException $exception) {
            $this->logger->warning('Notification non envoyée : ' . $exception->getMessage(), ['event' => $event, 'recipient' => $recipient]);
            return false;
        }
    }

    /** @param array<string,string> $variables */
    private function replace(string $text, array $variables): string
    {
        $replacements = [];
        foreach ($variables as $key => $value) {
            $replacements['{{' . $key . '}}'] = $value;
        }
        return strtr($text, $replacements);
    }
}
