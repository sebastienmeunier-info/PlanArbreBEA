# PlanArbreBEA

PlanArbreBEA est un logiciel libre de gestion du patrimoine arboré et des propositions citoyennes de plantation. Il est conçu pour les collectivités, avec une installation simple sur hébergement PHP et un stockage pérenne en JSON/GeoJSON.

## État du projet

Le Sprint 0 installe le socle technique : front controller, routage, configuration, gestion d'erreurs, journalisation et première page publique. Les modules cartographiques, formulaire citoyen, administration et API seront ajoutés dans les sprints suivants.

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
