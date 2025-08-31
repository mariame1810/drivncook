<?php
session_start();
require_once '../config/db.php';

if (!isset($_SESSION['id_franchise'])) {
  header('Location: login.php'); exit;
}
$idFr = (int)$_SESSION['id_franchise'];

// CSRF token
if (empty($_SESSION['csrf_token'])) {
  $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$fr = null;
$st = $pdo->prepare("SELECT * FROM franchises WHERE id_franchise=? LIMIT 1");
$st->execute([$idFr]);
$fr = $st->fetch(PDO::FETCH_ASSOC);

$docs = ['KBIS'=>null,'CNI'=>null];
$sd = $pdo->prepare("SELECT id, type, path, statut, uploaded_at FROM documents WHERE id_franchise=?");
$sd->execute([$idFr]);
while ($row = $sd->fetch(PDO::FETCH_ASSOC)) {
  if (isset($docs[$row['type']])) $docs[$row['type']] = $row;
}

function badgeDoc($s){
  return match($s){
    'valide' => '<span class="badge text-bg-success">Validé</span>',
    'refuse' => '<span class="badge text-bg-danger">Refusé</span>',
    default  => '<span class="badge text-bg-secondary">En attente</span>',
  };
}
?>
<!DOCTYPE html>
<html lang="fr" data-bs-theme="light">
<head>
  <meta charset="UTF-8" />
  <title>Mon profil — Driv’n Cook</title>
  <meta name="viewport" content="width=device-width, initial-scale=1" />

  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" />
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet" />

  <style>
    :root{
      --brand:#e11d48;
      --radius: 1rem;
    }
    body{ background: linear-gradient(135deg,#fafafa,#f3f4f6); }
    [data-bs-theme="dark"] body{ background: linear-gradient(135deg,#0b1220,#0f172a); }
    .container-narrow{ width:min(1100px, 92%); margin-inline:auto; }
    .card{ border-radius: var(--radius); }
    .btn-primary{ background: var(--brand); border-color: var(--brand); }
    .btn-primary:hover{ filter:brightness(.95); }
  </style>
</head>
<body>
  <nav class="navbar navbar-expand-lg bg-body-tertiary sticky-top shadow-sm">
    <div class="container-narrow">
      <a class="navbar-brand fw-bold" href="dashboard.php">🚚 Driv’n Cook</a>
      <div class="ms-auto">
        <a class="btn btn-outline-secondary btn-sm" href="dashboard.php"><i class="bi bi-speedometer2 me-1"></i> Dashboard</a>
        <a class="btn btn-outline-secondary btn-sm" href="mes_commandes.php"><i class="bi bi-bag me-1"></i> Mes commandes</a>
        <a class="btn btn-outline-secondary btn-sm" href="../logout.php"><i class="bi bi-box-arrow-right me-1"></i> Déconnexion</a>
      </div>
    </div>
  </nav>

  <main class="container-narrow my-4">
    <?php if (isset($_GET['success'])): ?>
      <div class="alert alert-success py-2 mb-3">Action effectuée avec succès.</div>
    <?php elseif (isset($_GET['error'])): ?>
      <div class="alert alert-danger py-2 mb-3">Une erreur est survenue (<?php echo htmlspecialchars($_GET['error']); ?>).</div>
    <?php endif; ?>

    <!-- Infos profil -->
    <div class="card mb-4">
      <div class="card-header d-flex align-items-center justify-content-between">
        <h5 class="mb-0"><i class="bi bi-person-circle me-2"></i>Mes informations</h5>
        <?php if (!empty($fr['docs_valides'])): ?>
          <span class="badge text-bg-success">Documents validés</span>
        <?php else: ?>
          <span class="badge text-bg-secondary">Documents non validés</span>
        <?php endif; ?>
      </div>
      <div class="card-body">
        <form action="../php/update_profil.php" method="POST" class="row g-3">
          <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
          <div class="col-md-6">
            <label class="form-label">Nom / Raison sociale</label>
            <input type="text" class="form-control" name="nom" value="<?php echo htmlspecialchars($fr['nom'] ?? ''); ?>" required>
          </div>
          <div class="col-md-6">
            <label class="form-label">Email</label>
            <input type="email" class="form-control" name="email" value="<?php echo htmlspecialchars($fr['email'] ?? ''); ?>" required>
          </div>
          <div class="col-md-6">
            <label class="form-label">Téléphone</label>
            <input type="text" class="form-control" name="telephone" value="<?php echo htmlspecialchars($fr['telephone'] ?? ''); ?>">
          </div>
          <div class="col-md-6">
            <label class="form-label">Adresse</label>
            <input type="text" class="form-control" name="adresse" value="<?php echo htmlspecialchars($fr['adresse'] ?? ''); ?>">
          </div>
          <div class="col-12 d-flex gap-2">
            <button class="btn btn-primary"><i class="bi bi-save me-1"></i> Enregistrer</button>
            <a class="btn btn-outline-secondary" href="dashboard.php">Annuler</a>
          </div>
        </form>
      </div>
    </div>

    <!-- Mes documents -->
    <div class="card">
      <div class="card-header d-flex align-items-center justify-content-between">
        <h5 class="mb-0"><i class="bi bi-file-earmark-text me-2"></i>Mes documents</h5>
        <?php if (isset($_GET['step']) && $_GET['step']==='documents'): ?>
          <span class="text-muted small">Merci d’envoyer votre KBIS et votre CNI pour valider votre compte.</span>
        <?php endif; ?>
      </div>
      <div class="card-body">
        <div class="row g-4">
          <!-- KBIS -->
          <div class="col-md-6">
            <h6 class="fw-bold mb-1">KBIS</h6>
            <div class="mb-2">
              Statut :
              <?php echo $docs['KBIS'] ? badgeDoc($docs['KBIS']['statut']) : '<span class="badge text-bg-secondary">Non transmis</span>'; ?>
            </div>
            <?php if ($docs['KBIS']): ?>
              <div class="mb-2 small text-muted">Envoyé le : <?php echo htmlspecialchars($docs['KBIS']['uploaded_at']); ?></div>
              <div class="mb-2 small text-muted">Fichier : <?php echo htmlspecialchars(basename($docs['KBIS']['path'])); ?></div>
            <?php endif; ?>
            <form action="../php/upload_document.php" method="POST" enctype="multipart/form-data" class="d-flex gap-2 align-items-center">
              <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
              <input type="hidden" name="type" value="KBIS">
              <input type="file" name="document" accept=".pdf,image/png,image/jpeg" class="form-control" required>
              <button class="btn btn-primary">Uploader</button>
            </form>
          </div>

          <!-- CNI -->
          <div class="col-md-6">
            <h6 class="fw-bold mb-1">Carte Nationale d’Identité (CNI)</h6>
            <div class="mb-2">
              Statut :
              <?php echo $docs['CNI'] ? badgeDoc($docs['CNI']['statut']) : '<span class="badge text-bg-secondary">Non transmis</span>'; ?>
            </div>
            <?php if ($docs['CNI']): ?>
              <div class="mb-2 small text-muted">Envoyé le : <?php echo htmlspecialchars($docs['CNI']['uploaded_at']); ?></div>
              <div class="mb-2 small text-muted">Fichier : <?php echo htmlspecialchars(basename($docs['CNI']['path'])); ?></div>
            <?php endif; ?>
            <form action="../php/upload_document.php" method="POST" enctype="multipart/form-data" class="d-flex gap-2 align-items-center">
              <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
              <input type="hidden" name="type" value="CNI">
              <input type="file" name="document" accept=".pdf,image/png,image/jpeg" class="form-control" required>
              <button class="btn btn-primary">Uploader</button>
            </form>
          </div>
        </div>

        <p class="mt-3 mb-0 small text-muted">
          Formats acceptés : PDF, JPG, PNG. Taille max : 8 Mo. Vos documents sont stockés de manière non publique.
        </p>
      </div>
    </div>

  </main>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
<?php
session_start();
require_once '../config/db.php';

/**
 * This profile page matches the dashboard look & adds:
 * - Avatar upload with preview (stores path in $_SESSION['avatar'])
 * - Clean, glassy card UI with theme toggle (same pattern as dashboard)
 * - Profile form (name, email, phone, password change)
 *
 * NOTES:
 * - The file is saved to ../uploads/avatars/. Make sure the directory exists and is writable.
 * - If your DB has an avatar column, you can update it where indicated.
 */

// Guard: require auth (adjust key to your app)
if (!isset($_SESSION['id_admin']) && !isset($_SESSION['id_franchise']) && !isset($_SESSION['id_user'])) {
    header("Location: ../public/login.php");
    exit;
}

// Resolve current user id + display name/email from session (fallback-safe)
$userId = $_SESSION['id_franchise'] ?? $_SESSION['id_admin'] ?? $_SESSION['id_user'];
$displayName = $_SESSION['display_name'] ?? ($_SESSION['prenom'] ?? '') . ' ' . ($_SESSION['nom'] ?? '');
$displayName = trim($displayName) ?: 'Utilisateur';
$displayEmail = $_SESSION['email'] ?? '';

// Handle avatar upload
$uploadError = '';
$uploadSuccess = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['avatar']) && is_array($_FILES['avatar'])) {
    if (!is_dir(__DIR__ . '/../uploads/avatars')) {
        @mkdir(__DIR__ . '/../uploads/avatars', 0775, true);
    }
    $f = $_FILES['avatar'];
    if ($f['error'] === UPLOAD_ERR_OK) {
        $tmp = $f['tmp_name'];
        $mime = mime_content_type($tmp) ?: '';
        $allowed = ['image/jpeg'=>'jpg','image/png'=>'png','image/webp'=>'webp','image/gif'=>'gif'];
        if (!isset($allowed[$mime])) {
            $uploadError = "Format non pris en charge. Utilisez JPG, PNG, WEBP ou GIF.";
        } else {
            $ext = $allowed[$mime];
            $safeId = preg_replace('~[^a-zA-Z0-9_-]~','_', (string)$userId);
            $fileName = $safeId . '_' . time() . '.' . $ext;
            $dest = __DIR__ . '/../uploads/avatars/' . $fileName;
            if (move_uploaded_file($tmp, $dest)) {
                // Persist path relative to this file
                $_SESSION['avatar'] = '../uploads/avatars/' . $fileName;
                $uploadSuccess = "Photo de profil mise à jour.";
                // Optional: update DB avatar column if you have one
                // $stmt = $pdo->prepare(\"UPDATE franchises SET avatar_path = ? WHERE id_franchise = ?\");
                // $stmt->execute([$_SESSION['avatar'], $_SESSION['id_franchise']]);
            } else {
                $uploadError = "Échec lors de l'enregistrement du fichier.";
            }
        }
    } elseif ($f['error'] !== UPLOAD_ERR_NO_FILE) {
        $uploadError = "Erreur de téléversement (code: {$f['error']}).";
    }

    // Optionally handle other profile fields here (name/email/phone/password)
    // Validate, then update your DB accordingly.
}

$avatar = $_SESSION['avatar'] ?? '../public/assets/avatar-placeholder.png'; // fallback image path

function e($v){ return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
?>
<!DOCTYPE html>
<html lang="fr" data-bs-theme="light">
<head>
  <meta charset="UTF-8">
  <title>Profil – Compte</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">

  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">

  <style>
    :root{
      --brand: #6366f1;
      --radius: 1rem;
      --shadow-soft: 0 12px 28px rgba(0,0,0,.08);
      --shadow-hover: 0 18px 48px rgba(0,0,0,.14);
    }
    [data-bs-theme="dark"] {
      --shadow-soft: 0 12px 28px rgba(0,0,0,.35);
      --shadow-hover: 0 18px 48px rgba(0,0,0,.5);
    }
    body{
      background: linear-gradient(180deg, color-mix(in oklab, var(--bs-body-bg) 95%, transparent), var(--bs-body-bg));
    }
    .navbar-blur {
      backdrop-filter: saturate(180%) blur(10px);
      background: color-mix(in oklab, var(--bs-body-bg) 70%, transparent);
      border-bottom: 1px solid var(--bs-border-color);
    }
    .hero {
      background: linear-gradient(135deg, var(--brand) 0%, #7c3aed 50%, #0ea5e9 100%);
      color: #fff;
      border-bottom-left-radius: 1.5rem;
      border-bottom-right-radius: 1.5rem;
    }
    .hero .lead{ opacity:.92; }
    .card-elev { border: 0; border-radius: var(--radius); box-shadow: var(--shadow-soft); }
    .card-elev:hover { box-shadow: var(--shadow-hover); }
    .btn-pill{ border-radius: 999px; }

    .avatar-wrap{
      width: 128px; height: 128px; border-radius: 50%;
      overflow: hidden; border: 3px solid rgba(255,255,255,.6);
      box-shadow: 0 10px 30px rgba(0,0,0,.15);
      background: #fff; display:grid; place-items:center;
    }
    .avatar-wrap img{ width:100%; height:100%; object-fit: cover; }
    .dropzone{
      border: 2px dashed var(--bs-border-color);
      border-radius: var(--radius);
      padding: 18px; text-align: center;
      background: color-mix(in oklab, var(--bs-body-bg) 92%, transparent);
    }
    .form-floating>label{ left: .75rem; }
  </style>
</head>
<body>

  <!-- Topbar (match dashboard) -->
  <nav class="navbar navbar-expand-lg navbar-blur sticky-top">
    <div class="container">
      <a class="navbar-brand fw-semibold d-flex align-items-center gap-2" href="dashboard.php">
        <i class="bi bi-speedometer2 text-primary"></i> Tableau de bord
      </a>
      <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#topnav">
        <span class="navbar-toggler-icon"></span>
      </button>
      <div class="collapse navbar-collapse" id="topnav">
        <ul class="navbar-nav me-auto">
          <li class="nav-item"><a class="nav-link" href="dashboard.php">Accueil</a></li>
          <li class="nav-item"><a class="nav-link" href="admin_franchises.php">Franchisés</a></li>
          <li class="nav-item"><a class="nav-link" href="admin_gestion_camions.php">Camions</a></li>
          <li class="nav-item"><a class="nav-link" href="admin_entrepots.php">Entrepôts</a></li>
          <li class="nav-item"><a class="nav-link active" href="#">Profil</a></li>
        </ul>
        <button id="themeToggle" class="btn btn-outline-secondary btn-sm btn-pill" type="button" aria-label="Basculer le thème">
          <i class="bi bi-moon-stars"></i>
        </button>
      </div>
    </div>
  </nav>

  <!-- Hero -->
  <header class="hero py-5">
    <div class="container">
      <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
        <div class="d-flex align-items-center gap-3">
          <div class="avatar-wrap">
            <img id="avatarPreview" src="<?php echo e($avatar); ?>" alt="Avatar">
          </div>
          <div>
            <h1 class="h3 mb-1"><?php echo e($displayName); ?></h1>
            <p class="lead mb-0"><?php echo e($displayEmail ?: ''); ?></p>
          </div>
        </div>
        <div class="d-flex gap-2">
          <a href="dashboard.php" class="btn btn-light btn-pill"><i class="bi bi-arrow-left me-1"></i> Retour</a>
        </div>
      </div>
    </div>
  </header>

  <main class="container py-5">
    <?php if ($uploadSuccess): ?>
      <div class="alert alert-success card-elev mb-4"><i class="bi bi-check2-circle me-2"></i><?php echo e($uploadSuccess); ?></div>
    <?php endif; ?>
    <?php if ($uploadError): ?>
      <div class="alert alert-danger card-elev mb-4"><i class="bi bi-exclamation-triangle me-2"></i><?php echo e($uploadError); ?></div>
    <?php endif; ?>

    <div class="row g-4">
      <!-- Avatar upload -->
      <div class="col-12 col-lg-5">
        <div class="card card-elev h-100">
          <div class="card-body">
            <h5 class="card-title mb-3">Photo de profil</h5>
            <form method="post" enctype="multipart/form-data" class="d-grid gap-3">
              <div class="d-flex align-items-center gap-3">
                <div class="avatar-wrap">
                  <img id="avatarPreview2" src="<?php echo e($avatar); ?>" alt="Avatar">
                </div>
                <div class="flex-grow-1">
                  <div class="dropzone small text-secondary">
                    Glissez-déposez une image ici ou <span class="text-primary">cliquez</span> pour choisir un fichier.
                  </div>
                  <input class="form-control mt-2" type="file" id="avatar" name="avatar" accept="image/*">
                  <small class="text-secondary d-block mt-1">Formats : JPG, PNG, WEBP, GIF. Taille conseillée 512×512.</small>
                </div>
              </div>
              <div class="text-end">
                <button type="submit" class="btn btn-primary btn-pill">
                  <i class="bi bi-upload me-1"></i> Mettre à jour la photo
                </button>
              </div>
            </form>
          </div>
        </div>
      </div>

      <!-- Profile details -->
      <div class="col-12 col-lg-7">
        <div class="card card-elev h-100">
          <div class="card-body">
            <h5 class="card-title mb-3">Informations du compte</h5>
            <form action="../php/update_profile.php" method="post" class="row g-3 needs-validation" novalidate>
              <div class="col-md-6 form-floating">
                <input type="text" class="form-control" id="prenom" name="prenom" placeholder="Prénom" value="<?php echo e($_POST['prenom'] ?? ($_SESSION['prenom'] ?? '')); ?>" required>
                <label for="prenom">Prénom</label>
                <div class="invalid-feedback">Veuillez renseigner votre prénom.</div>
              </div>
              <div class="col-md-6 form-floating">
                <input type="text" class="form-control" id="nom" name="nom" placeholder="Nom" value="<?php echo e($_POST['nom'] ?? ($_SESSION['nom'] ?? '')); ?>" required>
                <label for="nom">Nom</label>
                <div class="invalid-feedback">Veuillez renseigner votre nom.</div>
              </div>
              <div class="col-md-8 form-floating">
                <input type="email" class="form-control" id="email" name="email" placeholder="vous@exemple.com" value="<?php echo e($_POST['email'] ?? ($displayEmail)); ?>" required>
                <label for="email">Adresse email</label>
                <div class="invalid-feedback">Adresse email invalide.</div>
              </div>
              <div class="col-md-4 form-floating">
                <input type="tel" class="form-control" id="telephone" name="telephone" placeholder="Téléphone" value="<?php echo e($_POST['telephone'] ?? ($_SESSION['telephone'] ?? '')); ?>">
                <label for="telephone">Téléphone</label>
              </div>

              <div class="col-12"><hr class="my-2"></div>

              <div class="col-md-6 form-floating">
                <input type="password" class="form-control" id="new_password" name="new_password" placeholder="Nouveau mot de passe" minlength="8">
                <label for="new_password">Nouveau mot de passe</label>
              </div>
              <div class="col-md-6 form-floating">
                <input type="password" class="form-control" id="confirm_password" name="confirm_password" placeholder="Confirmer">
                <label for="confirm_password">Confirmer le mot de passe</label>
              </div>

              <div class="col-12 d-flex justify-content-end gap-2">
                <a href="dashboard.php" class="btn btn-outline-secondary btn-pill">Annuler</a>
                <button type="submit" class="btn btn-primary btn-pill"><i class="bi bi-save me-1"></i> Enregistrer</button>
              </div>
            </form>
          </div>
        </div>
      </div>
    </div>
  </main>

  <footer class="py-4 bg-body">
    <div class="container d-flex flex-column flex-md-row align-items-center justify-content-between gap-2">
      <span class="text-secondary small">© <?php echo date('Y'); ?> DrivnCook. Tous droits réservés.</span>
      <div class="d-flex align-items-center gap-3 small">
        <a class="link-secondary text-decoration-none" href="dashboard.php"><i class="bi bi-speedometer2 me-1"></i>Dashboard</a>
        <a class="link-secondary text-decoration-none" href="#">Aide</a>
      </div>
    </div>
  </footer>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <script>
    // Theme toggle
    const html = document.documentElement;
    const key = 'pref-theme-admin';
    const btn = document.getElementById('themeToggle');
    const saved = localStorage.getItem(key);
    if (saved) html.setAttribute('data-bs-theme', saved);
    else if (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches) {
      html.setAttribute('data-bs-theme', 'dark');
    }
    function setIcon(){
      btn.innerHTML = (html.getAttribute('data-bs-theme') === 'dark')
        ? '<i class="bi bi-sun"></i>' : '<i class="bi bi-moon-stars"></i>';
    }
    setIcon();
    btn.addEventListener('click', () => {
      const next = html.getAttribute('data-bs-theme') === 'dark' ? 'light' : 'dark';
      html.setAttribute('data-bs-theme', next);
      localStorage.setItem(key, next);
      setIcon();
    });

    // Bootstrap validation
    (() => {
      const form = document.querySelector('.needs-validation');
      if (!form) return;
      form.addEventListener('submit', (event) => {
        if (!form.checkValidity()) {
          event.preventDefault();
          event.stopPropagation();
        }
        form.classList.add('was-validated');
      }, false);
    })();

    // Avatar live preview
    const input = document.getElementById('avatar');
    const prev1 = document.getElementById('avatarPreview');
    const prev2 = document.getElementById('avatarPreview2');
    if (input) {
      input.addEventListener('change', (e) => {
        const file = e.target.files?.[0];
        if (!file) return;
        const url = URL.createObjectURL(file);
        if (prev1) prev1.src = url;
        if (prev2) prev2.src = url;
      });
    }
  </script>
</body>
</html>
