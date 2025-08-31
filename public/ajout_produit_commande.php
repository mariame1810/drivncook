<?php
session_start();
require_once '../config/db.php';
require_once '../php/middleware_onboarding.php';

if (!isset($_SESSION['id_franchise'])) {
    header("Location: login.html");
    exit;
}

$id_franchise = (int) $_SESSION['id_franchise'];

/* -------------------------------------------------
   Chemins sûrs pour les <form action="...">
   ------------------------------------------------- */
$BASE_PATH = rtrim(dirname($_SERVER['PHP_SELF']), '/'); // ex: /drivncook/public
$ABS = function(string $file) use ($BASE_PATH) { return $BASE_PATH . '/' . ltrim($file, '/'); };

/* -------------------------------------------------
   Source of truth : droit d'entrée payé (BDD)
   ------------------------------------------------- */
$stPaid = $pdo->prepare("SELECT paid_entry_fee FROM franchises WHERE id_franchise = ?");
$stPaid->execute([$id_franchise]);
$paidEntryFee = (int) ($stPaid->fetchColumn() ?? 0);
$_SESSION['paid_entry_fee'] = $paidEntryFee; // resync session
$canPay = ($paidEntryFee === 1);

/* -------------------------------------------------
   Données page
   ------------------------------------------------- */
$cmdStmt = $pdo->prepare("SELECT id_commande, date_commande FROM commandes WHERE id_franchise = ? ORDER BY date_commande DESC");
$cmdStmt->execute([$id_franchise]);
$commandes = $cmdStmt->fetchAll(PDO::FETCH_ASSOC);

$produits = $pdo->query("SELECT id_produit, nom, prix_unitaire, image_url FROM produits ORDER BY nom ASC")->fetchAll(PDO::FETCH_ASSOC);

/* -------------------------------------------------
   Helpers
   ------------------------------------------------- */
function e($v){ return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
function formatDateFr($dateStr){ $ts = strtotime($dateStr); return $ts ? date('d/m/Y H:i', $ts) : e($dateStr); }

/* -------------------------------------------------
   Panier
   ------------------------------------------------- */
$cart = $_SESSION['cart'] ?? ['commande_id'=>null,'items'=>[],'currency'=>'EUR'];
$cartCount = array_sum(array_column($cart['items'], 'qty')) ?: 0;
$cartTotal = 0.0;
foreach ($cart['items'] as $it) { $cartTotal += (float)$it['price'] * (int)$it['qty']; }
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Ajouter un produit – Driv’n Cook</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
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

    .thumb{ width:80px; height:80px; object-fit:cover; border-radius:12px; border:1px solid #eef0f4; background:#fff; }
    .mini{ width:60px; height:60px; object-fit:cover; border-radius:10px; border:1px solid #eef0f4; background:#fff; }
    .cart-box{ position:sticky; top:24px; }
    .price-tag{ font-weight:700; color:#0f172a; }
    .muted{ color:#64748b; }
    .divider{ border-top:1px dashed #e5e7eb; margin: .75rem 0; }
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
      <a class="nav-link" href="<?= $ABS('dashboard.php') ?>"><i class="bi bi-house-door me-2"></i>Dashboard</a>
      <a class="nav-link" href="<?= $ABS('mes_commandes.php') ?>"><i class="bi bi-box-seam me-2"></i>Mes commandes</a>
      <a class="nav-link active" href="<?= $ABS('ajout_produit_commande.php') ?>"><i class="bi bi-plus-circle me-2"></i>Ajouter un produit</a>
      <a class="nav-link" href="<?= $ABS('profil.php') ?>"><i class="bi bi-person-circle me-2"></i>Modifier mon profil</a>
      <div class="mt-auto"></div>
      <a class="nav-link logout" href="<?= $ABS('logout.php') ?>"><i class="bi bi-box-arrow-right me-2"></i>Se déconnecter</a>
    </nav>
  </aside>

  <!-- Main -->
  <main class="page flex-grow-1">

    <div class="d-flex align-items-center justify-content-between gap-2 mb-4 hero">
      <div>
        <h3 class="mb-1">➕ Ajouter un produit à une commande</h3>
        <div class="text-muted">Sélectionnez la commande, choisissez le produit et ajoutez-le au panier, puis payez.</div>
      </div>
      <div class="d-flex gap-2">
        <a href="<?= $ABS('mes_commandes.php') ?>" class="btn btn-outline-secondary"><i class="bi bi-box-seam"></i> Voir mes commandes</a>
        <a href="#cart" class="btn btn-outline-dark position-relative">
          <i class="bi bi-basket"></i> Panier
          <?php if ($cartCount): ?><span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger"><?= (int)$cartCount ?></span><?php endif; ?>
        </a>
      </div>
    </div>

    <?php foreach (['flash_error'=>'danger','flash_success'=>'success','flash_info'=>'info'] as $k=>$cls):
      if(!empty($_SESSION[$k])): ?>
      <div class="alert alert-<?= $cls ?>"><?= e($_SESSION[$k]); unset($_SESSION[$k]); ?></div>
    <?php endif; endforeach; ?>
    <?php if(!$canPay): ?>
      <div class="alert alert-warning"><i class="bi bi-exclamation-triangle"></i> Vous pouvez constituer votre panier, mais le paiement est bloqué tant que le droit d’entrée n’est pas réglé.</div>
    <?php endif; ?>

    <div class="row g-4">
      <!-- Formulaire unique -->
      <div class="col-12 col-lg-7">
        <div class="card-elev p-3 p-md-4">
          <form action="<?= $ABS('panier_add.php') ?>" method="POST" class="row g-3 needs-validation" novalidate>
            <input type="hidden" name="redirect" value="ajout_produit_commande.php">

            <div class="col-12 col-md-6">
              <label for="id_commande" class="form-label required">Commande</label>
              <div class="input-icon">
                <i class="bi bi-receipt-cutoff"></i>
                <select id="id_commande" name="id_commande" class="form-select" required>
                  <option value="">— Sélectionner une commande —</option>
                  <?php foreach ($commandes as $c): ?>
                    <option value="<?= (int)$c['id_commande'] ?>"
                      <?= ($cart['commande_id']??null)==$c['id_commande']?'selected':''; ?>
                    >#<?= (int)$c['id_commande'] ?> – <?= e(formatDateFr($c['date_commande'])) ?></option>
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
                    <option
                      value="<?= (int)$p['id_produit'] ?>"
                      data-prix="<?= e(number_format((float)$p['prix_unitaire'], 2, ',', ' ')) ?>"
                      data-image="<?= e($p['image_url'] ?: '') ?>"
                    ><?= e($p['nom']) ?></option>
                  <?php endforeach; ?>
                </select>
                <div class="invalid-feedback">Veuillez sélectionner un produit.</div>
              </div>
            </div>

            <div class="col-12 col-md-6">
              <label for="quantite" class="form-label required">Quantité</label>
              <div class="input-icon">
                <i class="bi bi-123"></i>
                <input id="quantite" type="number" name="qty" class="form-control" min="1" step="1" value="1" required>
                <div class="invalid-feedback">La quantité doit être au moins 1.</div>
              </div>
            </div>

            <div class="col-12 col-md-6 d-flex align-items-center gap-3">
              <img id="prodThumb" src="" alt="Aperçu" class="thumb d-none">
              <div>
                <div class="muted">Prix unitaire</div>
                <div class="price-tag fs-5" id="prodPrice">—</div>
              </div>
            </div>

            <div class="col-12 d-flex justify-content-end gap-2">
              <a href="<?= $ABS('dashboard.php') ?>" class="btn btn-outline-secondary"><i class="bi bi-arrow-left"></i> Annuler</a>
              <button type="submit" class="btn btn-primary">
                <i class="bi bi-basket2"></i> Ajouter au panier
              </button>
            </div>
          </form>
        </div>

        <div class="mt-4 small text-muted">Astuce : tapez la première lettre dans les listes pour aller plus vite. (Ctrl/⌘ + K pour focaliser le sélecteur).</div>
      </div>

      <!-- Panier -->
      <div class="col-12 col-lg-5" id="cart">
        <div class="card-elev p-3 p-md-4 cart-box">
          <div class="d-flex align-items-center justify-content-between">
            <h5 class="m-0"><i class="bi bi-basket"></i> Mon panier</h5>
            <?php if ($cartCount): ?>
              <form action="<?= $ABS('panier_clear.php') ?>" method="post" onsubmit="return confirm('Vider le panier ?');">
                <input type="hidden" name="redirect" value="ajout_produit_commande.php">
                <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i> Vider</button>
              </form>
            <?php endif; ?>
          </div>

          <?php if (!$cartCount): ?>
            <div class="text-center text-muted py-4">
              <div class="mb-2">Votre panier est vide.</div>
              <small>Ajoutez des produits via le formulaire ci-contre.</small>
            </div>
          <?php else: ?>
            <div class="mt-2 mb-2">
              <span class="muted">Commande liée :</span>
              <span class="fw-semibold">#<?= (int)$cart['commande_id'] ?></span>
            </div>
            <div class="divider"></div>

            <div class="mt-2">
              <?php foreach ($cart['items'] as $pid => $it): ?>
                <div class="d-flex align-items-center gap-3 py-2">
                  <img src="<?= e($it['image'] ?: '') ?>" class="mini" alt="">
                  <div class="flex-grow-1">
                    <div class="fw-semibold"><?= e($it['name']) ?></div>
                    <div class="muted">Prix unitaire : <?= number_format((float)$it['price'], 2, ',', ' ') ?> €</div>
                    <div class="d-flex align-items-center gap-2 mt-1">
                      <form action="<?= $ABS('panier_update.php') ?>" method="post" class="d-flex align-items-center gap-2">
                        <input type="hidden" name="redirect" value="ajout_produit_commande.php">
                        <input type="hidden" name="id_produit" value="<?= (int)$pid ?>">
                        <input type="number" name="qty" value="<?= (int)$it['qty'] ?>" min="1" max="99" class="form-control form-control-sm" style="width:90px;">
                        <button class="btn btn-sm btn-outline-primary">Mettre à jour</button>
                      </form>
                      <form action="<?= $ABS('panier_remove.php') ?>" method="post" onsubmit="return confirm('Retirer ce produit ?');">
                        <input type="hidden" name="redirect" value="ajout_produit_commande.php">
                        <input type="hidden" name="id_produit" value="<?= (int)$pid ?>">
                        <button class="btn btn-sm btn-outline-secondary"><i class="bi bi-x"></i></button>
                      </form>
                    </div>
                  </div>
                  <div class="text-end">
                    <div class="price-tag">
                      <?= number_format((float)$it['price'] * (int)$it['qty'], 2, ',', ' ') ?> €
                    </div>
                  </div>
                </div>
                <div class="divider"></div>
              <?php endforeach; ?>
              <div class="d-flex align-items-center justify-content-between mt-2">
                <div class="fw-semibold">Total</div>
                <div class="fs-5 fw-bold"><?= number_format($cartTotal, 2, ',', ' ') ?> €</div>
              </div>

              <form action="<?= $ABS('create_checkout_session.php') ?>" method="post" class="mt-3">
                <button type="submit" class="btn btn-success w-100" <?= $canPay ? '' : 'disabled' ?>>
                  <i class="bi bi-credit-card"></i> Passer au paiement
                </button>
                <?php if (!$canPay): ?>
                  <div class="form-text text-danger">Le paiement est bloqué tant que le droit d’entrée n’est pas réglé.</div>
                <?php endif; ?>
              </form>
            </div>
          <?php endif; ?>
        </div>
      </div>
    </div>

  </main>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
  // Validation Bootstrap
  (() => {
    const forms = document.querySelectorAll('.needs-validation');
    Array.from(forms).forEach(form => {
      form.addEventListener('submit', event => {
        if (!form.checkValidity()) { event.preventDefault(); event.stopPropagation(); }
        form.classList.add('was-validated');
      }, false);
    });
  })();

  // Preview prix + image
  const select = document.getElementById('id_produit');
  const priceEl = document.getElementById('prodPrice');
  const imgEl = document.getElementById('prodThumb');
  function updatePreview(){
    const opt = select.options[select.selectedIndex];
    if (!opt || !opt.dataset) { priceEl.textContent = '—'; imgEl.classList.add('d-none'); return; }
    const prix = opt.dataset.prix || null;
    const img  = opt.dataset.image || '';
    priceEl.textContent = prix ? (prix + ' €') : '—';
    if (img) { imgEl.src = img; imgEl.classList.remove('d-none'); } else { imgEl.classList.add('d-none'); }
  }
  select.addEventListener('change', updatePreview); updatePreview();

  // Raccourci focus (Ctrl/Cmd + K)
  window.addEventListener('keydown', (e) => {
    if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === 'k') {
      e.preventDefault(); document.getElementById('id_produit').focus();
    }
  });
</script>
</body>
</html>
