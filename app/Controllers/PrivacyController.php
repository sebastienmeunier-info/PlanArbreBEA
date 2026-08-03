<?php

declare(strict_types=1);

namespace Plantons\Controllers;

use Plantons\Core\Application;
use Plantons\Core\Request;
use Plantons\Core\Response;
use Plantons\Repositories\UserRepository;
use Plantons\Services\AuthService;

final class PrivacyController
{
    public function __construct(private readonly Application $app) {}

    public function index(Request $request): never
    {
        $auth = new AuthService(new UserRepository($this->app->config('auth')['users_file']), $this->app->config('auth'));
        Response::html($this->app->view()->render('privacy', [
            'application' => $this->app->config('app'),
            'privacy' => $this->app->config('privacy'),
            'csrfToken' => $auth->csrfToken(),
            'user' => $auth->currentUser(),
        ]));
    }
}
