<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#0d2115">
    <title><?= htmlspecialchars($application['name'], ENT_QUOTES, 'UTF-8') ?></title>
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
    <link rel="stylesheet" href="public/css/app.css">
</head>
<body class="outdoor-mode">
<header class="site-header"><a class="project-brand" href="/" aria-label="<?= htmlspecialchars($application['name'], ENT_QUOTES, 'UTF-8') ?>"><img class="project-logo" src="<?= htmlspecialchars($application['logo_url'], ENT_QUOTES, 'UTF-8') ?>" alt=""><strong><?= htmlspecialchars($application['name'], ENT_QUOTES, 'UTF-8') ?></strong></a><nav class="site-navigation" aria-label="Navigation principale"><?php if ($user): ?><span class="user-menu"><?= htmlspecialchars($user['first_name'], ENT_QUOTES, 'UTF-8') ?></span><?php if ($user['role'] === 'administrateur'): ?><a class="menu-button" href="/admin/utilisateurs">Utilisateurs</a><?php endif; ?><form class="logout-form" method="post" action="/deconnexion"><input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>"><button class="menu-button">Déconnexion</button></form><?php else: ?><a class="menu-button" href="/connexion">Connexion</a><a class="menu-button" href="/inscription">Inscription</a><?php endif; ?></nav></header>
<main class="page-layout">
    <section class="intro"><h1>Proposer une plantation</h1><p>Objectif : <?= number_format((int) $planting['target_count'], 0, ',', ' ') ?> plantations — <?= (int) $statistics['proposed'] ?> plantations proposées, <?= (int) $statistics['validated'] ?> plantations validées, <?= (int) $statistics['planted'] ?> arbres plantés.</p></section>
    <section class="map-panel" aria-label="Choix de l'emplacement">
        <form id="address-search" class="address-search"><label for="address">Rechercher une adresse</label><div><input id="address" type="search" autocomplete="street-address" placeholder="Rue, lieu-dit, commune"><button type="submit">Rechercher</button></div></form>
        <div id="address-results" class="address-results" aria-live="polite"></div>
        <div id="map" role="application" aria-label="Carte du territoire"></div>
        <div class="map-actions"><button id="locate-me" type="button">Utiliser ma position</button><output id="selected-location">Choisissez un point sur la carte.</output></div>
    </section>
    <section class="form-panel">
        <form id="proposal-form" enctype="multipart/form-data" novalidate>
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
            <input type="hidden" name="latitude" id="latitude"><input type="hidden" name="longitude" id="longitude"><input type="hidden" name="address" id="selected-address">
            <label>Votre nom <small>facultatif</small><input name="author" maxlength="80" autocomplete="name" value="<?= htmlspecialchars($user ? $user['first_name'] . ' ' . $user['last_name'] : '', ENT_QUOTES, 'UTF-8') ?>"></label>
            <label>Votre adresse e-mail <small>facultative — pour recevoir les changements de statut</small><input name="email" type="email" maxlength="<?= (int) $security['max_email_length'] ?>" autocomplete="email" inputmode="email" placeholder="nom@exemple.fr" value="<?= htmlspecialchars($user['email'] ?? '', ENT_QUOTES, 'UTF-8') ?>"></label>
            <label>Essence souhaitée<select name="species" required><option value="">Choisir une essence</option><?php foreach ($planting['allowed_species'] as $species): ?><option value="<?= htmlspecialchars($species, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($species, ENT_QUOTES, 'UTF-8') ?></option><?php endforeach; ?></select></label>
            <fieldset><legend>Objectifs de plantation <small>3 maximum</small></legend><div class="objective-grid"><?php foreach ($planting['objectives'] as $key => $objective): ?><label class="objective"><input type="checkbox" name="objectives[]" value="<?= htmlspecialchars($key, ENT_QUOTES, 'UTF-8') ?>"><span class="objective-tile"><span class="objective-icon" aria-hidden="true"><?= htmlspecialchars($objective['icon'], ENT_QUOTES, 'UTF-8') ?></span><span><?= htmlspecialchars($objective['label'], ENT_QUOTES, 'UTF-8') ?></span></span></label><?php endforeach; ?></div></fieldset>
            <label>Commentaire <small>facultatif</small><textarea name="comment" maxlength="1000" rows="4" placeholder="Précisez votre idée de plantation."></textarea></label>
            <label>Photos <small>jusqu'à <?= (int) $security['max_photos_per_proposal'] ?></small><input id="photos" name="photos[]" type="file" accept="image/jpeg,image/png,image/webp" multiple></label>
            <div id="photo-previews" class="photo-previews" aria-live="polite"></div>
            <p id="form-message" class="form-message" aria-live="polite"></p>
            <button class="submit-button" type="submit">Envoyer ma proposition</button>
        </form>
    </section>
</main>
<?php require __DIR__ . '/partials/footer.php'; ?>
<div id="toast" class="toast" role="status" aria-live="polite" hidden></div>
<script>window.PlanArbreConfig = <?= json_encode(['center' => $territory['center'], 'zoom' => $map['default_zoom'], 'territoryUrl' => $dataSources['territory']['url'], 'municipalitiesUrl' => $dataSources['delegated_municipalities']['url'], 'proposalsUrl' => $dataSources['proposals']['url'], 'proposalUrl' => '/api/propositions', 'maxPhotos' => $security['max_photos_per_proposal'], 'maxObjectives' => $planting['max_objectives_per_proposal'], 'objectives' => $planting['objectives']], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;</script>
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script src="https://cdn.jsdelivr.net/npm/@turf/turf@7/turf.min.js"></script>
<script src="public/js/app.js" defer></script>
</body>
</html>
