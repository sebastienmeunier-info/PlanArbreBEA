<?php

declare(strict_types=1);

namespace Plantons\Controllers;

use Plantons\Core\Application;
use Plantons\Core\Request;
use Plantons\Core\Response;
use Plantons\Repositories\UserRepository;
use Plantons\Services\AuthService;

final class AboutController
{
    public function __construct(private readonly Application $app) {}

    public function index(Request $request): never
    {
        $auth = new AuthService(new UserRepository($this->app->config('auth')['users_file']), $this->app->config('auth'));
        Response::html($this->app->view()->render('about', [
            'application' => $this->app->config('app'),
            'territory' => $this->app->config('territory'),
            'csrfToken' => $auth->csrfToken(),
            'user' => $auth->currentUser(),
        ]));
    }
}
