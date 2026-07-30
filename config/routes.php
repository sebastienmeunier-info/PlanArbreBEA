<?php

declare(strict_types=1);

use PlanArbreBEA\Controllers\HomeController;

return [
    ['GET', '/', [HomeController::class, 'index']],
    ['GET', '/sante', [HomeController::class, 'health']],
];
