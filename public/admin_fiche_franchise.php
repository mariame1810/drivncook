<?php
session_start();
require_once '../config/db.php';

if (!isset($_SESSION['id_admin'])) {
    header("Location: admin_login.php");
    exit;
}

$nomAdmin = $_SESSION['nom_admin'] ?? 'Admin';

$id_franchise = $_GET['id'] ?? null;
if (!$id_franchise) {
    header("Location: admin_franchises.php");
    exit;
}

$sql = "SELECT f.*, c.immatriculation
        FROM franchises f
        LEFT JOIN camions c ON f.id_franchise = c.id_franchise
        WHERE f.id_franchise = ?";
$stmt = $pdo->prepare($sql);
$stmt->execute([$id_franchise]);
$franchise = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$franchise) { die("Franchisé introuvable."); }

$stmt = $pdo->prepare("SELECT montant FROM ventes WHERE id_franchise = ?");
$stmt->execute([$id_franchise]);
$ventes = $stmt->fetchAll(PDO::FETCH_ASSOC);
$totalCA = array_sum(array_column($ventes, 'montant'));

$sql = "SELECT * FROM commandes WHERE id_franchise = ? ORDER BY date_commande DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute([$id_franchise]);
$commandes = $stmt->fetchAll(PDO::FETCH_ASSOC);

$nomComplet = trim(strtoupper($franchise['nom'] ?? '') . ' ' . ucfirst($franchise['prenom'] ?? ''));
$actif = !empty($franchise['actif']);
$immat = $franchise['immatriculation'] ?? null;
$nbCommandes = count($commandes);
$derniereCommande = $nbCommandes ? (new DateTime($commandes[0]['date_commande']))->format('d/m/Y H:i') : null;

function badgeStatutCommande(string $statutRaw): string {
  $s = mb_strtolower(trim($statutRaw));
  // map simple status → contextual badge
  if (in_array($s, ['livrée','livree','terminée','terminee','validée','validee'])) return 'success';
  if (in_array($s, ['en cours','processing','préparation','preparation'])) return 'warning';
  if (in_array($s, ['annulée','annulee','refusée','refusee','retard'])) return 'danger';
  if (in_array($s, ['expédiée','expediee','envoyée','envoyee'])) return 'primary';
  return 'secondary';
}
// === DOCS: chargement des documents de ce franchisé + helper badge ===
$docs = [];
$stDocs = $pdo->prepare("SELECT id, type, path, statut, uploaded_at FROM documents WHERE id_franchise=? ORDER BY type");
$stDocs->execute([$id_franchise]);
while ($row = $stDocs->fetch(PDO::FETCH_ASSOC)) { $docs[] = $row; }

function badgeDocAdmin($statut){
  $s = strtolower(trim($statut));
  return match($s){
    'valide' => '<span class="badge bg-success">Validé</span>',
    'refuse' => '<span class="badge bg-danger">Refusé</span>',
    default  => '<span class="badge bg-secondary">En attente</span>',
  };
}

?>
<!DOCTYPE html>
<html lang="fr" data-bs-theme="light">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Franchisé – Détails</title>

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
    .chip{ border-radius:999px; padding:.35rem .6rem; }
    .table thead th{ position:sticky; top:0; background:var(--bs-body-bg); z-index:1; }
    .search-input{ max-width:380px; }
    .empty{ border:1px dashed var(--bs-border-color); border-radius:1rem; padding:2rem; text-align:center; color:var(--bs-secondary-color); }
  </style>
</head>
<body class="bg-body-tertiary">

  <nav class="navbar navbar-expand-lg navbar-blur sticky-top">
    <div class="container">
      <a class="navbar-brand fw-semibold d-flex align-items-center gap-2" href="admin_franchises.php">
        <i class="bi bi-person-badge text-primary"></i> Détails franchisé
      </a>

      <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#topnav" aria-controls="topnav" aria-expanded="false" aria-label="Basculer la navigation">
        <span class="navbar-toggler-icon"></span>
      </button>

      <div class="collapse navbar-collapse" id="topnav">
        <ul class="navbar-nav me-auto mb-2 mb-lg-0">
          <li class="nav-item"><a class="nav-link" href="admin_dashboard.php">Tableau de bord</a></li>
          <li class="nav-item"><a class="nav-link" href="admin_gestion_camions.php">Camions</a></li>
          <li class="nav-item"><a class="nav-link" href="admin_franchises.php">Franchisés</a></li>
        </ul>

        <div class="d-flex align-items-center gap-2">
          <button id="themeToggle" class="btn btn-outline-secondary btn-sm btn-pill" type="button" aria-label="Basculer le thème">
            <i class="bi bi-moon-stars"></i>
          </button>
          <div class="dropdown">
            <button class="btn btn-primary btn-pill dropdown-toggle" data-bs-toggle="dropdown">
              <i class="bi bi-person-circle me-1"></i> <?= htmlspecialchars($nomAdmin, ENT_QUOTES, 'UTF-8') ?>
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

  <header class="hero py-5">
    <div class="container">
      <nav aria-label="breadcrumb" class="mb-2">
        <ol class="breadcrumb">
          <li class="breadcrumb-item"><a class="link-light text-decoration-underline" href="admin_dashboard.php">Accueil</a></li>
          <li class="breadcrumb-item"><a class="link-light text-decoration-underline" href="admin_franchises.php">Franchisés</a></li>
          <li class="breadcrumb-item active text-white">Détails</li>
        </ol>
      </nav>
      <h1 class="display-6 mb-2">👤 <?= htmlspecialchars($nomComplet, ENT_QUOTES, 'UTF-8') ?></h1>
      <p class="lead mb-0">Vue d’ensemble, activité et commandes récentes.</p>
    </div>
  </header>

  <main class="container py-5">

    <div class="row g-4 mb-4">
      <div class="col-12 col-lg-5">
        <div class="card card-zoom h-100">
          <div class="card-body p-4">
            <div class="d-flex align-items-start justify-content-between mb-3">
              <div>
                <h5 class="mb-1"><?= htmlspecialchars($nomComplet, ENT_QUOTES, 'UTF-8') ?></h5>
                <div class="d-flex flex-wrap gap-2">
                  <span class="chip text-bg-<?= $actif ? 'success' : 'danger' ?>">
                    <?= $actif ? 'Actif' : 'Bloqué' ?>
                  </span>
                  <?php if ($immat): ?>
                    <span class="chip text-bg-primary-subtle text-primary">
                      <i class="bi bi-truck-front me-1"></i><?= htmlspecialchars($immat, ENT_QUOTES, 'UTF-8') ?>
                    </span>
                  <?php else: ?>
                    <span class="chip text-bg-secondary">Aucun camion</span>
                  <?php endif; ?>
                </div>
              </div>
              <div class="dropdown">
                <button class="btn btn-outline-secondary btn-sm btn-pill dropdown-toggle" data-bs-toggle="dropdown">
                  Actions
                </button>
                <ul class="dropdown-menu dropdown-menu-end">
                  <li>
                    <button class="dropdown-item d-flex align-items-center gap-2" data-bs-toggle="modal" data-bs-target="#confirmStatut"
                      data-next="<?= $actif ? 0 : 1 ?>">
                      <i class="bi <?= $actif ? 'bi-slash-circle' : 'bi-check-circle' ?>"></i>
                      <?= $actif ? 'Bloquer' : 'Activer' ?>
                    </button>
                  </li>
                  <li><a class="dropdown-item d-flex align-items-center gap-2" href="admin_attribuer_camion.php"><i class="bi bi-link-45deg"></i>Attribuer un camion</a></li>
                </ul>
              </div>
            </div>

            <div class="mb-2">
              <i class="bi bi-envelope me-2"></i>
              <a id="emailLink" href="mailto:<?= htmlspecialchars($franchise['email'] ?? '', ENT_QUOTES, 'UTF-8') ?>" class="text-decoration-none">
                <?= htmlspecialchars($franchise['email'] ?? '', ENT_QUOTES, 'UTF-8') ?>
              </a>
              <button id="copyEmail" class="btn btn-sm btn-outline-secondary btn-pill ms-2"><i class="bi bi-clipboard"></i></button>
            </div>

            <?php if (!empty($franchise['adresse'])): ?>
              <div class="text-secondary">
                <i class="bi bi-geo-alt me-2"></i><?= htmlspecialchars($franchise['adresse'], ENT_QUOTES, 'UTF-8') ?>
              </div>
            <?php endif; ?>
          </div>
        </div>
      </div>

      <div class="col-12 col-lg-7">
        <div class="row g-3">
          <div class="col-12 col-sm-6">
            <div class="kpi"><i class="bi bi-currency-euro fs-5 text-success"></i>
              <div>
                <div class="fw-semibold"><?= number_format((float)$totalCA, 2, ',', ' ') ?> €</div>
                <div class="text-secondary small">Chiffre d’affaires total</div>
              </div>
            </div>
          </div>
          <div class="col-12 col-sm-6">
            <div class="kpi"><i class="bi bi-basket fs-5 text-primary"></i>
              <div>
                <div class="fw-semibold"><?= (int)$nbCommandes ?></div>
                <div class="text-secondary small">Nombre de commandes</div>
              </div>
            </div>
          </div>
          <div class="col-12 col-sm-6">
            <div class="kpi"><i class="bi bi-clock-history fs-5 text-warning"></i>
              <div>
                <div class="fw-semibold"><?= $derniereCommande ? $derniereCommande : '—' ?></div>
                <div class="text-secondary small">Dernière commande</div>
              </div>
            </div>
          </div>
          <div class="col-12 col-sm-6">
            <a href="admin_franchises.php" class="kpi text-decoration-none">
              <i class="bi bi-arrow-left-circle fs-5 text-secondary"></i>
              <div>
                <div class="fw-semibold">Retour</div>
                <div class="text-secondary small">Vers la liste des franchisés</div>
              </div>
            </a>
          </div>
        </div>
      </div>
    </div>
    <!-- ====== Documents (KBIS & CNI) ====== -->
<div class="card card-zoom mb-4">
  <div class="card-body p-4 p-lg-5">
    <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 mb-3">
      <h5 class="card-title m-0 d-flex align-items-center gap-2">
        <span class="badge rounded-pill text-bg-secondary"><i class="bi bi-file-earmark-text"></i></span>
        Documents (KBIS & CNI)
      </h5>
    </div>

    <?php if (empty($docs)): ?>
      <div class="empty">
        <div class="fs-6 mb-1">Aucun document transmis</div>
        <p class="mb-0">Quand le franchisé aura envoyé ses pièces, elles s’afficheront ici.</p>
      </div>
    <?php else: ?>
      <div class="table-responsive">
        <table class="table align-middle table-hover">
          <thead>
            <tr>
              <th>Type</th>
              <th>Statut</th>
              <th>Fichier</th>
              <th>Envoyé le</th>
              <th>Action</th>
            </tr>
          </thead>
          <tbody>
          <?php foreach($docs as $d): ?>
            <tr>
              <td><?= htmlspecialchars($d['type'], ENT_QUOTES, 'UTF-8') ?></td>
              <td><?= badgeDocAdmin($d['statut']) ?></td>
              <td><?= htmlspecialchars(basename($d['path']), ENT_QUOTES, 'UTF-8') ?></td>
              <td><?= htmlspecialchars($d['uploaded_at'], ENT_QUOTES, 'UTF-8') ?></td>
              <td class="d-flex gap-2">
                <form action="../php/admin_document_action.php" method="post" class="m-0">
                  <input type="hidden" name="id" value="<?= (int)$d['id'] ?>">
                  <input type="hidden" name="action" value="valider">
                  <!-- pour revenir ici après action -->
                  <input type="hidden" name="redirect" value="admin_fiche_franchise.php?id=<?= (int)$id_franchise ?>">
                  <button class="btn btn-success btn-sm">Valider</button>
                </form>
                <form action="../php/admin_document_action.php" method="post" class="m-0">
                  <input type="hidden" name="id" value="<?= (int)$d['id'] ?>">
                  <input type="hidden" name="action" value="refuser">
                  <input type="hidden" name="redirect" value="admin_fiche_franchise.php?id=<?= (int)$id_franchise ?>">
                  <button class="btn btn-danger btn-sm">Refuser</button>
                </form>
              </td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php endif; ?>
  </div>
</div>

    <div class="card card-zoom">
      <div class="card-body p-4 p-lg-5">
        <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 mb-3">
          <h5 class="card-title m-0 d-flex align-items-center gap-2">
            <span class="badge rounded-pill text-bg-secondary"><i class="bi bi-list-task"></i></span>
            Commandes
          </h5>
          <div class="input-group search-input">
            <span class="input-group-text"><i class="bi bi-search"></i></span>
            <input type="search" id="cmdSearch" class="form-control" placeholder="Rechercher par type, statut ou produit…">
          </div>
        </div>

        <?php if ($commandes): ?>
          <div class="table-responsive">
            <table class="table align-middle table-hover">
              <thead>
                <tr>
                  <th>Date</th>
                  <th>Type</th>
                  <th>Statut</th>
                  <th>Produits</th>
                </tr>
              </thead>
              <tbody id="cmdBody">
                <?php foreach ($commandes as $commande):
                  $dateAff = '';
                  if (!empty($commande['date_commande'])) {
                    $dt = new DateTime($commande['date_commande']);
                    $dateAff = $dt->format('d/m/Y H:i');
                  }
                  $type = ucfirst($commande['type_commande'] ?? '');
                  $statut = ucfirst($commande['statut'] ?? '');
                  $stmt2 = $pdo->prepare("
                    SELECT p.nom, lc.quantite
                    FROM ligne_commande lc
                    JOIN produits p ON lc.id_produit = p.id_produit
                    WHERE lc.id_commande = ?
                  ");
                  $stmt2->execute([$commande['id_commande']]);
                  $produits = $stmt2->fetchAll(PDO::FETCH_ASSOC);

                  $chipsSearch = [];
                  foreach ($produits as $p) { $chipsSearch[] = strtolower(($p['nom'] ?? '') . ' x' . ($p['quantite'] ?? '')); }
                  $rowSearch = strtolower($dateAff.' '.$type.' '.$statut.' '.implode(' ', $chipsSearch));
                  $badgeClass = 'text-bg-'.badgeStatutCommande($statut);
                ?>
                  <tr data-search="<?= htmlspecialchars($rowSearch, ENT_QUOTES, 'UTF-8') ?>">
                    <td class="text-nowrap"><?= htmlspecialchars($dateAff ?: '—', ENT_QUOTES, 'UTF-8') ?></td>
                    <td><span class="chip text-bg-info-subtle text-info"><?= htmlspecialchars($type ?: '—', ENT_QUOTES, 'UTF-8') ?></span></td>
                    <td><span class="badge <?= $badgeClass ?> chip"><?= htmlspecialchars($statut ?: '—', ENT_QUOTES, 'UTF-8') ?></span></td>
                    <td>
                      <?php if ($produits): ?>
                        <div class="d-flex flex-wrap gap-2">
                          <?php foreach ($produits as $p): ?>
                            <span class="chip text-bg-primary-subtle text-primary">
                              <?= htmlspecialchars($p['nom'], ENT_QUOTES, 'UTF-8') ?> × <?= (int)$p['quantite'] ?>
                            </span>
                          <?php endforeach; ?>
                        </div>
                      <?php else: ?>
                        <span class="text-secondary"><em>Aucun</em></span>
                      <?php endif; ?>
                    </td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        <?php else: ?>
          <div class="empty">
            <div class="fs-5 mb-2">Aucune commande</div>
            <p class="mb-0">Les commandes de ce franchisé apparaîtront ici.</p>
          </div>
        <?php endif; ?>
      </div>
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
          <form id="formStatut" action="../php/changer_statut_franchise.php" method="POST" class="m-0">
            <input type="hidden" name="id_franchise" value="<?= (int)$id_franchise ?>">
            <input type="hidden" name="nouvel_statut" id="nouvel_statut" value="">
            <button type="submit" id="confirmBtn" class="btn btn-warning btn-pill">Continuer</button>
          </form>
        </div>
      </div>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <script>
    const html = document.documentElement;
    const key = 'pref-theme';
    const btn = document.getElementById('themeToggle');
    const saved = localStorage.getItem(key);
    if (saved) html.setAttribute('data-bs-theme', saved);
    const setIcon = () => btn.innerHTML = (html.getAttribute('data-bs-theme') === 'dark') ? '<i class="bi bi-sun"></i>' : '<i class="bi bi-moon-stars"></i>';
    const toggleTheme = () => { const next = html.getAttribute('data-bs-theme') === 'dark' ? 'light' : 'dark'; html.setAttribute('data-bs-theme', next); localStorage.setItem(key, next); setIcon(); };
    btn.addEventListener('click', toggleTheme); setIcon();

    document.getElementById('copyEmail')?.addEventListener('click', async () => {
      const email = document.getElementById('emailLink')?.textContent?.trim();
      if (!email) return;
      try { await navigator.clipboard.writeText(email); }
      catch {}
    });

    const cmdSearch = document.getElementById('cmdSearch');
    const cmdRows = document.querySelectorAll('#cmdBody tr');
    cmdSearch?.addEventListener('input', () => {
      const q = cmdSearch.value.toLowerCase().trim();
      cmdRows.forEach(r => {
        const hay = r.getAttribute('data-search') || '';
        r.style.display = hay.includes(q) ? '' : 'none';
      });
    });

    const modal = document.getElementById('confirmStatut');
    const confirmText = document.getElementById('confirmText');
    const confirmBtn = document.getElementById('confirmBtn');
    const nouvelStatut = document.getElementById('nouvel_statut');
    modal?.addEventListener('show.bs.modal', (e) => {
      const button = e.relatedTarget;
      const next = button?.getAttribute('data-next'); // "0" or "1"
      confirmText.textContent = (next === '0') ? 'Bloquer ce franchisé ?' : 'Activer ce franchisé ?';
      confirmBtn.className = 'btn btn-pill ' + (next === '0' ? 'btn-warning' : 'btn-success');
      confirmBtn.textContent = (next === '0' ? 'Bloquer' : 'Activer');
      nouvelStatut.value = next;
    });
  </script>
</body>
</html>
