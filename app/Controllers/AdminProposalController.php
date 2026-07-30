<?php

declare(strict_types=1);

namespace Plantons\Controllers;

use InvalidArgumentException;
use Plantons\Core\Application;
use Plantons\Core\Request;
use Plantons\Core\Response;
use Plantons\Repositories\UserRepository;
use Plantons\Services\AuthService;
use Plantons\Services\GeoJsonStore;
use Plantons\Services\NotificationService;
use Plantons\Services\TerritoryService;
use RuntimeException;

final class AdminProposalController
{
    public function __construct(private readonly Application $app) {}

    public function index(Request $request): never
    {
        $auth = $this->auth();
        $this->guard($auth);
        header('Location: ' . $this->app->routeUrl('/mon-compte?onglet=plantations'), true, 303);
        exit;
    }

    public function update(Request $request): never
    {
        $this->updateForSource($request, 'proposals', 'plantations');
    }

    public function updateDonation(Request $request): never
    {
        $this->updateForSource($request, 'donations', 'dons');
    }

    private function updateForSource(Request $request, string $source, string $tab): never
    {
        try {
            $auth = $this->auth();
            $current = $this->guard($auth);
            if (!$auth->verifyCsrf((string) $request->input('csrf_token'))) {
                Response::html('Session expirée.', 403);
            }
            $status = (string) $request->input('status');
            $statuses = $this->app->config('proposals')['status_labels'];
            if (!array_key_exists($status, $statuses)) {
                throw new InvalidArgumentException('Statut invalide.');
            }
            $longitude = $this->coordinate($request->input('longitude'));
            $latitude = $this->coordinate($request->input('latitude'));
            $planting = $this->app->config('planting');
            $species = trim((string) $request->input('species'));
            $objectives = array_values(array_unique(array_filter((array) $request->input('objectives', []), 'is_string')));
            if (!in_array($species, $planting['allowed_species'], true)) { throw new InvalidArgumentException('Essence invalide.'); }
            $changes = ['status' => $status, 'species' => $species];
            if ($source === 'donations') {
                $conditioning = (string) $request->input('conditioning');
                $treeSize = (string) $request->input('tree_size');
                if (!array_key_exists($conditioning, $planting['tree_conditioning'])) { throw new InvalidArgumentException('Conditionnement invalide.'); }
                if (!array_key_exists($treeSize, $planting['tree_sizes'])) { throw new InvalidArgumentException('Taille invalide.'); }
                $changes += ['conditioning' => $conditioning, 'tree_size' => $treeSize, 'objectives' => []];
            } else {
                if ($objectives === [] || array_diff($objectives, array_keys($planting['objectives'])) !== []) { throw new InvalidArgumentException('Objectifs invalides.'); }
                if (count($objectives) > $planting['max_objectives_per_proposal']) { throw new InvalidArgumentException('Trois objectifs maximum sont autorisés.'); }
                $changes['objectives'] = $objectives;
            }
            $store = new GeoJsonStore();
            $sources = $this->app->config('data_sources');
            $proposalId = (string) $request->input('id');
            $previous = $this->featureById($store->read($sources[$source]['file']), $proposalId);
            if ($previous === null) {
                throw new RuntimeException('Proposition introuvable.');
            }
            $territory = $store->read($sources['territory']['file']);
            $territoryService = new TerritoryService();
            if (!$territoryService->contains($territory, $longitude, $latitude)) {
                throw new InvalidArgumentException('La localisation doit rester dans le territoire autorisé.');
            }
            $municipalities = $store->read($sources['delegated_municipalities']['file']);
            $store->updateFeature($sources[$source]['file'], $proposalId, $changes + [
                'delegated_municipality' => $territoryService->municipality($municipalities, $longitude, $latitude),
                'updated_at' => date(DATE_ATOM),
                'updated_by' => $current['id'],
            ], [$longitude, $latitude]);
            $this->notifyChanges($previous, $status, $species, $longitude, $latitude, $territoryService->municipality($municipalities, $longitude, $latitude));
            header('Location: ' . $this->app->routeUrl('/mon-compte?onglet=' . $tab), true, 303);
            exit;
        } catch (InvalidArgumentException|RuntimeException $exception) {
            Response::html(htmlspecialchars($exception->getMessage(), ENT_QUOTES, 'UTF-8'), 422);
        }
    }

    private function coordinate(mixed $value): float
    {
        if (!is_numeric($value)) { throw new InvalidArgumentException('Coordonnées invalides.'); }
        return (float) $value;
    }

    private function featureById(array $collection, string $id): ?array
    {
        foreach ($collection['features'] ?? [] as $feature) {
            if (($feature['properties']['id'] ?? null) === $id) {
                return $feature;
            }
        }
        return null;
    }

    private function notifyChanges(array $previous, string $status, string $species, float $longitude, float $latitude, ?string $municipality): void
    {
        $notifications = $this->app->config('notifications');
        $properties = $previous['properties'] ?? [];
        $recipient = (string) ($properties['email'] ?? '');
        if (($notifications['status_changes'] ?? false) !== true || filter_var($recipient, FILTER_VALIDATE_EMAIL) === false) {
            return;
        }

        $coordinates = $previous['geometry']['coordinates'] ?? [];
        $moved = count($coordinates) >= 2 && ((float) $coordinates[0] !== $longitude || (float) $coordinates[1] !== $latitude);
        $user = $this->repository()->findByEmail($recipient);
        $variables = [
            'project_name' => (string) $this->app->config('app')['name'],
            'first_name' => (string) ($user['first_name'] ?? $properties['author'] ?? ''),
            'last_name' => (string) ($user['last_name'] ?? ''),
            'proposal_id' => (string) ($properties['id'] ?? ''),
            'species' => $species,
            'location' => (string) (($properties['address'] ?? '') ?: $municipality ?: sprintf('%.5f, %.5f', $latitude, $longitude)),
            'url' => $this->app->absoluteRouteUrl('/'),
        ];
        $notifier = new NotificationService($this->app->config('smtp'), $notifications, $this->app->logger());
        if (($properties['status'] ?? '') !== $status) {
            $event = match ($status) {
                'validee' => 'proposal_validated',
                'refusee', 'rejetee' => 'proposal_rejected',
                'arbre_plante', 'realisee' => 'tree_planted',
                default => null,
            };
            if ($event !== null) {
                $notifier->send($event, $recipient, $variables);
            }
        }
        if ($moved) {
            $notifier->send('location_moved', $recipient, $variables);
        }
    }

    private function guard(AuthService $auth): array
    {
        $user = $auth->currentUser();
        if (!in_array($user['role'] ?? null, ['administrateur', 'super_administrateur'], true)) {
            Response::html('Accès réservé aux administrateurs.', 403);
        }
        return $user;
    }

    private function auth(): AuthService
    {
        return new AuthService(new UserRepository($this->app->config('auth')['users_file']), $this->app->config('auth'));
    }

    private function repository(): UserRepository
    {
        return new UserRepository($this->app->config('auth')['users_file']);
    }
}
