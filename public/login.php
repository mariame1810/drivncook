<?php
session_start();

// Générer un token CSRF si pas déjà présent
if (empty($_SESSION['csrf_token'])) {
  $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
?>
<!DOCTYPE html>
<html lang="fr" data-bs-theme="light">
<head>
  <meta charset="UTF-8" />
  <title>Connexion Franchisé – Driv’n Cook</title>
  <meta name="viewport" content="width=device-width, initial-scale=1" />

  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" />
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet" />

  <style>
    :root{
      --brand:#e11d48;
      --card-radius: 1rem;
      --glass-bg: rgba(255,255,255,.7);
      --glass-border: rgba(255,255,255,.6);
      --shadow-soft: 0 10px 30px rgba(0,0,0,.12);
      --shadow-strong: 0 20px 60px rgba(0,0,0,.18);
    }
    [data-bs-theme="dark"]{
      --glass-bg: rgba(17,24,39,.6);
      --glass-border: rgba(255,255,255,.08);
      --shadow-soft: 0 10px 30px rgba(0,0,0,.55);
      --shadow-strong: 0 20px 60px rgba(0,0,0,.7);
    }

    body{
      min-height:100vh;
      margin:0;
      display:grid;
      place-items:center;
      background:
        radial-gradient(1200px 600px at -10% -10%, rgba(225,29,72,.18), transparent 60%),
        radial-gradient(1000px 600px at 110% 110%, rgba(59,130,246,.18), transparent 55%),
        linear-gradient(135deg, #fafafa, #f3f4f6);
    }
    [data-bs-theme="dark"] body{
      background:
        radial-gradient(1200px 600px at -10% -10%, rgba(225,29,72,.18), transparent 60%),
        radial-gradient(1000px 600px at 110% 110%, rgba(59,130,246,.18), transparent 55%),
        linear-gradient(135deg, #0b1220, #0f172a);
    }

    .topbar{
      position:absolute; inset: 0 0 auto 0; height:64px;
      display:flex; align-items:center; justify-content:center;
      pointer-events:none;
    }
    .topbar-inner{
      width: min(1200px, 92%);
      display:flex; align-items:center; justify-content:space-between;
      pointer-events:auto;
    }
    .brand{
      display:flex; align-items:center; gap:.6rem;
      font-weight:800; letter-spacing:.2px;
    }
    .brand .logo-dot{
      width:38px; height:38px; border-radius:12px;
      display:grid; place-items:center;
      background: color-mix(in oklab, var(--brand) 92%, transparent);
      color:#fff; box-shadow: 0 10px 22px rgba(225,29,72,.3);
    }

    .auth-card{
      width:min(920px, 92%);
      border-radius: var(--card-radius);
      backdrop-filter: blur(10px);
      background: var(--glass-bg);
      border:1px solid var(--glass-border);
      box-shadow: var(--shadow-soft);
      overflow:hidden;
    }
    .auth-sides{
      display:grid;
      grid-template-columns: 1.1fr .9fr;
    }
    @media (max-width: 992px){
      .auth-sides{ grid-template-columns: 1fr; }
    }

    .hero-pane{
      padding: clamp(20px, 6vw, 48px);
      background:
        radial-gradient(500px 300px at 20% 20%, rgba(225,29,72,.12), transparent 60%),
        radial-gradient(700px 380px at 80% 80%, rgba(59,130,246,.10), transparent 55%);
      display:flex; flex-direction:column; justify-content:center;
    }
    .hero-pane h1{
      font-weight:800;
      line-height:1.15;
    }
    .hero-pane .lead{
      color: var(--bs-secondary-color);
    }

    .form-pane{
      padding: clamp(20px, 5vw, 40px);
      background: rgba(255,255,255,.5);
    }
    [data-bs-theme="dark"] .form-pane{
      background: rgba(15,23,42,.45);
    }
    .card-title{
      font-weight:700;
    }
    .btn-pill{ border-radius: 999px; }
    .input-group-text{
      border-radius: 999px 0 0 999px !important;
    }
    .form-control{
      border-radius: 0 999px 999px 0 !important;
    }
    .form-control, .form-select{
      box-shadow:none !important;
    }
    .btn-primary{
      background: var(--brand);
      border-color: var(--brand);
    }
    .btn-primary:hover{ filter: brightness(.95); }

    .footnote{ color: var(--bs-secondary-color); }
  </style>
</head>
<body>

  <div class="topbar">
    <div class="topbar-inner">
      <div class="brand">
        <div class="logo-dot">🚚</div>
        <div>Driv’n Cook</div>
      </div>
      <button id="themeToggle" class="btn btn-outline-secondary btn-sm btn-pill" type="button" aria-label="Basculer le thème">
        <i class="bi bi-moon-stars"></i>
      </button>
    </div>
  </div>

  <div class="auth-card">
    <div class="auth-sides">
      <div class="hero-pane">
        <span class="badge text-bg-light border border-1 mb-3" style="border-radius:999px;">
          Portail franchisé
        </span>
        <h1 class="mb-3">Connectez-vous et gérez votre activité</h1>
        <p class="lead mb-4">
          Accédez à vos commandes, vos ventes et votre camion. Le tout, au même endroit — en clair ou en mode sombre 🌙.
        </p>
        <ul class="mb-0 text-secondary ps-3">
          <li>Tableau de bord en temps réel</li>
          <li>Suivi des commandes et export PDF</li>
          <li>Carte des entrepôts et camions libres</li>
        </ul>
      </div>

      <div class="form-pane">
        <h3 class="card-title mb-3">Connexion franchisé</h3>

        <form action="../php/login.php" method="POST" class="needs-validation" novalidate>
          <!-- ⚡ Ajout CSRF -->
          <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">

          <div class="mb-3">
            <label for="email" class="form-label fw-semibold">Adresse email</label>
            <div class="input-group">
              <span class="input-group-text"><i class="bi bi-envelope"></i></span>
              <input type="email" class="form-control" id="email" name="email" placeholder="vous@exemple.com" required>
              <div class="invalid-feedback">Veuillez saisir une adresse email valide.</div>
            </div>
          </div>

          <div class="mb-2">
            <label for="mot_de_passe" class="form-label fw-semibold">Mot de passe</label>
            <div class="input-group">
              <span class="input-group-text"><i class="bi bi-lock"></i></span>
              <input type="password" class="form-control" id="mot_de_passe" name="mot_de_passe" placeholder="••••••••" required minlength="6">
              <button class="btn btn-outline-secondary" type="button" id="togglePwd" tabindex="-1" aria-label="Afficher le mot de passe">
                <i class="bi bi-eye"></i>
              </button>
              <div class="invalid-feedback">Votre mot de passe est requis (6 caractères minimum).</div>
            </div>
          </div>

          <div class="d-flex align-items-center justify-content-between mb-4">
            <div class="form-check">
              <input class="form-check-input" type="checkbox" id="remember">
              <label class="form-check-label" for="remember">Se souvenir de moi</label>
            </div>
            <a href="#" class="link-secondary small text-decoration-none">Mot de passe oublié ?</a>
          </div>

          <div class="d-grid gap-2">
            <button type="submit" class="btn btn-primary btn-lg btn-pill">
              <i class="bi bi-box-arrow-in-right me-1"></i> Se connecter
            </button>
            <a href="../public/register.html" class="btn btn-outline-secondary btn-pill">
              <i class="bi bi-person-plus me-1"></i> Créer un compte
            </a>
          </div>

          <p class="footnote small mt-3 mb-0">
            En vous connectant, vous acceptez nos <a href="#" class="link-secondary text-decoration-none">conditions</a> et notre <a href="#" class="link-secondary text-decoration-none">politique de confidentialité</a>.
          </p>
        </form>
      </div>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <script>
    const html = document.documentElement;
    const themeKey = 'pref-theme-login';
    const themeBtn = document.getElementById('themeToggle');
    const saved = localStorage.getItem(themeKey);
    if (saved) {
      html.setAttribute('data-bs-theme', saved);
    } else if (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches) {
      html.setAttribute('data-bs-theme', 'dark');
    }
    function setThemeIcon(){
      themeBtn.innerHTML = (html.getAttribute('data-bs-theme') === 'dark')
        ? '<i class="bi bi-sun"></i>' : '<i class="bi bi-moon-stars"></i>';
    }
    setThemeIcon();
    themeBtn.addEventListener('click', () => {
      const next = html.getAttribute('data-bs-theme') === 'dark' ? 'light' : 'dark';
      html.setAttribute('data-bs-theme', next);
      localStorage.setItem(themeKey, next);
      setThemeIcon();
    });

    const togglePwd = document.getElementById('togglePwd');
    const inputPwd = document.getElementById('mot_de_passe');
    togglePwd.addEventListener('click', () => {
      const isPwd = inputPwd.getAttribute('type') === 'password';
      inputPwd.setAttribute('type', isPwd ? 'text' : 'password');
      togglePwd.innerHTML = isPwd ? '<i class="bi bi-eye-slash"></i>' : '<i class="bi bi-eye"></i>';
    });

    (() => {
      const form = document.querySelector('.needs-validation');
      form.addEventListener('submit', (event) => {
        if (!form.checkValidity()) {
          event.preventDefault();
          event.stopPropagation();
        }
        form.classList.add('was-validated');
      }, false);
    })();
  </script>
</body>
</html>
