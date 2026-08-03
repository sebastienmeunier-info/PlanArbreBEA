<?php

declare(strict_types=1);

namespace Plantons\Core;

final class Logger
{
    public const DEBUG = 100;
    public const INFO = 200;
    public const WARNING = 300;
    public const ERROR = 400;

    public function __construct(private readonly string $file, private readonly int $minimumLevel) {}

    public function error(string $message, array $context = []): void { $this->write(self::ERROR, 'ERROR', $message, $context); }
    public function warning(string $message, array $context = []): void { $this->write(self::WARNING, 'WARNING', $message, $context); }
    public function info(string $message, array $context = []): void { $this->write(self::INFO, 'INFO', $message, $context); }

    private function write(int $level, string $label, string $message, array $context): void
    {
        if ($level < $this->minimumLevel) { return; }
        $line = sprintf("[%s] %s %s %s\n", date('c'), $label, $message, json_encode($context, JSON_UNESCAPED_UNICODE));
        error_log($line, 3, $this->file);
    }
}
