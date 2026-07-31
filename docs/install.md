# Installation

Plantons nécessite PHP 8.2 ou une version ultérieure. Déposer le contenu du dépôt dans l'espace web, en veillant à ce que PHP puisse écrire dans `data/`, `logs/` et `uploads/`.

Pour une installation en production, définir :

```text
PLANTONS_ENV=production
PLANTONS_DEBUG=false
PLANTONS_BASE_URL=https://exemple.fr/plantons
PLANTONS_TIMEZONE=Europe/Paris
```

Lors d'une installation dans un sous-dossier, par exemple `https://exemple.fr/PlanArbreBEA/`, le préfixe est détecté automatiquement. Si l'hébergement ne transmet pas correctement ce chemin à PHP, définir aussi `PLANTONS_BASE_PATH=/PlanArbreBEA`.

Par défaut, Plantons utilise des liens compatibles avec les hébergements mutualisés sans réécriture Apache : `index.php?route=…`. Aucun accès à la racine du domaine ni activation de `mod_rewrite` n'est nécessaire. Pour utiliser des URL courtes sur un hébergement dont `.htaccess` est actif, définir `PLANTONS_ROUTING_MODE=pretty`.

Les fichiers de données et les journaux sont protégés par `.htaccess` sur Apache. Sur Nginx, ajouter une règle équivalente interdisant l'accès HTTP aux extensions `.json`, `.geojson` et `.log`.

## Configuration d'un territoire

Avant la mise en service, adapter `config/config.php` :

- `app.name` : nom du projet affiché ;
- `territory.name` et `territory.center` : identité et centre de carte ;
- `data_sources` : chemins locaux et URL de service des GeoJSON ;
- `planting.allowed_species` : essences proposées au citoyen ;
- `planting.objectives` : objectifs de plantation proposés ;
- `security.max_photos_per_proposal` : fixé à `3` pour la version 1.0.

L'extension PHP `gd` doit être activée : elle contrôle et stocke les trois photos éventuelles en WebP. Le navigateur optimise les photos à 500 Ko maximum avant leur envoi ; le serveur applique la même limite.

Les demandes peuvent contenir une adresse e-mail facultative. Elle est enregistrée afin de notifier le demandeur lors d'une validation, d'un rejet, d'une plantation ou d'un déplacement de localisation. L'envoi des e-mails sera activé avec le module d'administration.

La procédure de recherche des sources administratives officielles et de génération de `territoire.geojson` et `communes-deleguees.geojson` est décrite dans [la documentation GeoJSON](geojson.md).

## Comptes et administration

Chaque inscription crée un compte `contributeur`. Pour créer le premier administrateur, définir `PLANTONS_BOOTSTRAP_ADMIN_EMAIL` avec l'adresse e-mail de ce compte avant son inscription. Un administrateur peut ensuite promouvoir les contributeurs depuis `/admin/utilisateurs`.

## Notifications par e-mail

Plantons envoie les invitations, les liens de réinitialisation de mot de passe et les changements de statut via SMTP. Les objets et corps de ces e-mails sont centralisés dans `config/config.php`, sous `notifications.messages`.

Sur l'hébergement, définir au minimum :

```text
PLANTONS_BASE_URL=https://apps.sebastienmeunier.info/PlanArbreBEA
PLANTONS_SMTP_PASSWORD=mot-de-passe-de-la-boite-mail
```

`PLANTONS_BASE_URL` garantit que les liens reçus par e-mail fonctionnent depuis l'hébergement FTP. Ne placez jamais le mot de passe SMTP dans `config/config.php`, dans le ZIP ou dans Git. La cause exacte d'un échec est inscrite dans `logs/application.log`, sans y enregistrer le mot de passe.

## Données affichées publiquement

Les services publics `api/data/propositions` et `api/data/dons` ne retournent que le statut, l'essence, les objectifs ou caractéristiques de l'arbre et une position arrondie. Les coordonnées exactes, noms, e-mails, adresses, commentaires et photos restent dans les fichiers privés et ne sont disponibles que pour les administrateurs authentifiés. Le niveau d'arrondi se règle avec `privacy.public_coordinate_precision` dans `config/config.php`.
