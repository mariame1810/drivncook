<?php
session_start();
require_once '../config/db.php';
require_once '../php/middleware_onboarding.php';

if (!isset($_SESSION['id_franchise'])) {
    header("Location: login.html");
    exit;
}

$id_franchise = $_SESSION['id_franchise'];

// Commandes du franchisé
$cmdStmt = $pdo->prepare("SELECT id_commande, date_commande FROM commandes WHERE id_franchise = ? ORDER BY date_commande DESC");
$cmdStmt->execute([$id_franchise]);
$commandes = $cmdStmt->fetchAll();

// Produits disponibles
$produits = $pdo->query("SELECT id_produit, nom FROM produits ORDER BY nom ASC")->fetchAll();

// Helpers
function e($v){ return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
function formatDateFr($dateStr){ $ts = strtotime($dateStr); return $ts ? date('d/m/Y H:i', $ts) : e($dateStr); }
?>

<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Ajouter un produit – Driv’n Cook</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
  <style>
    :root{ --brand:#E11D48; }
    body{ font-family: Inter, system-ui, -apple-system, Segoe UI, Roboto, Arial, "Helvetica Neue", sans-serif; background: linear-gradient(180deg,#fafafa,#f5f7fb); }
    .app-shell{ min-height:100vh; }
    .sidebar{ width:280px; background:white; border-right:1px solid #eef0f4; position:sticky; top:0; height:100vh; }
    .brand{ font-weight:700; }
    .nav-link{ color:#334155; border-radius:12px; padding:.6rem .85rem; }
    .nav-link:hover{ background:#f3f4f6; color:#0f172a; }
    .nav-link.active{ background: rgba(225,29,72,.12); color: var(--brand); font-weight:600; }
    .logout{ color:#ef4444 !important; }

    .page{ padding:32px; }
    .hero{ background: radial-gradient(1200px 400px at 10% -10%, rgba(225,29,72,.10), transparent), white; border:1px solid #eef0f4; border-radius:20px; padding:24px; }
    .card-elev{ border:1px solid #eef0f4; border-radius:16px; box-shadow:0 1px 2px rgba(0,0,0,.03), 0 12px 24px -12px rgba(15,23,42,.12); }
    .input-icon{ position:relative; }
    .input-icon .bi{ position:absolute; left:12px; top:50%; transform:translateY(-50%); pointer-events:none; }
    .input-icon .form-select, .input-icon .form-control{ padding-left:2.25rem; }
    .required:after{ content:" *"; color:#ef4444; }
  </style>
</head>
<body>
<div class="d-flex app-shell">
  <!-- Sidebar -->
  <aside class="sidebar p-4 d-flex flex-column">
    <div class="d-flex align-items-center gap-2 mb-4">
      <div class="rounded-4 p-2 pe-3 bg-light border"><span class="fs-4">🚚</span></div>
      <div>
        <div class="brand h4 m-0 text-danger">Driv’n Cook</div>
        <small class="text-muted">Portail franchise</small>
      </div>
    </div>
    <nav class="nav flex-column gap-2">
      <a class="nav-link" href="dashboard.php"><i class="bi bi-house-door me-2"></i>Dashboard</a>
      <a class="nav-link" href="mes_commandes.php"><i class="bi bi-box-seam me-2"></i>Mes commandes</a>
      <a class="nav-link active" href="ajout_produit_commande.php"><i class="bi bi-plus-circle me-2"></i>Ajouter un produit</a>
      <a class="nav-link" href="profil.php"><i class="bi bi-person-circle me-2"></i>Modifier mon profil</a>
      <div class="mt-auto"></div>
      <a class="nav-link logout" href="logout.php"><i class="bi bi-box-arrow-right me-2"></i>Se déconnecter</a>
    </nav>
  </aside>

  <!-- Main content -->
  <main class="page flex-grow-1">

    <div class="d-flex align-items-center justify-content-between gap-2 mb-4 hero">
      <div>
        <h3 class="mb-1">➕ Ajouter un produit à une commande</h3>
        <div class="text-muted">Sélectionnez la commande, choisissez le produit et indiquez la quantité.</div>
      </div>
      <div class="d-flex gap-2">
        <a href="mes_commandes.php" class="btn btn-outline-secondary"><i class="bi bi-box-seam"></i> Voir mes commandes</a>
      </div>
    </div>

    <div class="card-elev p-3 p-md-4">
      <form action="../php/ajout_ligne_commande.php" method="POST" class="row g-3 needs-validation" novalidate>

        <div class="col-12 col-md-6">
          <label for="id_commande" class="form-label required">Commande</label>
          <div class="input-icon">
            <i class="bi bi-receipt-cutoff"></i>
            <select id="id_commande" name="id_commande" class="form-select" required>
              <option value="">— Sélectionner une commande —</option>
              <?php foreach ($commandes as $c): ?>
                <option value="<?= (int)$c['id_commande'] ?>">#<?= (int)$c['id_commande'] ?> – <?= e(formatDateFr($c['date_commande'])) ?></option>
              <?php endforeach; ?>
            </select>
            <div class="invalid-feedback">Veuillez choisir une commande.</div>
          </div>
        </div>

        <div class="col-12 col-md-6">
          <label for="id_produit" class="form-label required">Produit</label>
          <div class="input-icon">
            <i class="bi bi-bag"></i>
            <select id="id_produit" name="id_produit" class="form-select" required>
              <option value="">— Sélectionner un produit —</option>
              <?php foreach ($produits as $p): ?>
                <option value="<?= (int)$p['id_produit'] ?>"><?= e($p['nom']) ?></option>
              <?php endforeach; ?>
            </select>
            <div class="invalid-feedback">Veuillez sélectionner un produit.</div>
          </div>
        </div>

        <div class="col-12 col-md-6">
          <label for="quantite" class="form-label required">Quantité</label>
          <div class="input-icon">
            <i class="bi bi-123"></i>
            <input id="quantite" type="number" name="quantite" class="form-control" min="1" step="1" value="1" required>
            <div class="invalid-feedback">La quantité doit être au moins 1.</div>
          </div>
        </div>

        <div class="col-12 d-flex justify-content-end gap-2">
          <a href="dashboard.php" class="btn btn-outline-secondary"><i class="bi bi-arrow-left"></i> Annuler</a>
          <button type="submit" class="btn btn-dark">
            <i class="bi bi-plus-lg"></i> Ajouter à la commande
          </button>
        </div>
      </form>
    </div>

    <!-- Astuce -->
    <div class="mt-4 small text-muted">Astuce : tapez la première lettre dans les listes pour aller plus vite.</div>

  </main>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
  // Validation Bootstrap
  (() => {
    const forms = document.querySelectorAll('.needs-validation');
    Array.from(forms).forEach(form => {
      form.addEventListener('submit', event => {
        if (!form.checkValidity()) {
          event.preventDefault();
          event.stopPropagation();
        }
        form.classList.add('was-validated');
      }, false);
    });
  })();

  // Simple recherche dans le select Produit (Ctrl+K pour focus)
  const produitSelect = document.getElementById('id_produit');
  window.addEventListener('keydown', (e) => {
    if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === 'k') {
      e.preventDefault();
      produitSelect.focus();
    }
  });
</script>
</body>
</html>
