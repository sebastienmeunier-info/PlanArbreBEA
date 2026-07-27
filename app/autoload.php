<?php

declare(strict_types=1);

spl_autoload_register(function (string $class): void {

    $file = __DIR__ . '/' . $class . '.php';

    if (is_file($file)) {
        require_once $file;
    }

});
