<?php
session_start();
require_once '../config/db.php';
require_once '../php/middleware_onboarding.php';

$fid = (int)$_SESSION['id_franchise'];

$stmt = $pdo->prepare("SELECT nom, prenom FROM franchises WHERE id_franchise=?");
$stmt->execute([$fid]);
$u = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];

$nom    = (string)($u['nom']    ?? ($_SESSION['nom']    ?? ''));
$prenom = (string)($u['prenom'] ?? ($_SESSION['prenom'] ?? ''));

function display_name(string $prenom = '', string $nom = ''): string {
  $p = $prenom !== '' ? ucfirst($prenom) : '';
  $n = $nom !== '' ? strtoupper($nom) : '';
  return trim("$p $n");
}


$fid = (int)$_SESSION['id_franchise'];
$st = $pdo->prepare("SELECT paid_entry_fee FROM franchises WHERE id_franchise=?");
$st->execute([$fid]);
$paid = (int)$st->fetchColumn();



if (!isset($_SESSION['id_franchise'])) {
    header("Location: login.html");
    exit;
}

$id_franchise = $_SESSION['id_franchise'];
//$nom = $_SESSION['nom'];
//$prenom = $_SESSION['prenom'];
// Garde l’ID si présent, sinon redirige
$id_franchise = $_SESSION['id_franchise'] ?? null;
if (!$id_franchise) {
    header("Location: login.html");
    exit;
}
// NE PAS réassigner $nom/$prenom ici : ils sont déjà remplis plus haut (sources: DB/SESSION)

// Photo de profil
$stmt = $pdo->prepare("SELECT photo FROM franchises WHERE id_franchise = ?");
$stmt->execute([$id_franchise]);
$franchise = $stmt->fetch();
$photo = $franchise['photo'] ?? '';

// Camion
$sql = "SELECT * FROM camions WHERE id_franchise = ?";
$stmt = $pdo->prepare($sql);
$stmt->execute([$id_franchise]);
$camion = $stmt->fetch();

// Commandes
$sql = "SELECT COUNT(*) FROM commandes WHERE id_franchise = ?";
$stmt = $pdo->prepare($sql);
$stmt->execute([$id_franchise]);
$totalCommandes = $stmt->fetchColumn();

// Ventes
$sql = "SELECT date_vente, montant FROM ventes WHERE id_franchise = ? ORDER BY date_vente";
$stmt = $pdo->prepare($sql);
$stmt->execute([$id_franchise]);
$ventes = $stmt->fetchAll();
$totalCA = array_sum(array_column($ventes, 'montant'));
?>
<!DOCTYPE html>
<html lang="fr" data-theme="light">
<head>
  <meta charset="UTF-8">
  <title>Dashboard Franchisé</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <!-- Bootstrap + Icons -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <style>
    :root {
      /* brand */
      --primary: #e63946;

      /* light theme (default) */
      --bg: #f6f7fb;
      --surface: #ffffff;
      --text: #111827;
      --muted: #6c757d;
      --border: #e9ecef;
      --hover: #fafafa;
      --shadow: 0 6px 14px rgba(16, 24, 40, 0.06);
      --sidebar-link: #2b2d42;
      --sidebar-link-hover: #f8f9fa;
    }
    /* DARK THEME OVERRIDES */
    [data-theme="dark"] {
      color-scheme: dark;
      --bg: #0b1220;
      --surface: #0f172a;      /* slate-900 */
      --text: #e5e7eb;         /* gray-200 */
      --muted: #94a3b8;        /* slate-400 */
      --border: #1f2937;       /* gray-800 */
      --hover: #0b132b;
      --shadow: 0 6px 14px rgba(0,0,0,0.45);
      --sidebar-link: #cbd5e1;
      --sidebar-link-hover: #111827;
    }

    body { background: var(--bg); color: var(--text); }
    .topbar {
      position: sticky; top: 0; z-index: 1020;
      background: var(--surface); border-bottom: 1px solid var(--border);
    }
    .sidebar {
      width: 260px; min-height: 100vh; background: var(--surface);
      border-right: 1px solid var(--border);
    }
    .brand { font-weight: 800; color: var(--text); letter-spacing: .5px; }

    .sidebar .nav-link {
      color: var(--sidebar-link); border-radius: 8px; padding: .6rem .85rem;
      display: flex; align-items: center; gap: .5rem;
    }
    .sidebar .nav-link.active,
    .sidebar .nav-link:hover {
      background: var(--sidebar-link-hover); color: var(--primary);
    }

    .card {
      border: none; border-radius: 14px;
      background: var(--surface); box-shadow: var(--shadow);
      color: var(--text);
    }
    .card .card-title { font-weight: 700; color: var(--text); }
    .card-header { background: var(--surface); border-bottom: 1px solid var(--border); }

    .card-stat { display: flex; align-items: center; gap: 1rem; }
    .icon-badge {
      width: 46px; height: 46px; border-radius: 12px;
      display: grid; place-items: center; color: #fff; font-size: 1.25rem;
      background: var(--primary);
      box-shadow: 0 6px 14px rgba(230, 57, 70, 0.25);
    }

    .btn-primary { background: var(--primary); border-color: var(--primary); }
    .btn-outline-primary { color: var(--primary); border-color: var(--primary); }
    .btn-outline-primary:hover { background: var(--primary); color: #fff; }

    .table thead th {
      border-bottom: none; color: var(--muted);
      text-transform: uppercase; font-size: .8rem;
      background: var(--surface);
    }
    .table tbody tr:hover { background: var(--hover); }
    [data-theme="dark"] .dropdown-menu {
      background: var(--surface); border-color: var(--border); color: var(--text);
    }
    [data-theme="dark"] .dropdown-item { color: var(--text); }
    [data-theme="dark"] .dropdown-item:hover { background: var(--hover); }

    .avatar {
      width: 44px; height: 44px; border-radius: 50%; object-fit: cover;
      border: 2px solid #f1f3f5;
    }
    .sidebar .profile-pic {
      width: 110px; height: 110px; border-radius: 50%; object-fit: cover; border: 3px solid #f1f3f5;
    }

    .section-title { font-weight: 800; color: var(--text); }
    .small-muted { color: var(--muted); font-size: .925rem; }
  </style>
</head>
<body>
  <!-- Topbar -->
  <nav class="topbar navbar navbar-expand-lg px-3">
    <a class="navbar-brand brand d-flex align-items-center gap-2" href="#">
      🚛 Driv'n Cook
    </a>
    <div class="ms-auto d-flex align-items-center gap-2 gap-md-3">
      <!-- THEME TOGGLE -->
      <button id="themeToggle" class="btn btn-sm btn-outline-secondary" type="button" aria-label="Basculer le thème">
        <i class="bi bi-moon-stars"></i>
      </button>

      <a href="mes_commandes.php" class="btn btn-sm btn-outline-primary"><i class="bi bi-bag-check"></i> Mes commandes</a>
      <a href="../php/export_ventes_pdf.php" class="btn btn-sm btn-primary"><i class="bi bi-file-earmark-pdf"></i> Export PDF</a>
      <div class="dropdown">
        <a href="#" class="d-flex align-items-center text-decoration-none dropdown-toggle" data-bs-toggle="dropdown">
          <?php if (!empty($photo) && file_exists("../uploads/$photo")): ?>
            <img src="../uploads/<?= htmlspecialchars($photo) ?>" class="avatar me-2" alt="Profil">
          <?php else: ?>
            <div class="avatar me-2 d-grid place-items-center bg-light text-muted"><i class="bi bi-person"></i></div>
          <?php endif; ?>
          <span class="fw-semibold"><?= htmlspecialchars(display_name($prenom, $nom)) ?>
          </span>
        </a>
        <ul class="dropdown-menu dropdown-menu-end">
          <li><a class="dropdown-item" href="profil.php"><i class="bi bi-person-gear me-2"></i>Profil</a></li>
          <li><hr class="dropdown-divider"></li>
          <li><a class="dropdown-item text-danger" href="logout.php"><i class="bi bi-box-arrow-right me-2"></i>Se déconnecter</a></li>
        </ul>
      </div>
    </div>
  </nav>
  </nav>

<?php if (!$paid): ?>
<div class="alert alert-warning border-start border-4 border-warning-subtle d-flex align-items-center justify-content-between mt-3" role="alert">
  <div class="me-3">
    <strong>Action requise :</strong> votre droit d’entrée n’est pas encore réglé. 
    Certaines fonctionnalités sont désactivées tant que le paiement n’est pas effectué.
  </div>
  <a class="btn btn-sm btn-primary" href="pay_entry_fee.php">
    <i class="bi bi-credit-card me-1"></i> Payer maintenant
  </a>
</div>
<?php endif; ?>

<main class="container py-5">

  <div class="d-flex">
    <!-- Sidebar -->
    <aside class="sidebar p-3">
      <div class="text-center mb-3">
        <?php if (!empty($photo) && file_exists("../uploads/$photo")): ?>
          <img src="../uploads/<?= htmlspecialchars($photo) ?>" class="profile-pic" alt="Photo de profil">
        <?php else: ?>
          <div class="profile-pic d-grid place-items-center bg-light text-muted"><i class="bi bi-person" style="font-size:2rem;"></i></div>
        <?php endif; ?>
        <div class="small-muted mt-2">Bienvenue</div>
        <div class="fw-bold"><?= htmlspecialchars(ucfirst($prenom) . ' ' . strtoupper($nom)) ?></div>
      </div>
      <hr>
      <ul class="nav flex-column gap-1">
        <li class="nav-item"><a href="dashboard.php" class="nav-link active"><i class="bi bi-speedometer2"></i> Dashboard</a></li>
        <li class="nav-item"><a href="mes_commandes.php" class="nav-link"><i class="bi bi-bag"></i> Mes commandes</a></li>
        <li class="nav-item"><a href="ajout_produit_commande.php" class="nav-link"><i class="bi bi-plus-square"></i> Ajouter un produit</a></li>
        <li class="nav-item"><a href="profil.php" class="nav-link"><i class="bi bi-person-gear"></i> Modifier mon profil</a></li>
        <li class="nav-item"><a href="logout.php" class="nav-link text-danger"><i class="bi bi-box-arrow-right"></i> Se déconnecter</a></li>
      </ul>
      <div class="mt-4 p-3 rounded" style="background: #fff4f4;">
        <div class="d-flex align-items-start gap-2">
          <div class="icon-badge" style="background:#ffa94d;"><i class="bi bi-lightning-charge"></i></div>
          <div>
            <div class="fw-semibold">Astuce</div>
            <div class="small-muted">Exportez vos ventes pour l’analyse mensuelle.</div>
          </div>
        </div>
      </div>
    </aside>

    <!-- Main -->
    <main class="p-4 w-100">
      <h3 class="section-title mb-3">Bonjour, <span class="text-danger"><?= htmlspecialchars($prenom !== '' ? ucfirst($prenom) : ''); ?>
      </span> 👋</h3>
      <p class="small-muted mb-4">Voici un aperçu de votre activité et vos actions rapides.</p>

      <!-- Stats -->
      <div class="row g-3 mb-4">
        <div class="col-md-4">
          <div class="card p-3">
            <div class="card-stat">
              <div class="icon-badge"><i class="bi bi-cash-coin"></i></div>
              <div>
                <div class="small-muted">Chiffre d'affaires</div>
                <div class="h3 mb-0"><?= number_format($totalCA, 2, ',', ' ') ?> €</div>
              </div>
            </div>
          </div>
        </div>
        <div class="col-md-4">
          <div class="card p-3">
            <div class="card-stat">
              <div class="icon-badge" style="background:#3a86ff;"><i class="bi bi-receipt"></i></div>
              <div>
                <div class="small-muted">Commandes</div>
                <div class="h3 mb-0"><?= (int)$totalCommandes ?></div>
              </div>
            </div>
          </div>
        </div>
        <div class="col-md-4">
          <div class="card p-3">
            <div class="card-stat">
              <div class="icon-badge" style="background:#2a9d8f;"><i class="bi bi-truck"></i></div>
              <div>
                <div class="small-muted">Camion</div>
                <div class="h6 mb-0">
                  <?php if ($camion): ?>
                    <?= htmlspecialchars($camion['immatriculation']) ?> — <span class="text-muted"><?= htmlspecialchars($camion['etat']) ?></span>
                  <?php else: ?>
                    <span class="text-danger">Aucun camion attribué</span>
                  <?php endif; ?>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Camion + Ventes highlight -->
      <div class="row g-3 mb-4">
        <div class="col-md-6">
          <div class="card h-100">
            <div class="card-body">
              <div class="d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0">Mon camion</h5>
                <span class="badge rounded-pill text-bg-light"><i class="bi bi-wrench-adjustable-circle"></i></span>
              </div>
              <hr>
              <?php if ($camion): ?>
                <div class="row g-2">
                  <div class="col-6 small-muted">Immatriculation</div>
                  <div class="col-6 fw-semibold text-end"><?= htmlspecialchars($camion['immatriculation']) ?></div>

                  <div class="col-6 small-muted">État</div>
                  <div class="col-6 fw-semibold text-end"><?= htmlspecialchars($camion['etat']) ?></div>

                  <div class="col-6 small-muted">Prochain entretien</div>
                  <div class="col-6 fw-semibold text-end"><?= htmlspecialchars($camion['date_prochain_entretien']) ?></div>
                </div>
              <?php else: ?>
                <div class="alert alert-warning d-flex align-items-center" role="alert">
                  <i class="bi bi-exclamation-triangle me-2"></i> Aucun camion attribué.
                </div>
              <?php endif; ?>
            </div>
          </div>
        </div>

        <div class="col-md-6">
          <div class="card h-100 text-white" style="background: linear-gradient(135deg, #e63946, #d0003a);">
            <div class="card-body">
              <div class="d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0">Mes ventes</h5>
                <a href="../php/export_ventes_pdf.php" class="btn btn-sm btn-outline-light"><i class="bi bi-file-earmark-arrow-down"></i> Exporter</a>
              </div>
              <hr class="border-light">
              <div class="display-6 fw-bold"><?= number_format($totalCA, 2, ',', ' ') ?> €</div>
              <div class="small text-white-50">Total toutes périodes</div>
            </div>
          </div>
        </div>
      </div>

      <!-- Commandes & Graph -->
      <div class="row g-3 mb-4">
        <div class="col-md-5">
          <div class="card h-100">
            <div class="card-body d-flex flex-column">
              <h5 class="card-title">Mes commandes</h5>
              <p class="small-muted mb-2">Vous avez <strong><?= (int)$totalCommandes ?></strong> commande(s).</p>
              <div class="mt-auto d-flex gap-2">
                <a href="mes_commandes.php" class="btn btn-outline-primary"><i class="bi bi-eye"></i> Voir</a>
                <a href="ajout_produit_commande.php" class="btn btn-primary"><i class="bi bi-plus-lg"></i> Ajouter un produit</a>
              </div>
            </div>
          </div>
        </div>
        <div class="col-md-7">
          <div class="card h-100">
            <div class="card-body">
              <div class="d-flex justify-content-between align-items-center mb-2">
                <h5 class="card-title mb-0">Ventes mensuelles</h5>
                <span class="small-muted">Tendance</span>
              </div>
              <canvas id="chartVentes" height="120" aria-label="Graphique des ventes mensuelles" role="img"></canvas>
            </div>
          </div>
        </div>
      </div>

      <!-- Forms -->
      <div class="row g-3 mb-4">
        <div class="col-lg-6">
          <div class="card">
            <div class="card-header fw-bold">
              <i class="bi bi-basket2 me-2 text-danger"></i> Passer une commande
            </div>
            <div class="card-body">
              <form action="../php/nouvelle_commande.php" method="POST" class="row g-3">
                <div class="col-md-6">
                  <label class="form-label">Type</label>
                  <select name="type_commande" class="form-select" required>
                    <option value="entrepot">Commande depuis entrepôt</option>
                    <option value="libre">Commande libre</option>
                  </select>
                </div>
                <div class="col-md-6">
                  <label class="form-label">Entrepôt</label>
                  <select name="id_entrepot" class="form-select">
                    <option value="">-- Aucun --</option>
                    <?php
                      $entrepots = $pdo->query("SELECT id_entrepot, nom FROM entrepots")->fetchAll();
                      foreach ($entrepots as $entrepot) {
                        echo "<option value='{$entrepot['id_entrepot']}'>".htmlspecialchars($entrepot['nom'])."</option>";
                      }
                    ?>
                  </select>
                </div>
                <div class="col-12 text-end">
                  <button type="submit" class="btn btn-danger"><i class="bi bi-check2-circle me-1"></i> Valider la commande</button>
                </div>
              </form>
            </div>
          </div>
        </div>

        <div class="col-lg-6">
          <div class="card">
            <div class="card-header fw-bold">
              <i class="bi bi-plus-circle me-2 text-success"></i> Enregistrer une vente
            </div>
            <div class="card-body">
              <form action="../php/ajouter_vente.php" method="POST" class="row g-3">
                <div class="col-md-6">
                  <label class="form-label">Montant (€)</label>
                  <input type="number" name="montant" step="0.01" min="0.01" class="form-control" placeholder="0,00" required>
                </div>
                <div class="col-md-6">
                  <label class="form-label">Date de vente</label>
                  <input type="date" name="date_vente" class="form-control" value="<?= date('Y-m-d') ?>">
                </div>
                <div class="col-12 text-end">
                  <button type="submit" class="btn btn-success"><i class="bi bi-save me-1"></i> Enregistrer</button>
                </div>
              </form>
            </div>
          </div>
        </div>
      </div>

      <!-- Historique des ventes -->
      <div class="card mb-5">
        <div class="card-header fw-bold">
          <i class="bi bi-clock-history me-2 text-primary"></i> Historique des ventes
        </div>
        <div class="card-body">
          <?php if (count($ventes) > 0): ?>
            <div class="table-responsive">
              <table class="table align-middle">
                <thead>
                  <tr><th>Date</th><th>Montant (€)</th></tr>
                </thead>
                <tbody>
                  <?php foreach ($ventes as $vente): ?>
                    <tr>
                      <td><?= htmlspecialchars(date('d/m/Y', strtotime($vente['date_vente']))) ?></td>
                      <td class="fw-semibold"><?= number_format($vente['montant'], 2, ',', ' ') ?> €</td>
                    </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>
          <?php else: ?>
            <div class="text-center py-4">
              <i class="bi bi-emoji-neutral text-muted" style="font-size:2rem;"></i>
              <p class="mt-2 mb-0">Aucune vente enregistrée.</p>
              <small class="text-muted">Commencez en enregistrant votre première vente ci-dessus.</small>
            </div>
          <?php endif; ?>
        </div>
      </div>

      <!-- Carte des entrepôts et camions libres (appel à carte.php) -->
      <div class="card mb-5">
        <div class="card-header fw-bold">
          <i class="bi bi-geo-alt-fill text-success me-2"></i> Carte des entrepôts et camions libres
        </div>
        <div class="card-body p-0">
          <!-- Appel direct à carte.php qui gère tout (HTML + Leaflet + données) -->
          <iframe
            src="carte.php"
            style="width:100%; height:520px; border:0;"
            title="Carte des entrepôts et camions">
          </iframe>
        </div>
      </div>

    </main>
  </div>

  <!-- Chart + Theme logic -->
  <script>
    // ---------- Data prep ----------
    const rawVentes = <?php
      $labels = [];
      $data = [];
      foreach ($ventes as $vente) {
        $labels[] = date('Y-m', strtotime($vente['date_vente']));
        $data[] = (float)$vente['montant'];
      }
      echo json_encode(['labels' => $labels, 'data' => $data], JSON_UNESCAPED_UNICODE);
    ?>;
    const monthMap = new Map();
    rawVentes.labels.forEach((ym, i) => monthMap.set(ym, (monthMap.get(ym) || 0) + (Number(rawVentes.data[i]) || 0)));
    const monthsSorted = Array.from(monthMap.keys()).sort();
    const monthLabels = monthsSorted.map(ym => {
      const [y,m] = ym.split('-'); const d = new Date(Number(y), Number(m)-1, 1);
      return d.toLocaleDateString('fr-FR', { month: 'short', year: '2-digit' });
    });
    const monthValues = monthsSorted.map(k => monthMap.get(k));

    // ---------- Theme helpers ----------
    const root = document.documentElement;
    const THEME_KEY = 'pref-theme-franch';
    const savedTheme = localStorage.getItem(THEME_KEY);
    if (savedTheme) root.setAttribute('data-theme', savedTheme);

    function currentTheme(){ return root.getAttribute('data-theme') === 'dark' ? 'dark' : 'light'; }
    function setToggleIcon(){
      const btn = document.getElementById('themeToggle');
      btn.innerHTML = currentTheme() === 'dark' ? '<i class="bi bi-sun"></i>' : '<i class="bi bi-moon-stars"></i>';
    }

    // ---------- Chart.js setup ----------
    let salesChart;
    function chartColors(theme){
      if (theme === 'dark') {
        return {
          border: '#f87171',
          gradientTop: 'rgba(248,113,113,0.35)',
          gradientBottom: 'rgba(248,113,113,0.05)',
          xTick: '#94a3b8',
          yTick: '#94a3b8',
          grid: 'rgba(148,163,184,0.15)'
        };
      }
      return {
        border: '#e63946',
        gradientTop: 'rgba(230,57,70,0.25)',
        gradientBottom: 'rgba(230,57,70,0.02)',
        xTick: '#6b7280',
        yTick: '#6b7280',
        grid: 'rgba(0,0,0,0.06)'
      };
    }

    function createSalesChart(){
      const ctx = document.getElementById('chartVentes').getContext('2d');
      const theme = currentTheme();
      const c = chartColors(theme);
      const gradient = ctx.createLinearGradient(0, 0, 0, 200);
      gradient.addColorStop(0, c.gradientTop);
      gradient.addColorStop(1, c.gradientBottom);

      salesChart = new Chart(ctx, {
        type: 'line',
        data: {
          labels: monthLabels,
          datasets: [{
            label: 'Ventes (€)',
            data: monthValues,
            borderColor: c.border,
            backgroundColor: gradient,
            borderWidth: 2,
            fill: true,
            pointRadius: 3,
            pointHoverRadius: 5,
            tension: 0.35,
          }]
        },
        options: {
          responsive: true,
          plugins: { legend: { display: false }, tooltip: { callbacks: { label: (ctx) => ` ${ctx.formattedValue} €` } } },
          scales: {
            x: { grid: { display: false }, ticks: { maxRotation: 0, color: c.xTick } },
            y: { beginAtZero: true, ticks: { color: c.yTick, callback: v => v.toLocaleString('fr-FR') + ' €' }, grid: { color: c.grid } }
          }
        }
      });
    }

    function refreshChartTheme(){
      const theme = currentTheme();
      const c = chartColors(theme);
      const ctx = salesChart.ctx;
      const gradient = ctx.createLinearGradient(0, 0, 0, 200);
      gradient.addColorStop(0, c.gradientTop);
      gradient.addColorStop(1, c.gradientBottom);

      const ds = salesChart.data.datasets[0];
      ds.borderColor = c.border;
      ds.backgroundColor = gradient;
      salesChart.options.scales.x.ticks.color = c.xTick;
      salesChart.options.scales.y.ticks.color = c.yTick;
      salesChart.options.scales.y.grid.color = c.grid;
      salesChart.update();
    }

    // Init
    document.addEventListener('DOMContentLoaded', () => {
      setToggleIcon();
      createSalesChart();

      document.getElementById('themeToggle').addEventListener('click', () => {
        const next = currentTheme() === 'dark' ? 'light' : 'dark';
        root.setAttribute('data-theme', next);
        localStorage.setItem(THEME_KEY, next);
        setToggleIcon();
        refreshChartTheme();
      });
    });
  </script>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
