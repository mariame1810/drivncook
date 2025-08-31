<?php
session_start();
require_once '../config/db.php';

if (!isset($_SESSION['id_admin'])) {
    header("Location: admin_login.php");
    exit;
}

$nom = $_SESSION['nom_admin'] ?? 'Admin';

// Franchises + info camion
$sql = "SELECT f.*, c.immatriculation
        FROM franchises f
        LEFT JOIN camions c ON f.id_franchise = c.id_franchise
        ORDER BY f.nom ASC";
$franchises = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);

// Petites stats
$total = count($franchises);
$actifs = 0; $bloques = 0; $avecCamion = 0; $sansCamion = 0;
foreach ($franchises as $f) {
  if (!empty($f['actif'])) $actifs++; else $bloques++;
  if (!empty($f['immatriculation'])) $avecCamion++; else $sansCamion++;
}
?>
<!DOCTYPE html>
<html lang="fr" data-bs-theme="light">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Franchisés – Admin</title>

  <!-- Bootstrap & Icons -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">

  <style>
    :root{
      --brand:#4f46e5;
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
    .card-zoom{ border:0;border-radius:var(--card-radius); box-shadow:var(--shadow-soft); transition:transform .2s, box-shadow .2s; }
    .card-zoom:hover{ transform:translateY(-4px); box-shadow:var(--shadow-hover); }
    .btn-pill{ border-radius:999px; }
    .kpi{
      display:flex;align-items:center;gap:.75rem;
      padding:.75rem 1rem;border-radius:.75rem;
      background:var(--bs-body-bg); border:1px solid var(--bs-border-color);
    }
    .table thead th{ position:sticky; top:0; background:var(--bs-body-bg); z-index:1; }
    .search-input{ max-width:380px; }
    .badge-chip{ border-radius:999px; padding:.35rem .6rem; }
    .empty{ border:1px dashed var(--bs-border-color); border-radius:1rem; padding:2rem; text-align:center; color:var(--bs-secondary-color); }
  </style>
</head>
<body class="bg-body-tertiary">

  <!-- Top nav -->
  <nav class="navbar navbar-expand-lg navbar-blur sticky-top">
    <div class="container">
      <a class="navbar-brand fw-semibold d-flex align-items-center gap-2" href="admin_dashboard.php">
        <i class="bi bi-people text-primary"></i> Franchisés
      </a>

      <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#topnav" aria-controls="topnav" aria-expanded="false" aria-label="Basculer la navigation">
        <span class="navbar-toggler-icon"></span>
      </button>

      <div class="collapse navbar-collapse" id="topnav">
        <ul class="navbar-nav me-auto mb-2 mb-lg-0">
          <li class="nav-item"><a class="nav-link" href="admin_dashboard.php">Tableau de bord</a></li>
          <li class="nav-item"><a class="nav-link" href="admin_gestion_camions.php">Camions</a></li>
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
          <li class="breadcrumb-item active text-white">Franchisés</li>
        </ol>
      </nav>
      <h1 class="display-6 mb-2">👥 Liste des franchisés</h1>
      <p class="lead mb-0">Gérez l’activité, le statut et l’affectation des camions.</p>
    </div>
  </header>

  <main class="container py-5">

    <!-- KPIs -->
    <div class="row g-3 mb-4">
      <div class="col-12 col-sm-6 col-lg-3">
        <div class="kpi"><i class="bi bi-people fs-5 text-primary"></i>
          <div><div class="fw-semibold"><?= (int)$total ?></div><div class="text-secondary small">Total franchisés</div></div>
        </div>
      </div>
      <div class="col-12 col-sm-6 col-lg-3">
        <div class="kpi"><i class="bi bi-check-circle fs-5 text-success"></i>
          <div><div class="fw-semibold"><?= (int)$actifs ?></div><div class="text-secondary small">Actifs</div></div>
        </div>
      </div>
      <div class="col-12 col-sm-6 col-lg-3">
        <div class="kpi"><i class="bi bi-slash-circle fs-5 text-danger"></i>
          <div><div class="fw-semibold"><?= (int)$bloques ?></div><div class="text-secondary small">Bloqués</div></div>
        </div>
      </div>
      <div class="col-12 col-sm-6 col-lg-3">
        <div class="kpi"><i class="bi bi-truck fs-5 text-warning"></i>
          <div><div class="fw-semibold"><?= (int)$avecCamion ?></div><div class="text-secondary small">Avec camion</div></div>
        </div>
      </div>
    </div>

    <!-- Table -->
    <div class="card card-zoom">
      <div class="card-body p-4 p-lg-5">
        <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 mb-3">
          <h5 class="card-title m-0 d-flex align-items-center gap-2">
            <span class="badge rounded-pill text-bg-secondary"><i class="bi bi-list-task"></i></span>
            Franchisés
          </h5>
          <div class="d-flex gap-2 w-100 w-md-auto">
            <div class="input-group search-input">
              <span class="input-group-text"><i class="bi bi-search"></i></span>
              <input type="search" id="tableSearch" class="form-control" placeholder="Rechercher nom, email ou immatriculation…">
            </div>
            <select id="statusFilter" class="form-select" style="max-width:180px">
              <option value="">Tous statuts</option>
              <option value="actif">Actif</option>
              <option value="bloque">Bloqué</option>
            </select>
          </div>
        </div>

        <?php if ($franchises): ?>
          <div class="table-responsive">
            <table class="table align-middle table-hover">
              <thead>
                <tr>
                  <th class="text-nowrap">ID</th>
                  <th>Nom</th>
                  <th>Email</th>
                  <th>Camion</th>
                  <th>Statut</th>
                  <th class="text-end">Actions</th>
                </tr>
              </thead>
              <tbody id="frBody">
                <?php foreach ($franchises as $f):
                  $id = (int)$f['id_franchise'];
                  $nomComplet = strtoupper($f['nom'] ?? '') . ' ' . ucfirst($f['prenom'] ?? '');
                  $nomComplet = trim($nomComplet);
                  $email = htmlspecialchars($f['email'] ?? '', ENT_QUOTES, 'UTF-8');
                  $immat = htmlspecialchars($f['immatriculation'] ?? '', ENT_QUOTES, 'UTF-8');
                  $actif = !empty($f['actif']);
                  $searchHay = strtolower($nomComplet . ' ' . $email . ' ' . ($immat ?: ''));
                ?>
                  <tr data-search="<?= $searchHay ?>" data-status="<?= $actif ? 'actif' : 'bloque' ?>">
                    <td class="text-muted"><?= $id ?></td>
                    <td class="fw-semibold"><?= htmlspecialchars($nomComplet, ENT_QUOTES, 'UTF-8') ?></td>
                    <td><a href="mailto:<?= $email ?>" class="text-decoration-none"><?= $email ?></a></td>
                    <td>
                      <?php if ($immat): ?>
                        <span class="badge text-bg-primary-subtle text-primary badge-chip"><i class="bi bi-truck-front me-1"></i><?= $immat ?></span>
                      <?php else: ?>
                        <span class="text-secondary"><em>—</em></span>
                      <?php endif; ?>
                    </td>
                    <td>
                      <?php if ($actif): ?>
                        <span class="badge text-bg-success badge-chip">Actif</span>
                      <?php else: ?>
                        <span class="badge text-bg-danger badge-chip">Bloqué</span>
                      <?php endif; ?>
                    </td>
                    <td class="text-end">
                      <div class="btn-group">
                        <a href="admin_fiche_franchise.php?id=<?= $id ?>" class="btn btn-sm btn-outline-primary btn-pill">
                          <i class="bi bi-eye"></i> Détails
                        </a>
                        <button class="btn btn-sm <?= $actif ? 'btn-warning' : 'btn-success' ?> btn-pill dropdown-toggle dropdown-toggle-split" data-bs-toggle="dropdown" aria-expanded="false">
                          <span class="visually-hidden">Actions</span>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end">
                          <li>
                            <button class="dropdown-item d-flex align-items-center gap-2" data-bs-toggle="modal" data-bs-target="#confirmStatut"
                              data-id="<?= $id ?>" data-next="<?= $actif ? 0 : 1 ?>" data-name="<?= htmlspecialchars($nomComplet, ENT_QUOTES, 'UTF-8') ?>">
                              <i class="bi <?= $actif ? 'bi-slash-circle' : 'bi-check-circle' ?>"></i>
                              <?= $actif ? 'Bloquer' : 'Activer' ?>
                            </button>
                          </li>
                        </ul>
                      </div>
                      <!-- Hidden form template per row (populated by modal) -->
                      <form id="form-<?= $id ?>" action="../php/changer_statut_franchise.php" method="POST" class="d-none">
                        <input type="hidden" name="id_franchise" value="<?= $id ?>">
                        <input type="hidden" name="nouvel_statut" value="">
                      </form>
                    </td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        <?php else: ?>
          <div class="empty">
            <div class="fs-4 mb-2">Aucun franchisé trouvé</div>
            <p class="mb-0">Les franchisés apparaîtront ici une fois enregistrés.</p>
          </div>
        <?php endif; ?>
      </div>
    </div>

    <div class="d-flex justify-content-end mt-4">
      <a href="admin_dashboard.php" class="btn btn-outline-secondary btn-pill"><i class="bi bi-arrow-left"></i> Retour au tableau de bord</a>
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

  <!-- Toast success -->
  <div class="position-fixed bottom-0 end-0 p-3" style="z-index: 1080">
    <?php if (isset($_GET['success'])): ?>
      <div class="toast align-items-center text-bg-success border-0 show" role="alert">
        <div class="d-flex">
          <div class="toast-body"><i class="bi bi-check-circle me-2"></i>Opération réussie.</div>
          <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
        </div>
      </div>
    <?php endif; ?>
  </div>

  <!-- Modal confirmation statut -->
  <div class="modal fade" id="confirmStatut" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title"><i class="bi bi-exclamation-triangle text-warning me-2"></i>Confirmer le changement de statut</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
        </div>
        <div class="modal-body">
          <p id="confirmText" class="mb-0">Confirmez-vous cette action&nbsp;?</p>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary btn-pill" data-bs-dismiss="modal">Annuler</button>
          <button id="confirmBtn" type="button" class="btn btn-warning btn-pill">Continuer</button>
        </div>
      </div>
    </div>
  </div>

  <!-- Scripts -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <script>
    // Light/Dark toggle with persistence
    const html = document.documentElement;
    const key = 'pref-theme';
    const btn = document.getElementById('themeToggle');
    const saved = localStorage.getItem(key);
    if (saved) html.setAttribute('data-bs-theme', saved);
    const setIcon = () => btn.innerHTML = (html.getAttribute('data-bs-theme') === 'dark') ? '<i class="bi bi-sun"></i>' : '<i class="bi bi-moon-stars"></i>';
    const toggleTheme = () => { const next = html.getAttribute('data-bs-theme') === 'dark' ? 'light' : 'dark'; html.setAttribute('data-bs-theme', next); localStorage.setItem(key, next); setIcon(); };
    btn.addEventListener('click', toggleTheme); setIcon();

    // Client-side search + filter
    const q = document.getElementById('tableSearch');
    const filter = document.getElementById('statusFilter');
    const rows = Array.from(document.querySelectorAll('#frBody tr'));
    function applyFilters(){
      const s = (q?.value || '').toLowerCase().trim();
      const f = (filter?.value || '').toLowerCase();
      rows.forEach(r => {
        const hay = (r.getAttribute('data-search') || '');
        const st  = (r.getAttribute('data-status') || '');
        const okText = hay.includes(s);
        const okStatus = !f || st === f;
        r.style.display = (okText && okStatus) ? '' : 'none';
      });
    }
    q?.addEventListener('input', applyFilters);
    filter?.addEventListener('change', applyFilters);

    // Modal: confirm block/activate then submit hidden form
    const modal = document.getElementById('confirmStatut');
    const confirmText = document.getElementById('confirmText');
    const confirmBtn = document.getElementById('confirmBtn');
    let pending = { id: null, next: null };

    modal?.addEventListener('show.bs.modal', (event) => {
      const button = event.relatedTarget;
      const id = button?.getAttribute('data-id');
      const next = button?.getAttribute('data-next'); // 0 or 1
      const name = button?.getAttribute('data-name') || 'ce franchisé';
      pending = { id, next };
      confirmText.textContent = (next === '0')
        ? `Bloquer ${name} ?`
        : `Activer ${name} ?`;
      confirmBtn.className = 'btn btn-pill ' + (next === '0' ? 'btn-warning' : 'btn-success');
      confirmBtn.textContent = (next === '0' ? 'Bloquer' : 'Activer');
    });

    confirmBtn?.addEventListener('click', () => {
      if (!pending.id) return;
      const form = document.getElementById(`form-${pending.id}`);
      if (!form) return;
      form.querySelector('input[name="nouvel_statut"]').value = pending.next;
      form.submit();
    });
  </script>
</body>
</html>
