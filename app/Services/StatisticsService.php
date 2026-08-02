<?php

declare(strict_types=1);

namespace Plantons\Services;

final class StatisticsService
{
    /**
     * @param array<int,array<string,mixed>> $features
     * @param array<int,array<string,mixed>> $users
     * @param array<string,string> $statusLabels
     * @return array<string,mixed>
     */
    public function summarize(array $features, array $users, array $statusLabels): array
    {
        $statuses = [];
        foreach ($statusLabels as $status => $label) {
            $statuses[$this->normalizeStatus((string) $status)] = $label;
        }
        $contributors = [];
        foreach ($users as $user) {
            if (in_array($user['role'] ?? '', ['contributeur', 'administrateur', 'super_administrateur'], true)) {
                $contributors[mb_strtolower((string) ($user['email'] ?? ''))] = [
                    'name' => trim((string) ($user['first_name'] ?? '') . ' ' . (string) ($user['last_name'] ?? '')),
                    'email' => (string) ($user['email'] ?? ''),
                    'total' => 0,
                    'successful' => 0,
                ];
            }
        }

        $bySector = [];
        $statusTotals = array_fill_keys(array_keys($statuses), 0);
        foreach ($features as $feature) {
            $properties = (array) ($feature['properties'] ?? []);
            $status = $this->normalizeStatus((string) ($properties['status'] ?? 'a_valider'));
            $statuses[$status] ??= ucfirst(str_replace('_', ' ', $status));
            $statusTotals[$status] ??= 0;
            $sector = trim((string) ($properties['sector'] ?? $properties['delegated_municipality'] ?? '')) ?: 'Non renseigné';
            $bySector[$sector] ??= array_fill_keys(array_keys($statuses), 0);
            foreach (array_keys($statuses) as $key) {
                $bySector[$sector][$key] ??= 0;
            }
            $bySector[$sector][$status]++;
            $statusTotals[$status]++;

            $email = mb_strtolower((string) ($properties['email'] ?? ''));
            if (isset($contributors[$email])) {
                $contributors[$email]['total']++;
                if (in_array($status, ['validee', 'arbre_plante'], true)) {
                    $contributors[$email]['successful']++;
                }
            }
        }

        ksort($bySector, SORT_NATURAL | SORT_FLAG_CASE);
        $contributions = array_sum(array_column($contributors, 'total'));
        $leaders = array_values(array_filter($contributors, static fn(array $contributor): bool => $contributor['successful'] > 0));
        usort($leaders, static fn(array $left, array $right): int => [$right['successful'], $right['total'], $left['name']] <=> [$left['successful'], $left['total'], $right['name']]);

        return [
            'statuses' => $statuses,
            'by_sector' => $bySector,
            'status_totals' => $statusTotals,
            'contributor_count' => count($contributors),
            'average_contributions' => $contributors === [] ? 0 : round($contributions / count($contributors), 1),
            'leaders' => array_slice($leaders, 0, 10),
        ];
    }

    private function normalizeStatus(string $status): string
    {
        return match ($status) {
            'rejetee' => 'refusee',
            'realisee' => 'arbre_plante',
            default => $status,
        };
    }
}
