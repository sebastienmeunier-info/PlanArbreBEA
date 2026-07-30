<?php

declare(strict_types=1);

namespace PlanArbreBEA\Controllers;

use InvalidArgumentException;
use PlanArbreBEA\Core\Application;
use PlanArbreBEA\Core\Request;
use PlanArbreBEA\Core\Response;
use PlanArbreBEA\Services\GeoJsonStore;
use PlanArbreBEA\Services\PhotoService;
use PlanArbreBEA\Services\TerritoryService;
use RuntimeException;

final class ProposalController
{
    public function __construct(private readonly Application $app) {}

    public function create(Request $request): never
    {
        try {
            $this->verifyCsrf((string) $request->input('csrf_token'));
            $planting = $this->app->config('planting');
            $longitude = $this->number($request->input('longitude'), 'Longitude invalide.');
            $latitude = $this->number($request->input('latitude'), 'Latitude invalide.');
            $species = trim((string) $request->input('species'));
            $objectives = array_values(array_filter((array) $request->input('objectives', []), 'is_string'));
            $author = mb_substr(trim((string) $request->input('author')), 0, 80);
            $comment = mb_substr(trim((string) $request->input('comment')), 0, 1000);

            if (!in_array($species, $planting['allowed_species'], true)) {
                throw new InvalidArgumentException('Veuillez sélectionner une essence autorisée.');
            }
            if ($objectives === [] || array_diff($objectives, array_keys($planting['objectives'])) !== []) {
                throw new InvalidArgumentException('Veuillez sélectionner au moins un objectif valide.');
            }

            $store = new GeoJsonStore();
            $sources = $this->app->config('data_sources');
            $territory = $store->read($sources['territory']['file']);
            $territoryService = new TerritoryService();
            if (($territory['features'] ?? []) === []) {
                throw new RuntimeException('Le territoire n’est pas encore configuré.');
            }
            if (!$territoryService->contains($territory, $longitude, $latitude)) {
                throw new InvalidArgumentException('Le point sélectionné est situé hors du territoire autorisé.');
            }

            $municipalities = $store->read($sources['delegated_municipalities']['file']);
            $photos = (new PhotoService())->store($request->files('photos'), $this->app->config('security'), $this->app->config('paths')['uploads']);
            $feature = [
                'type' => 'Feature',
                'geometry' => ['type' => 'Point', 'coordinates' => [$longitude, $latitude]],
                'properties' => [
                    'id' => 'PA-' . date('Y') . '-' . strtoupper(substr(bin2hex(random_bytes(5)), 0, 8)),
                    'status' => 'a_valider',
                    'created_at' => date(DATE_ATOM),
                    'author' => $author,
                    'species' => $species,
                    'objectives' => $objectives,
                    'comment' => $comment,
                    'address' => mb_substr(trim((string) $request->input('address')), 0, 255),
                    'delegated_municipality' => $territoryService->municipality($municipalities, $longitude, $latitude),
                    'photos' => $photos,
                ],
            ];
            $store->appendFeature($sources['proposals']['file'], $feature);
            Response::json(['message' => 'Votre proposition a été enregistrée et sera examinée par la collectivité.', 'feature' => $feature], 201);
        } catch (InvalidArgumentException $exception) {
            Response::json(['message' => $exception->getMessage()], 422);
        } catch (RuntimeException $exception) {
            $this->app->logger()->error($exception->getMessage());
            Response::json(['message' => $exception->getMessage()], 500);
        }
    }

    private function verifyCsrf(string $token): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) { session_start(); }
        if ($token === '' || !hash_equals((string) ($_SESSION['csrf_token'] ?? ''), $token)) {
            throw new InvalidArgumentException('Votre session a expiré. Veuillez recharger la page.');
        }
    }

    private function number(mixed $value, string $message): float
    {
        if (!is_numeric($value)) { throw new InvalidArgumentException($message); }
        return (float) $value;
    }
}
