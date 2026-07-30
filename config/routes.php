<?php

declare(strict_types=1);

use PlanArbreBEA\Controllers\HomeController;
use PlanArbreBEA\Controllers\GeoJsonController;
use PlanArbreBEA\Controllers\ProposalController;
use PlanArbreBEA\Controllers\AuthController;
use PlanArbreBEA\Controllers\AdminUserController;

return [
    ['GET', '/', [HomeController::class, 'index']],
    ['GET', '/sante', [HomeController::class, 'health']],
    ['GET', '/api/data/territoire', [GeoJsonController::class, 'territory']],
    ['GET', '/api/data/communes-deleguees', [GeoJsonController::class, 'municipalities']],
    ['GET', '/api/data/arbres', [GeoJsonController::class, 'trees']],
    ['GET', '/api/data/propositions', [GeoJsonController::class, 'proposals']],
    ['POST', '/api/propositions', [ProposalController::class, 'create']],
    ['GET', '/inscription', [AuthController::class, 'registerForm']],
    ['POST', '/inscription', [AuthController::class, 'register']],
    ['GET', '/connexion', [AuthController::class, 'loginForm']],
    ['POST', '/connexion', [AuthController::class, 'login']],
    ['POST', '/deconnexion', [AuthController::class, 'logout']],
    ['GET', '/mot-de-passe-oublie', [AuthController::class, 'forgotForm']],
    ['POST', '/mot-de-passe-oublie', [AuthController::class, 'forgot']],
    ['GET', '/reinitialiser-mot-de-passe', [AuthController::class, 'resetForm']],
    ['POST', '/reinitialiser-mot-de-passe', [AuthController::class, 'reset']],
    ['GET', '/admin/utilisateurs', [AdminUserController::class, 'index']],
    ['POST', '/admin/utilisateurs/promouvoir', [AdminUserController::class, 'promote']],
];
