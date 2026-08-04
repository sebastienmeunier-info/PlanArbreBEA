<!doctype html>
<html lang="fr">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <link rel="stylesheet" href="<?= htmlspecialchars($url('/public/css/app.css'), ENT_QUOTES, 'UTF-8') ?>">
  <title>À propos de l’application · <?= htmlspecialchars($application['name'], ENT_QUOTES, 'UTF-8') ?></title>
</head>
<body class="outdoor-mode">
<?php require __DIR__ . '/partials/header.php'; ?>
<main class="auth-page about-page">
  <h1>À propos de l’application</h1>
  <div class="about-columns">
    <section class="about-card">
      <h2>Un outil pour agir ensemble</h2>
      <p><?= htmlspecialchars($application['name'], ENT_QUOTES, 'UTF-8') ?> facilite la participation citoyenne aux plantations et aux dons d’arbres. Chacun peut localiser une idée, expliquer son objectif et suivre son évolution ; la collectivité peut ensuite l’instruire et la valoriser sur la carte.</p>
      <h2>Adaptable à chaque territoire</h2>
      <p>L’application ne dépend pas d’un territoire particulier. Le nom du projet, le logo, les limites géographiques, les communes associées, les essences et les objectifs de plantation sont regroupés dans la configuration. Une commune, une intercommunalité ou une association peut ainsi l’adapter à son propre contexte.</p>
      <h2>Simple à déployer</h2>
      <p>Plantons fonctionne avec PHP et des fichiers JSON/GeoJSON : aucune base de données SQL ni installation complexe ne sont nécessaires. Les fichiers peuvent être transférés par FTP sur un hébergement compatible PHP, puis configurés depuis un unique fichier <code>config/config.php</code>.</p>
      <h2>Une collaboration entre l’humain et l’intelligence artificielle</h2>
      <p>Plantons + est un projet imaginé par un humain, à partir de besoins concrets de terrain, puis développé avec l’appui d’une intelligence artificielle. Cette collaboration associe une vision locale et citoyenne — planter davantage d’arbres, mobiliser les habitants et suivre les projets — à la capacité de l’IA à accélérer la conception, l’écriture du code et l’amélioration continue de l’application.</p>
    </section>
    <div class="about-project-column">
      <h2>À propos du projet de plantation</h2>
      <section class="about-card about-project-card">
        <?= is_string($about['planting_project_html'] ?? null) ? $about['planting_project_html'] : '' ?>
      </section>
    </div>
  </div>
</main>
<?php require __DIR__ . '/partials/footer.php'; ?>
</body>
</html>
