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

## Personnaliser la page « À propos »

La page accessible par le lien « À propos » est définie dans `resources/templates/about.php`. Pour l’adapter à une collectivité :

1. ouvrir ce fichier avec un éditeur de texte ;
2. modifier les trois sections : objectif de l’application, adaptation au territoire et modalités de déploiement ;
3. conserver la structure HTML existante (`<section>`, `<h2>` et `<p>`) afin de garder la mise en page ;
4. transférer le fichier modifié sur l’hébergement, dans le même dossier.

Le nom de l’application affiché dans le premier paragraphe provient automatiquement de `app.name` dans `config/config.php`. Il n’est donc pas nécessaire de le modifier dans la page « À propos ».

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

### YunoHost — application `my_webapp`

Pour rendre les variables d’environnement disponibles à l’application PHP, éditer le fichier du pool PHP-FPM :

```bash
nano /etc/php/8.4/fpm/pool.d/my_webapp.conf
```

Ajouter ou compléter les lignes suivantes, en remplaçant le mot de passe :

```ini
env[PLANTONS_SMTP_PASSWORD] = "mot de passe a renseigner"
env[PLANTONS_NOTIFICATION_CCI] = "plantons@sebastienmeunier.info"
```

`PLANTONS_NOTIFICATION_CCI` est facultative : si elle est renseignée, cette adresse reçoit tous les e-mails transactionnels en copie cachée. Après modification, recharger le service PHP-FPM afin que les nouvelles variables soient prises en compte.

## Données affichées publiquement

Les services publics `api/data/propositions` et `api/data/dons` ne retournent que le statut, l'essence, les objectifs ou caractéristiques de l'arbre et une position arrondie. Les coordonnées exactes, noms, e-mails, adresses, commentaires et photos restent dans les fichiers privés et ne sont disponibles que pour les administrateurs authentifiés. Le niveau d'arrondi se règle avec `privacy.public_coordinate_precision` dans `config/config.php`.

## Protection des données

Avant la mise en service, renseigner les mentions de la section `privacy` de `config/config.php` : identité du responsable de traitement, adresse de contact, adresse du DPO si applicable, base légale et durées de conservation validées par la collectivité. La page `/donnees-personnelles` rend ces informations accessibles aux visiteurs. Chaque utilisateur connecté peut télécharger ses données depuis cette page.

Les changements de statut par e-mail requièrent l’accord explicite du demandeur pour chaque proposition. Les demandes d’effacement, de limitation ou d’opposition sont adressées au contact indiqué dans la politique de confidentialité afin que la collectivité vérifie les éventuelles obligations de conservation avant intervention.
