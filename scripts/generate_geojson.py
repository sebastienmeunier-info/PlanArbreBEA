#!/usr/bin/env python3
\"\"\"Génère les limites territoriales utilisées par Plantons.

Les données sont téléchargées depuis geo.api.gouv.fr, le service public de
diffusion du découpage administratif français. Le script n'a aucune dépendance
externe : Python 3.9 ou plus récent suffit.
\"\"\"

from __future__ import annotations

import argparse
import json
import sys
from pathlib import Path
from typing import Any, Iterable
from urllib.parse import urlencode
from urllib.request import Request, urlopen


API_BASE_URL = "https://geo.api.gouv.fr"


def get_json(path: str, parameters: dict[str, Any]) -> Any:
    """Retourne la réponse JSON de l'API géographique nationale."""
    query = urlencode(parameters, doseq=True)
    url = f"{API_BASE_URL}{path}?{query}" if query else f"{API_BASE_URL}{path}"
    request = Request(url, headers={"User-Agent": "Plantons/1.0 (+https://github.com/sebastienmeunier-info/plantons)"})

    try:
        with urlopen(request, timeout=60) as response:
            return json.load(response)
    except OSError as error:
        raise RuntimeError(f"Impossible de télécharger {url}: {error}") from error


def as_features(payload: Any) -> list[dict[str, Any]]:
    """Normalise une réponse GeoJSON Feature ou FeatureCollection."""
    if isinstance(payload, dict) and payload.get("type") == "FeatureCollection":
        features = payload.get("features", [])
        if isinstance(features, list):
            return features
    if isinstance(payload, dict) and payload.get("type") == "Feature":
        return [payload]
    raise RuntimeError("La réponse reçue n'est pas un GeoJSON valide.")


def feature_collection(features: Iterable[dict[str, Any]]) -> dict[str, Any]:
    return {"type": "FeatureCollection", "features": list(features)}


def download_territory(commune_codes: list[str]) -> dict[str, Any]:
    """Télécharge le contour de chaque commune actuelle du territoire."""
    territory_features: list[dict[str, Any]] = []
    for commune_code in commune_codes:
        payload = get_json(
            f"/communes/{commune_code}",
            {"format": "geojson", "geometry": "contour"},
        )
        features = as_features(payload)
        if not features:
            raise RuntimeError(f"Aucun contour reçu pour la commune {commune_code}.")
        territory_features.extend(features)
    return feature_collection(territory_features)


def download_delegated_communes(
    department_codes: list[str], parent_commune_codes: list[str]
) -> dict[str, Any]:
    """Télécharge les communes déléguées rattachées aux communes du territoire."""
    parent_codes = set(parent_commune_codes)
    delegated_codes: set[str] = set()

    for department_code in department_codes:
        metadata = get_json(
            "/communes_associees_deleguees",
            {"codeDepartement": department_code, "type": "commune-deleguee"},
        )
        if not isinstance(metadata, list):
            raise RuntimeError("La liste des communes déléguées n'est pas valide.")
        for commune in metadata:
            if isinstance(commune, dict) and str(commune.get("chefLieu", "")) in parent_codes:
                delegated_codes.add(str(commune.get("code")))

    delegated_features: list[dict[str, Any]] = []
    for department_code in department_codes:
        payload = get_json(
            "/communes_associees_deleguees",
            {
                "codeDepartement": department_code,
                "type": "commune-deleguee",
                "format": "geojson",
                "geometry": "contour",
            },
        )
        for feature in as_features(payload):
            properties = feature.get("properties", {})
            feature_code = str(properties.get("code", properties.get("code_insee", "")))
            if feature_code in delegated_codes:
                delegated_features.append(feature)

    return feature_collection(delegated_features)


def write_geojson(path: Path, content: dict[str, Any]) -> None:
    path.parent.mkdir(parents=True, exist_ok=True)
    path.write_text(json.dumps(content, ensure_ascii=False, indent=2) + "\n", encoding="utf-8")


def parse_arguments() -> argparse.Namespace:
    parser = argparse.ArgumentParser(
        description="Télécharge territoire.geojson et communes-deleguees.geojson pour Plantons."
    )
    parser.add_argument(
        "--communes",
        nargs="+",
        required=True,
        metavar="CODE_INSEE",
        help="Code(s) INSEE des communes actuelles qui composent le territoire.",
    )
    parser.add_argument(
        "--departments",
        nargs="+",
        required=True,
        metavar="CODE_DEPARTEMENT",
        help="Code(s) département à parcourir pour retrouver les communes déléguées.",
    )
    parser.add_argument(
        "--output-dir",
        type=Path,
        default=Path("data"),
        help="Dossier de destination (par défaut : data).",
    )
    return parser.parse_args()


def main() -> int:
    arguments = parse_arguments()
    try:
        territory = download_territory(arguments.communes)
        delegated_communes = download_delegated_communes(
            arguments.departments, arguments.communes
        )
        territory_path = arguments.output_dir / "territoire.geojson"
        delegated_path = arguments.output_dir / "communes-deleguees.geojson"
        write_geojson(territory_path, territory)
        write_geojson(delegated_path, delegated_communes)
    except RuntimeError as error:
        print(f"Erreur : {error}", file=sys.stderr)
        return 1

    print(f"{territory_path} : {len(territory['features'])} limite(s) communale(s).")
    print(f"{delegated_path} : {len(delegated_communes['features'])} commune(s) déléguée(s).")
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
