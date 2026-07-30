<?php

declare(strict_types=1);

use PlanArbreBEA\Controllers\HomeController;
use PlanArbreBEA\Controllers\GeoJsonController;
use PlanArbreBEA\Controllers\ProposalController;

return [
    ['GET', '/', [HomeController::class, 'index']],
    ['GET', '/sante', [HomeController::class, 'health']],
    ['GET', '/api/data/territoire', [GeoJsonController::class, 'territory']],
    ['GET', '/api/data/communes-deleguees', [GeoJsonController::class, 'municipalities']],
    ['GET', '/api/data/arbres', [GeoJsonController::class, 'trees']],
    ['POST', '/api/propositions', [ProposalController::class, 'create']],
];
