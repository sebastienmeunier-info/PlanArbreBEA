<?php

declare(strict_types=1);

namespace Plantons\Core;

final class Autoloader
{
    public static function register(string $applicationDirectory): void
    {
        spl_autoload_register(static function (string $class) use ($applicationDirectory): void {
            $prefix = 'Plantons\\';
            if (!str_starts_with($class, $prefix)) {
                return;
            }

            $relativeClass = substr($class, strlen($prefix));
            $file = $applicationDirectory . DIRECTORY_SEPARATOR
                . str_replace('\\', DIRECTORY_SEPARATOR, $relativeClass) . '.php';

            if (is_file($file)) {
                require $file;
            }
        });
    }
}
