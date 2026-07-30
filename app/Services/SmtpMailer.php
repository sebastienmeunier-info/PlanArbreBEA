<?php

declare(strict_types=1);

namespace Plantons\Services;

use RuntimeException;

final class SmtpMailer
{
    /** @param array<string,mixed> $config */
    public function __construct(private readonly array $config) {}

    public function send(string $recipient, string $subject, string $message): void
    {
        if (!extension_loaded('openssl')) { throw new RuntimeException('L’extension PHP OpenSSL est nécessaire pour l’envoi SMTP sécurisé.'); }
        if ((string) $this->config['password'] === '') { throw new RuntimeException('Le mot de passe SMTP n’est pas configuré.'); }
        $scheme = $this->config['encryption'] === 'ssl' ? 'ssl://' : '';
        $socket = @stream_socket_client($scheme . $this->config['host'] . ':' . $this->config['port'], $errorNumber, $error, 15, STREAM_CLIENT_CONNECT);
        if (!is_resource($socket)) { throw new RuntimeException('Connexion SMTP impossible : ' . $error); }
        stream_set_timeout($socket, 15);
        try {
            $this->expect($socket, [220]);
            $this->command($socket, 'EHLO localhost', [250]);
            $this->command($socket, 'AUTH LOGIN', [334]);
            $this->command($socket, base64_encode((string) $this->config['username']), [334]);
            $this->command($socket, base64_encode((string) $this->config['password']), [235]);
            $this->command($socket, 'MAIL FROM:<' . $this->config['from_email'] . '>', [250]);
            $this->command($socket, 'RCPT TO:<' . $recipient . '>', [250, 251]);
            $this->command($socket, 'DATA', [354]);
            $headers = ['From: ' . $this->config['from_name'] . ' <' . $this->config['from_email'] . '>', 'To: <' . $recipient . '>', 'Subject: ' . $subject, 'MIME-Version: 1.0', 'Content-Type: text/plain; charset=UTF-8'];
            fwrite($socket, implode("\r\n", $headers) . "\r\n\r\n" . str_replace("\n.", "\n..", $message) . "\r\n.\r\n");
            $this->expect($socket, [250]);
            $this->command($socket, 'QUIT', [221]);
        } finally {
            fclose($socket);
        }
    }

    /** @param resource $socket @param list<int> $codes */
    private function command($socket, string $command, array $codes): void
    {
        fwrite($socket, $command . "\r\n");
        $this->expect($socket, $codes);
    }

    /** @param resource $socket @param list<int> $codes */
    private function expect($socket, array $codes): void
    {
        $response = '';
        do {
            $line = fgets($socket, 1024);
            if ($line === false) { throw new RuntimeException('Réponse SMTP absente.'); }
            $response .= $line;
        } while (isset($line[3]) && $line[3] === '-');
        $code = (int) substr($response, 0, 3);
        if (!in_array($code, $codes, true)) { throw new RuntimeException('Erreur SMTP : ' . trim($response)); }
    }
}
