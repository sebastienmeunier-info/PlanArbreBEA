# Installation

PlanArbreBEA nécessite PHP 8.2 ou une version ultérieure. Déposer le contenu du dépôt dans l'espace web, en veillant à ce que PHP puisse écrire dans `data/`, `logs/` et `uploads/`.

Pour une installation en production, définir :

```text
PLANARBRE_ENV=production
PLANARBRE_DEBUG=false
PLANARBRE_BASE_URL=https://exemple.fr/planarbrebea
PLANARBRE_TIMEZONE=Europe/Paris
```

Les fichiers de données et les journaux sont protégés par `.htaccess` sur Apache. Sur Nginx, ajouter une règle équivalente interdisant l'accès HTTP aux extensions `.json`, `.geojson` et `.log`.

## Configuration d'un territoire

Avant la mise en service, adapter `config/config.php` :

- `app.name` : nom du projet affiché ;
- `territory.name` et `territory.center` : identité et centre de carte ;
- `data_sources` : chemins locaux et URL de service des GeoJSON ;
- `planting.allowed_species` : essences proposées au citoyen ;
- `planting.objectives` : objectifs de plantation proposés ;
- `security.max_photos_per_proposal` : fixé à `3` pour la version 1.0.

L'extension PHP `gd` doit être activée : elle convertit les trois photos éventuelles en WebP avant leur enregistrement.
