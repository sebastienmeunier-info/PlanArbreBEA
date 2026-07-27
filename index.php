<?php

declare(strict_types=1);

require_once __DIR__ . '/app/autoload.php';

Config::init();

?><!DOCTYPE html>
<html lang="fr">

<head>

    <meta charset="utf-8">

    <title><?= htmlspecialchars(Config::APP_NAME) ?></title>

    <meta name="viewport"
          content="width=device-width, initial-scale=1">

    <meta name="description"
          content="Proposez des emplacements de plantation d'arbres pour Baugé-en-Anjou.">

    <meta name="theme-color"
          content="#2E7D32">

    <link rel="manifest"
          href="manifest.webmanifest">

    <link rel="icon"
          href="public/images/icons/icon-192.png">

    <!-- Leaflet -->

    <link rel="stylesheet"
          href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">

    <!-- Bootstrap Icons -->

    <link rel="stylesheet"
          href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css">

    <!-- Feuille de style -->

    <link rel="stylesheet"
          href="public/css/style.css">

</head>

<body>

<header class="site-header">

    <div class="container">
            <div class="brand">

            <img src="public/images/logo.svg"
                 alt="Logo PlanArbreBEA"
                 class="logo">

            <div>

                <h1><?= htmlspecialchars(Config::APP_NAME) ?></h1>

                <p class="subtitle">
                    Proposez un emplacement de plantation
                    pour renforcer le patrimoine arboré
                    de Baugé-en-Anjou.
                </p>

            </div>

        </div>

    </div>

</header>

<main class="container">

    <!-- ===================================================== -->
    <!-- Carte -->
    <!-- ===================================================== -->

    <section class="card map-card">

        <div class="card-header">

            <h2>

                <i class="bi bi-map"></i>

                Carte interactive

            </h2>

        </div>

        <div id="map"></div>

        <p class="help">

            Cliquez sur la carte pour sélectionner
            un emplacement de plantation.

        </p>

    </section>

    <!-- ===================================================== -->
    <!-- Formulaire -->
    <!-- ===================================================== -->

    <form id="proposalForm"
          enctype="multipart/form-data"
          novalidate>
            <!-- ===================================================== -->
        <!-- Localisation -->
        <!-- ===================================================== -->

        <section class="card">

            <div class="card-header">

                <h2>
                    <i class="bi bi-geo-alt"></i>
                    Localisation
                </h2>

            </div>

            <div class="form-group">

                <label for="address">
                    Adresse
                </label>

                <input
                    type="text"
                    id="address"
                    name="address"
                    readonly>

            </div>

            <div class="form-group">

                <label for="commune">
                    Commune déléguée
                </label>

                <input
                    type="text"
                    id="commune"
                    name="commune"
                    readonly>

            </div>

            <!-- Coordonnées -->

            <input
                type="hidden"
                id="latitude"
                name="latitude">

            <input
                type="hidden"
                id="longitude"
                name="longitude">

        </section>

        <!-- ===================================================== -->
        <!-- Essence -->
        <!-- ===================================================== -->

        <section class="card">

            <div class="card-header">

                <h2>
                    <i class="bi bi-tree"></i>
                    Essence proposée
                </h2>

            </div>

            <div class="form-group">

                <label for="species">

                    Choisissez une essence

                </label>

                <select
                    id="species"
                    name="species"
                    required>

                    <option value="">
                        -- Sélectionner --
                    </option>

                    <option>Chêne</option>
                    <option>Tilleul</option>
                    <option>Érable</option>
                    <option>Charme</option>
                    <option>Frêne</option>
                    <option>Merisier</option>
                    <option>Pommier</option>
                    <option>Poirier</option>
                    <option>Prunier</option>
                    <option>Autre</option>

                </select>

            </div>

        </section>
        <!-- ===================================================== -->
        <!-- Objectifs -->
        <!-- ===================================================== -->

        <section class="card">

            <div class="card-header">

                <h2>
                    <i class="bi bi-bullseye"></i>
                    Objectifs de la plantation
                </h2>

            </div>

            <div class="goals-grid">

                <label class="goal-tile">
                    <input type="checkbox"
                           name="objectifs[]"
                           value="Ombre">
                    <span>🌞 Ombre</span>
                </label>

                <label class="goal-tile">
                    <input type="checkbox"
                           name="objectifs[]"
                           value="Biodiversité">
                    <span>🐝 Biodiversité</span>
                </label>

                <label class="goal-tile">
                    <input type="checkbox"
                           name="objectifs[]"
                           value="Paysage">
                    <span>🌳 Paysage</span>
                </label>

                <label class="goal-tile">
                    <input type="checkbox"
                           name="objectifs[]"
                           value="Climat">
                    <span>🌍 Climat</span>
                </label>

                <label class="goal-tile">
                    <input type="checkbox"
                           name="objectifs[]"
                           value="Eau">
                    <span>💧 Eau</span>
                </label>

                <label class="goal-tile">
                    <input type="checkbox"
                           name="objectifs[]"
                           value="Fruitier">
                    <span>🍎 Fruitier</span>
                </label>

            </div>

        </section>

        <!-- ===================================================== -->
        <!-- Photo -->
        <!-- ===================================================== -->

        <section class="card">

            <div class="card-header">

                <h2>
                    <i class="bi bi-camera"></i>
                    Photo (facultative)
                </h2>

            </div>

            <div id="photoDropZone"
                 class="photo-dropzone">

                <i class="bi bi-cloud-arrow-up"></i>

                <p>

                    Cliquez ici ou déposez une photo

                </p>

                <small>

                    JPEG ou PNG — image optimisée automatiquement

                </small>

                <input
                    type="file"
                    id="photo"
                    name="photo"
                    accept="image/jpeg,image/png"
                    hidden>

            </div>

            <img id="photoPreview"
                 class="photo-preview"
                 alt="Aperçu de la photo"
                 hidden>

        </section>
                <!-- ===================================================== -->
        <!-- Proposant -->
        <!-- ===================================================== -->

        <section class="card">

            <div class="card-header">

                <h2>
                    <i class="bi bi-person"></i>
                    Vos informations
                </h2>

            </div>

            <div class="form-group">

                <label for="author">

                    Nom (facultatif)

                </label>

                <input
                    type="text"
                    id="author"
                    name="author"
                    maxlength="100"
                    placeholder="Votre nom ou pseudonyme">

            </div>

        </section>

        <!-- ===================================================== -->
        <!-- Commentaire -->
        <!-- ===================================================== -->

        <section class="card">

            <div class="card-header">

                <h2>
                    <i class="bi bi-chat-left-text"></i>
                    Commentaire
                </h2>

            </div>

            <div class="form-group">

                <label for="comment">

                    Précisions (facultatif)

                </label>

                <textarea
                    id="comment"
                    name="comment"
                    rows="5"
                    maxlength="1000"
                    placeholder="Décrivez votre proposition, les contraintes éventuelles, les raisons de votre choix..."></textarea>

            </div>

        </section>

        <!-- ===================================================== -->
        <!-- Messages -->
        <!-- ===================================================== -->

        <div id="message"
             class="message"
             hidden></div>

        <!-- ===================================================== -->
        <!-- Validation -->
        <!-- ===================================================== -->

        <div class="form-actions">

            <button
                id="submitButton"
                type="submit"
                class="btn-primary"
                disabled>

                <i class="bi bi-send-fill"></i>

                Envoyer ma proposition

            </button>

        </div>

    </form>

</main>

<footer class="site-footer">

    <div class="container">

        <div class="footer-left">

            <strong><?= htmlspecialchars(Config::APP_NAME) ?></strong>

            <p>
                Projet de participation citoyenne
                pour la plantation d'arbres.
            </p>

        </div>

        <div class="footer-center">

            <p>

                Version
                <?= htmlspecialchars(Config::VERSION) ?>

            </p>

            <p>
                Licence GNU AGPL v3
            </p>

        </div>

        <div class="footer-right">

            <a href="https://www.sebastienmeunier.info"
               target="_blank"
               rel="noopener">

                Sébastien MEUNIER

            </a>

            <a href="admin/">

                Administration

            </a>

        </div>

    </div>

</footer>

<!-- ===================================================== -->
<!-- Javascript -->
<!-- ===================================================== -->

<script
src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js">
</script>

<script src="public/js/app.js"></script>

<script src="public/js/map.js"></script>

<script src="public/js/address.js"></script>

<script src="public/js/interactions.js"></script>

<script src="public/js/photo.js"></script>

<script src="public/js/fetch.js"></script>

<script>

document.addEventListener(

    "DOMContentLoaded",

    () => {

        App.init();

    }

);

if ("serviceWorker" in navigator) {

    window.addEventListener(

        "load",

        () => {

            navigator.serviceWorker.register(

                "service-worker.js"

            );

        }

    );

}

</script>

</body>

</html>
