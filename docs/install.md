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
