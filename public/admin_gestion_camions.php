<?php
session_start();
require_once '../config/db.php';

if (!isset($_SESSION['id_admin'])) {
    header("Location: admin_login.php");
    exit;
}

$nom = $_SESSION['nom_admin'] ?? 'Admin';

/* ---------- Récupération des camions + jointure franchisés ---------- */
$camionsStmt = $pdo->query("
    SELECT 
        c.id_camion, c.immatriculation, c.etat, c.date_prochain_entretien, c.id_franchise,
        c.latitude, c.longitude,
        c.marque, c.modele, c.kilometrage, c.annee, c.image_url,
        f.nom  AS franchise_nom,
        f.prenom AS franchise_prenom
    FROM camions c
    LEFT JOIN franchises f ON f.id_franchise = c.id_franchise
    ORDER BY c.id_camion DESC
");
$camions = $camionsStmt->fetchAll(PDO::FETCH_ASSOC);

/* ---------- Stats header ---------- */
$total        = count($camions);
$attribues    = count(array_filter($camions, fn($c) => !empty($c['id_franchise'])));
$nonAttribues = $total - $attribues;

$entretiens30 = 0;
$today = new DateTimeImmutable();
$limit = $today->modify('+30 days');
foreach ($camions as $c) {
    if (!empty($c['date_prochain_entretien'])) {
        $d = DateTimeImmutable::createFromFormat('Y-m-d', substr($c['date_prochain_entretien'], 0, 10));
        if ($d && $d <= $limit) $entretiens30++;
    }
}

?>
<!DOCTYPE html>
<html lang="fr" data-bs-theme="dark">
<head>
  <meta charset="UTF-8">
  <title>Gestion des camions</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
  <style>
    body { background: #0f172a; }
    .card { background: #1e293b; border-color:#334155; }
    .hero {
      background: linear-gradient(135deg, #6d28d9, #0ea5e9);
      border-bottom-left-radius: 1rem; border-bottom-right-radius: 1rem;
    }
    .avatar {
      width:56px;height:40px;object-fit:cover;border-radius:6px;border:1px solid #334155;
    }
    .table > :not(caption) > * > * { background-color: transparent !important; }
    .badge-soft { background: rgba(148,163,184,.15); color: #e2e8f0; }
  </style>
</head>
<body>

<!-- topbar simple -->
<nav class="navbar navbar-dark bg-dark border-bottom border-secondary">
  <div class="container-fluid">
    <a class="navbar-brand fw-semibold" href="admin_dashboard.php">Drive’n Cook — Admin</a>
    <div class="d-flex align-items-center gap-3">
      <span class="text-secondary">Bonjour, <?= htmlspecialchars($nom) ?></span>
      <a class="btn btn-sm btn-outline-light" href="admin_logout.php"><i class="bi bi-box-arrow-right me-1"></i>Déconnexion</a>
    </div>
  </div>
</nav>

<!-- hero -->
<header class="hero py-5 mb-4 text-white">
  <div class="container">
    <nav aria-label="breadcrumb" class="mb-2">
      <ol class="breadcrumb">
        <li class="breadcrumb-item"><a class="link-light" href="admin_dashboard.php">Accueil</a></li>
        <li class="breadcrumb-item active text-white">Gestion des camions</li>
      </ol>
    </nav>
    <h1 class="mb-1">🚚 Gestion des camions</h1>
    <p class="mb-0">Ajoutez des véhicules, visualisez les attributions et surveillez les entretiens.</p>
  </div>
</header>

<div class="container pb-5">

  <!-- KPIs -->
  <div class="row g-3 mb-4">
    <div class="col-6 col-md-3">
      <div class="card p-3">
        <div class="text-secondary">Total camions</div>
        <div class="h3 mb-0"><?= $total ?></div>
      </div>
    </div>
    <div class="col-6 col-md-3">
      <div class="card p-3">
        <div class="text-secondary">Attribués</div>
        <div class="h3 mb-0"><?= $attribues ?></div>
      </div>
    </div>
    <div class="col-6 col-md-3">
      <div class="card p-3">
        <div class="text-secondary">Non attribués</div>
        <div class="h3 mb-0"><?= $nonAttribues ?></div>
      </div>
    </div>
    <div class="col-6 col-md-3">
      <div class="card p-3">
        <div class="text-secondary">Entretiens (&lt; 30 j)</div>
        <div class="h3 mb-0"><?= $entretiens30 ?></div>
      </div>
    </div>
  </div>

  <!-- Ajout d'un camion -->
  <div class="card p-3 mb-4">
    <div class="d-flex justify-content-between align-items-center mb-2">
      <h5 class="mb-0"><i class="bi bi-plus-circle me-2"></i>Ajouter un nouveau camion</h5>
      <a class="btn btn-outline-secondary btn-sm" href="admin_attribuer_camion.php">
        <i class="bi bi-people me-1"></i>Attribuer un camion
      </a>
    </div>
    <!-- IMPORTANT: enctype pour l'upload -->
    <form class="row g-3" action="../php/ajouter_camion.php" method="POST" enctype="multipart/form-data">
      <div class="col-12 col-md-3">
        <label class="form-label">Immatriculation</label>
        <input type="text" name="immatriculation" class="form-control" placeholder="AA-123-BB" required>
      </div>
      <div class="col-12 col-md-3">
        <label class="form-label">État</label>
        <select name="etat" class="form-select">
          <option value="Bon">Bon / Révisé…</option>
          <option value="Moyen">Moyen</option>
          <option value="Mauvais">Mauvais</option>
        </select>
      </div>
      <div class="col-12 col-md-3">
        <label class="form-label">Prochain entretien</label>
        <input type="date" name="date_entretien" class="form-control">
      </div>
      <div class="col-12 col-md-3">
        <label class="form-label">Adresse</label>
        <input type="text" name="adresse" class="form-control" placeholder="10 rue de Paris, Toulouse">
      </div>

      <!-- nouveaux champs -->
      <div class="col-12 col-md-3">
        <label class="form-label">Marque</label>
        <input type="text" name="marque" class="form-control" placeholder="Renault, Mercedes…">
      </div>
      <div class="col-12 col-md-3">
        <label class="form-label">Modèle</label>
        <input type="text" name="modele" class="form-control" placeholder="Master, Sprinter…">
      </div>
      <div class="col-12 col-md-3">
        <label class="form-label">Kilométrage</label>
        <input type="number" name="kilometrage" min="0" step="1" class="form-control" placeholder="0">
      </div>
      <div class="col-12 col-md-3">
        <label class="form-label">Année</label>
        <input type="number" name="annee" min="1980" max="2100" step="1" class="form-control" placeholder="2022">
      </div>

      <!-- ICI: on remplace l'URL par un input fichier -->
      <div class="col-12 col-md-6">
        <label class="form-label" for="image">Photo du camion</label>
        <input type="file" name="image" id="image" class="form-control" accept="image/*">
        <small class="text-secondary">Formats: jpg/png/webp. Max conseillé ~5 Mo.</small>
      </div>
      <div class="col-12 col-md-6 d-flex align-items-end">
        <img id="previewCamion" src="" alt="" class="rounded avatar" style="width:110px;height:70px;display:none;">
      </div>

      <div class="col-12">
        <button class="btn btn-success"><i class="bi bi-check2-circle me-1"></i>Ajouter</button>
      </div>
    </form>
  </div>

  <!-- Liste des camions -->
  <div class="card p-3">
    <div class="d-flex justify-content-between align-items-center mb-2">
      <h5 class="mb-0"><i class="bi bi-list-ul me-2"></i>Liste des camions</h5>
      <div class="input-group" style="max-width: 320px;">
        <span class="input-group-text"><i class="bi bi-search"></i></span>
        <input id="search" type="text" class="form-control" placeholder="Rechercher immatriculation ou franchisé…">
      </div>
    </div>

    <div class="table-responsive">
      <table class="table align-middle table-hover">
        <thead>
          <tr>
            <th class="text-nowrap">ID</th>
            <th>Photo</th>
            <th>Immatriculation</th>
            <th>Marque / Modèle</th>
            <th>Année</th>
            <th>Km</th>
            <th>État</th>
            <th class="text-nowrap">Prochain entretien</th>
            <th>Attribué à</th>
            <th class="text-end">Action</th>
          </tr>
        </thead>
        <tbody id="camionsBody">
        <?php foreach ($camions as $c): 
            $id      = (int)$c['id_camion'];
            $immat   = htmlspecialchars($c['immatriculation']);
            $etat    = htmlspecialchars($c['etat']);
            $dateAff = $c['date_prochain_entretien'] ? date('d/m/Y', strtotime($c['date_prochain_entretien'])) : null;
            $frLabel = $c['id_franchise'] ? htmlspecialchars(($c['franchise_prenom'] ?? '').' '.strtoupper($c['franchise_nom'] ?? '')) : '';
            $img     = $c['image_url'] ?? '';
            $marque  = $c['marque'] ?? '';
            $modele  = $c['modele'] ?? '';
            $annee   = $c['annee'] ?? null;
            $km      = $c['kilometrage'] ?? null;

            // badge entretien
            $badge = '<span class="badge badge-soft">—</span>';
            if ($dateAff) {
              $d = DateTimeImmutable::createFromFormat('d/m/Y', $dateAff);
              if ($d) {
                $diff = (int)$d->diff($today)->format('%r%a');
                if ($diff < 0 && $diff >= -7)      $badge = '<span class="badge text-bg-warning"><i class="bi bi-exclamation-triangle me-1"></i>Bientôt</span>';
                elseif ($diff >= 0)               $badge = '<span class="badge text-bg-danger"><i class="bi bi-x-octagon me-1"></i>En retard</span>';
                else                               $badge = '<span class="badge text-bg-success"><i class="bi bi-check2 me-1"></i>OK</span>';
              }
            }
        ?>
          <tr>
            <td class="text-secondary">#<?= $id ?></td>
            <td>
              <?php if ($img): ?>
                <img src="<?= htmlspecialchars($img) ?>" class="avatar" alt="Camion">
              <?php else: ?>
                <span class="text-secondary">—</span>
              <?php endif; ?>
            </td>
            <td class="fw-semibold"><?= $immat ?></td>
            <td>
              <div class="fw-semibold"><?= htmlspecialchars($marque ?: '—') ?></div>
              <div class="small text-secondary"><?= htmlspecialchars($modele ?: '') ?></div>
            </td>
            <td><?= $annee ? (int)$annee : '—' ?></td>
            <td><?= ($km !== null && $km !== '') ? number_format((int)$km, 0, ',', ' ') . ' km' : '—' ?></td>
            <td><?= $etat ?></td>
            <td>
              <div class="d-flex align-items-center gap-2">
                <?= $badge ?>
                <span class="text-nowrap"><?= $dateAff ?: '—' ?></span>
              </div>
            </td>
            <td>
              <?php if ($c['id_franchise']): ?>
                <span class="badge text-bg-primary-subtle border border-primary">
                  <i class="bi bi-person-fill me-1"></i><?= $frLabel ?>
                </span>
              <?php else: ?>
                <span class="text-secondary"><em>Aucun</em></span>
              <?php endif; ?>
            </td>
            <td class="text-end">
              <?php if ($c['id_franchise']): ?>
                <button class="btn btn-sm btn-warning btn-liberer" data-bs-toggle="modal" data-bs-target="#confirmLiberer" data-id="<?= $id ?>">
                  <i class="bi bi-box-arrow-up-right"></i> Retirer
                </button>
              <?php else: ?>
                <a class="btn btn-sm btn-outline-secondary" href="admin_attribuer_camion.php">
                  <i class="bi bi-people"></i> Attribuer
                </a>
              <?php endif; ?>
            </td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<!-- Modal libérer -->
<div class="modal fade" id="confirmLiberer" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content bg-dark border-secondary">
      <div class="modal-header border-0">
        <h5 class="modal-title">Retirer le camion du franchisé</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        Êtes-vous sûr de vouloir <strong>libérer</strong> ce camion ?
      </div>
      <div class="modal-footer border-0">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
        <a id="libererLink" href="#" class="btn btn-warning">Retirer</a>
      </div>
    </div>
  </div>
</div>

<script>
  // preview du fichier image
  (function () {
    const fileInput = document.getElementById('image');
    const prev = document.getElementById('previewCamion');
    if (!fileInput || !prev) return;

    fileInput.addEventListener('change', () => {
      const file = fileInput.files?.[0];
      if (!file) { prev.style.display = 'none'; return; }
      const url = URL.createObjectURL(file);
      prev.src = url;
      prev.style.display = 'block';
    });
  })();

  // simple search (immat/franchisé)
  const input = document.getElementById('search');
  const body  = document.getElementById('camionsBody');
  input?.addEventListener('input', () => {
    const q = input.value.toLowerCase();
    [...body.querySelectorAll('tr')].forEach(tr => {
      const s = tr.innerText.toLowerCase();
      tr.style.display = s.includes(q) ? '' : 'none';
    });
  });

  // lien libérer
  const modal = document.getElementById('confirmLiberer');
  modal?.addEventListener('show.bs.modal', e => {
    const btn = e.relatedTarget;
    const id  = btn.getAttribute('data-id');
    modal.querySelector('#libererLink').setAttribute('href', '../php/liberer_camion.php?id=' + id);
  });
</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
