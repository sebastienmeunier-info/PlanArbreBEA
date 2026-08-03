# Plantons +

Plantons + est un logiciel libre de gestion du patrimoine arboré et des propositions citoyennes de plantation. Il est conçu pour les collectivités, avec une installation simple sur hébergement PHP et un stockage pérenne en JSON/GeoJSON.

## État du projet

Le Sprint 0 installe le socle technique : front controller, routage, configuration, gestion d'erreurs, journalisation et première page publique. Les modules cartographiques, formulaire citoyen, administration et API seront ajoutés dans les sprints suivants.

Le Sprint 1 ajoute la carte interactive et le dépôt sécurisé des premières propositions citoyennes. La limite territoriale et ses secteurs sont fournis dans `data/territoire.geojson` et `data/communes-deleguees.geojson` par défaut.

## Prérequis

- PHP 8.2 ou supérieur ;
- extensions PHP `json`, `mbstring` et `fileinfo` ;
- droits d'écriture PHP pour `data/`, `logs/` et `uploads/`.

## Installation

1. Copier le dépôt sur l'hébergement.
2. Configurer le serveur web pour servir ce dossier, ou conserver la réécriture Apache fournie.
3. Donner les droits d'écriture au processus PHP sur `data`, `logs` et `uploads`.
4. Ouvrir l'URL de l'application.

La configuration est centralisée dans `config/config.php`. Les réglages locaux sensibles ne doivent pas être versionnés : utiliser les variables d'environnement documentées dans [docs/install.md](docs/install.md).

## Transposition sur un autre territoire

Le déploiement sur une autre collectivité ne nécessite pas de modifier le code. Dans `config/config.php`, renseigner le nom du projet et du territoire, le centre de carte, les URL ou chemins des fichiers GeoJSON, les essences autorisées et les objectifs de plantation. Les envois citoyens peuvent contenir jusqu'à trois photos, limitées à 1 Mo chacune.

## Architecture

```text
app/            Cœur MVC et modules métier
config/         Configuration et routes
data/           Données JSON et GeoJSON (non versionnées)
public/         Ressources statiques
resources/      Vues, modèles d'e-mails et traductions
uploads/        Photos, documents, exports et sauvegardes
logs/           Journaux applicatifs
```

## Licence

GNU Affero General Public License v3.0 ou ultérieure. Voir [LICENSE](LICENSE).
