<?php
session_start();
require_once '../config/db.php';

if (!isset($_SESSION['id_admin'])) {
    header("Location: admin_login.php");
    exit;
}

$nom = $_SESSION['nom_admin'] ?? 'Admin';

// Récupérer les franchisés sans camion attribué
$franchises = $pdo->query("
    SELECT f.id_franchise, f.nom, f.prenom
    FROM franchises f
    LEFT JOIN camions c ON f.id_franchise = c.id_franchise
    WHERE c.id_franchise IS NULL
")->fetchAll(PDO::FETCH_ASSOC);

// Récupérer les camions non attribués
$camions = $pdo->query("
    SELECT id_camion, immatriculation
    FROM camions
    WHERE id_franchise IS NULL
")->fetchAll(PDO::FETCH_ASSOC);

$hasFranchises = count($franchises) > 0;
$hasCamions = count($camions) > 0;
?>
<!DOCTYPE html>
<html lang="fr" data-bs-theme="light">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Attribuer un camion</title>

  <!-- Bootstrap & Icons -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">

  <style>
    :root{
      --brand:#4f46e5;/* indigo-600 */
      --card-radius:1rem;
      --shadow-soft:0 10px 25px rgba(0,0,0,.08);
      --shadow-hover:0 16px 40px rgba(0,0,0,.12);
    }
    [data-bs-theme="dark"]{
      --shadow-soft:0 10px 25px rgba(0,0,0,.35);
      --shadow-hover:0 16px 40px rgba(0,0,0,.45);
    }
    .navbar-blur{
      backdrop-filter:saturate(180%) blur(10px);
      background: color-mix(in oklab, var(--bs-body-bg) 70%, transparent);
      border-bottom:1px solid var(--bs-border-color);
    }
    .hero{
      background: linear-gradient(135deg, var(--brand) 0%, #7c3aed 50%, #0ea5e9 100%);
      color:#fff;
      border-bottom-left-radius:2rem;
      border-bottom-right-radius:2rem;
    }
    .card-zoom{
      border:0;border-radius:var(--card-radius);
      box-shadow:var(--shadow-soft);
      transition:transform .2s ease, box-shadow .2s ease;
    }
    .card-zoom:hover{ transform:translateY(-4px); box-shadow:var(--shadow-hover); }
    .btn-pill{ border-radius:999px; }
    .form-card .form-label{ font-weight:600; }
    .help-text{ font-size:.9rem; color:var(--bs-secondary-color); }
    .kpi{
      display:flex;align-items:center;gap:.75rem;
      padding:.75rem 1rem;border-radius:.75rem;
      background:var(--bs-body-bg); border:1px solid var(--bs-border-color);
    }
  </style>
</head>
<body class="bg-body-tertiary">

  <!-- Top nav -->
  <nav class="navbar navbar-expand-lg navbar-blur sticky-top">
    <div class="container">
      <a class="navbar-brand fw-semibold d-flex align-items-center gap-2" href="admin_dashboard.php">
        <i class="bi bi-truck-front text-primary"></i> Flotte – Attribution
      </a>

      <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#topnav" aria-controls="topnav" aria-expanded="false" aria-label="Basculer la navigation">
        <span class="navbar-toggler-icon"></span>
      </button>

      <div class="collapse navbar-collapse" id="topnav">
        <ul class="navbar-nav me-auto mb-2 mb-lg-0">
          <li class="nav-item"><a class="nav-link" href="admin_dashboard.php">Tableau de bord</a></li>
          <li class="nav-item"><a class="nav-link" href="admin_gestion_camions.php">Camions</a></li>
          <li class="nav-item"><a class="nav-link" href="admin_franchises.php">Franchisés</a></li>
          <li class="nav-item"><a class="nav-link" href="admin_entrepots.php">Entrepôts</a></li>
        </ul>

        <div class="d-flex align-items-center gap-2">
          <button id="themeToggle" class="btn btn-outline-secondary btn-sm btn-pill" type="button" aria-label="Basculer le thème">
            <i class="bi bi-moon-stars"></i>
          </button>
          <div class="dropdown">
            <button class="btn btn-primary btn-pill dropdown-toggle" data-bs-toggle="dropdown">
              <i class="bi bi-person-circle me-1"></i> <?= htmlspecialchars($nom, ENT_QUOTES, 'UTF-8') ?>
            </button>
            <ul class="dropdown-menu dropdown-menu-end">
              <li><a class="dropdown-item" href="admin_profile.php"><i class="bi bi-gear me-2"></i>Profil</a></li>
              <li><hr class="dropdown-divider"></li>
              <li><a class="dropdown-item text-danger" href="admin_logout.php"><i class="bi bi-box-arrow-right me-2"></i>Se déconnecter</a></li>
            </ul>
          </div>
        </div>
      </div>
    </div>
  </nav>

  <!-- Hero -->
  <header class="hero py-5">
    <div class="container">
      <nav aria-label="breadcrumb" class="mb-2">
        <ol class="breadcrumb">
          <li class="breadcrumb-item"><a class="link-light text-decoration-underline" href="admin_dashboard.php">Accueil</a></li>
          <li class="breadcrumb-item text-white-50">Flotte</li>
          <li class="breadcrumb-item active text-white" aria-current="page">Attribuer un camion</li>
        </ol>
      </nav>
      <h1 class="display-6 mb-2">🚚 Attribuer un camion à un franchisé</h1>
      <p class="lead mb-0">Sélectionnez un franchisé éligible et un camion disponible, puis validez.</p>
    </div>
  </header>

  <main class="container py-5">

    <!-- Small KPIs -->
    <div class="row g-3 mb-4">
      <div class="col-12 col-sm-6 col-lg-4">
        <div class="kpi">
          <i class="bi bi-person-check fs-5 text-success"></i>
          <div><div class="fw-semibold"><?= (int)count($franchises) ?></div><div class="text-secondary small">Franchisés sans camion</div></div>
        </div>
      </div>
      <div class="col-12 col-sm-6 col-lg-4">
        <div class="kpi">
          <i class="bi bi-truck fs-5 text-primary"></i>
          <div><div class="fw-semibold"><?= (int)count($camions) ?></div><div class="text-secondary small">Camions disponibles</div></div>
        </div>
      </div>
    </div>

    <!-- Form card -->
    <div class="card card-zoom form-card">
      <div class="card-body p-4 p-lg-5">
        <h5 class="card-title d-flex align-items-center gap-2 mb-4">
          <span class="badge rounded-pill text-bg-primary"><i class="bi bi-link-45deg"></i></span>
          Associer un franchisé et un camion
        </h5>

        <?php if (isset($_GET['success'])): ?>
          <div class="alert alert-success d-flex align-items-center" role="alert">
            <i class="bi bi-check-circle me-2"></i>
            <div>Camion attribué avec succès.</div>
          </div>
        <?php elseif (isset($_GET['error'])): ?>
          <div class="alert alert-danger d-flex align-items-center" role="alert">
            <i class="bi bi-exclamation-triangle me-2"></i>
            <div>Erreur lors de l’attribution.</div>
          </div>
        <?php endif; ?>

        <form action="../php/attribuer_camion_action.php" method="POST" class="row g-4 needs-validation" novalidate>
          <div class="col-12 col-md-6">
            <label for="franchise" class="form-label">Franchisé</label>
            <select id="franchise" name="id_franchise" class="form-select" required <?= !$hasFranchises ? 'disabled' : '' ?>>
              <option value=""><?= $hasFranchises ? '— Choisir un franchisé —' : 'Aucun franchisé éligible' ?></option>
              <?php foreach ($franchises as $f): ?>
                <option value="<?= (int)$f['id_franchise'] ?>">
                  <?= htmlspecialchars(strtoupper($f['nom']) . ' ' . ucfirst($f['prenom']), ENT_QUOTES, 'UTF-8') ?>
                </option>
              <?php endforeach; ?>
            </select>
            <div class="invalid-feedback">Veuillez sélectionner un franchisé.</div>
            <div class="help-text">Seuls les franchisés sans camion apparaissent ici.</div>
          </div>

          <div class="col-12 col-md-6">
            <label for="camion" class="form-label">Camion disponible</label>
            <select id="camion" name="id_camion" class="form-select" required <?= !$hasCamions ? 'disabled' : '' ?>>
              <option value=""><?= $hasCamions ? '— Choisir un camion —' : 'Aucun camion disponible' ?></option>
              <?php foreach ($camions as $c): ?>
                <option value="<?= (int)$c['id_camion'] ?>">
                  <?= htmlspecialchars($c['immatriculation'], ENT_QUOTES, 'UTF-8') ?>
                </option>
              <?php endforeach; ?>
            </select>
            <div class="invalid-feedback">Veuillez sélectionner un camion.</div>
            <div class="help-text">Liste des véhicules non attribués.</div>
          </div>

          <div class="col-12 d-flex flex-wrap gap-2 justify-content-end">
            <a href="admin_dashboard.php" class="btn btn-outline-secondary btn-pill">
              <i class="bi bi-arrow-left"></i> Retour
            </a>
            <button type="submit" class="btn btn-success btn-pill"
              <?= (!$hasFranchises || !$hasCamions) ? 'disabled' : '' ?>>
              <i class="bi bi-check2-circle me-1"></i> Attribuer
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- Tips -->
    <div class="mt-4 text-secondary small">
      <i class="bi bi-lightbulb"></i> Astuce : mettez à jour la flotte ou les profils si la liste est vide.
      <a class="text-decoration-none" href="admin_gestion_camions.php">Gérer les camions</a> •
      <a class="text-decoration-none" href="admin_franchises.php">Voir les franchisés</a>
    </div>
  </main>

  <footer class="py-4 bg-body">
    <div class="container d-flex flex-column flex-md-row align-items-center justify-content-between gap-2">
      <span class="text-secondary small">© <?= date('Y') ?> Votre Société. Tous droits réservés.</span>
      <div class="d-flex align-items-center gap-3 small">
        <a href="#" class="link-secondary text-decoration-none">Aide</a>
        <a href="#" class="link-secondary text-decoration-none">Confidentialité</a>
        <a href="#" class="link-secondary text-decoration-none">Conditions</a>
      </div>
    </div>
  </footer>

  <!-- Toasts (for nicer feedback on redirect with ?success=1 or ?error=1) -->
  <div class="position-fixed bottom-0 end-0 p-3" style="z-index: 1080">
    <?php if (isset($_GET['success'])): ?>
      <div class="toast align-items-center text-bg-success border-0 show" role="alert">
        <div class="d-flex">
          <div class="toast-body"><i class="bi bi-check-circle me-2"></i>Camion attribué avec succès.</div>
          <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
        </div>
      </div>
    <?php elseif (isset($_GET['error'])): ?>
      <div class="toast align-items-center text-bg-danger border-0 show" role="alert">
        <div class="d-flex">
          <div class="toast-body"><i class="bi bi-exclamation-triangle me-2"></i>Erreur lors de l’attribution.</div>
          <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
        </div>
      </div>
    <?php endif; ?>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <script>
    // Light/Dark toggle with persistence
    const html = document.documentElement;
    const key = 'pref-theme';
    const btn = document.getElementById('themeToggle');
    const saved = localStorage.getItem(key);
    if (saved) html.setAttribute('data-bs-theme', saved);

    function setIcon() {
      btn.innerHTML = (html.getAttribute('data-bs-theme') === 'dark')
        ? '<i class="bi bi-sun"></i>'
        : '<i class="bi bi-moon-stars"></i>';
    }
    function toggleTheme(){
      const next = html.getAttribute('data-bs-theme') === 'dark' ? 'light' : 'dark';
      html.setAttribute('data-bs-theme', next);
      localStorage.setItem(key, next);
      setIcon();
    }
    btn.addEventListener('click', toggleTheme);
    setIcon();

    // Bootstrap validation
    (() => {
      const forms = document.querySelectorAll('.needs-validation');
      Array.from(forms).forEach(form => {
        form.addEventListener('submit', event => {
          if (!form.checkValidity()) {
            event.preventDefault(); event.stopPropagation();
          }
          form.classList.add('was-validated');
        }, false);
      });
    })();
  </script>
</body>
</html>
