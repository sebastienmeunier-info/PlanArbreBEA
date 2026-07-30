# Produire les limites GeoJSON d'un territoire

Plantons utilise deux fichiers de référence dans `data/` :

- `territoire.geojson` : les contours des communes actuelles qui composent le territoire autorisé ;
- `communes-deleguees.geojson` : les contours des communes déléguées, pour afficher le bon nom de commune au moment d'une proposition.

Les deux fichiers sont des `FeatureCollection` GeoJSON. Ils sont volontairement séparés : le premier sert au contrôle de l'emprise du projet, le second à l'identification précise de la commune déléguée.

## Sources officielles

La source recommandée est [l'API Découpage administratif de geo.api.gouv.fr](https://geo.api.gouv.fr/decoupage-administratif/communes). Elle diffuse les communes courantes et leurs contours au format GeoJSON, sans clé d'API. Les communes associées et déléguées sont fournies par [l'endpoint dédié](https://geo.api.gouv.fr/decoupage-administratif/communes-associees-deleguees).

Pour une production nationale, une analyse SIG avancée ou un traitement hors ligne, l'alternative officielle est la couche `COMMUNE_ASSOCIEE_OU_DELEGUEE` de la base [ADMIN EXPRESS](https://guides.data.gouv.fr/reutiliser-des-donnees/utiliser-les-api-geographiques/utiliser-lapi-decoupage-administratif). Elle peut être téléchargée et filtrée dans QGIS.

## Informations à réunir avant la génération

Il faut connaître :

1. le ou les codes INSEE des communes actuelles qui constituent le territoire ;
2. le ou les codes de département correspondants.

Pour retrouver ces informations, rechercher la commune dans l'API, par exemple :

```text
https://geo.api.gouv.fr/communes?nom=Baugé-en-Anjou&fields=code,nom,codeDepartement
```

Contrôler le résultat avant de générer les fichiers, notamment pour les communes ayant un homonyme. Les contours et rattachements administratifs évoluent : il est conseillé de relancer la génération après une modification communale.

## Génération automatique

Le script `scripts/generate_geojson.py` ne nécessite aucune bibliothèque Python externe. Installer Python 3.9 ou plus récent, ouvrir un terminal à la racine du dépôt puis lancer :

```bash
python scripts/generate_geojson.py --communes 49018 --departments 49 --output-dir data
```

Cet exemple produit les deux fichiers dans `data/` pour la commune actuelle de code INSEE `49018`, située dans le département `49`. La commande télécharge les contours courants, puis sélectionne les communes déléguées dont le chef-lieu est rattaché à la ou aux communes indiquées.

Un territoire intercommunal ou réparti sur plusieurs départements s'écrit ainsi :

```bash
python scripts/generate_geojson.py --communes 12345 12346 --departments 12 34 --output-dir data
```

À la fin, le script indique le nombre de limites enregistrées dans chaque fichier. Un fichier `communes-deleguees.geojson` vide est valide lorsqu'aucune commune déléguée n'est rattachée au territoire.

## Vérification et mise en service

1. Ouvrir les deux fichiers dans QGIS ou un visualiseur GeoJSON et vérifier que les contours correspondent au périmètre attendu.
2. Conserver les propriétés `code` et `nom` fournies par la source : Plantons les utilise pour identifier les communes.
3. Placer les fichiers validés dans `data/` ou renseigner leurs URL dans `config/config.php`, section `data_sources`.
4. Tester une proposition à l'intérieur, puis à l'extérieur, du territoire.

Ne pas éditer les coordonnées à la main sauf correction ponctuelle contrôlée dans un SIG : une nouvelle exécution du script remplace les deux fichiers de sortie.
