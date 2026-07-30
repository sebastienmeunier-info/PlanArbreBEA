<?php

declare(strict_types=1);

namespace PlanArbreBEA\Controllers;

use PlanArbreBEA\Core\Application;
use PlanArbreBEA\Core\Request;
use PlanArbreBEA\Core\Response;
use PlanArbreBEA\Services\GeoJsonStore;

final class GeoJsonController
{
    public function __construct(private readonly Application $app) {}

    public function territory(Request $request): never { $this->respond('territory'); }
    public function municipalities(Request $request): never { $this->respond('delegated_municipalities'); }
    public function trees(Request $request): never { $this->respond('trees'); }
    public function proposals(Request $request): never { $this->respond('proposals'); }

    private function respond(string $source): never
    {
        $config = $this->app->config('data_sources')[$source];
        Response::json((new GeoJsonStore())->read($config['file']));
    }
}
