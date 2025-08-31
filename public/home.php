<?php
session_start();
?>
<!DOCTYPE html>
<html lang="fr" data-bs-theme="light">
<head>
  <meta charset="UTF-8">
  <title>Accueil - DrivnCook</title>
  <meta name="viewport" content="width=device-width, initial-scale=1" />

  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">

  <style>
    :root{
      --brand: #e11d48;
      --radius: 1rem;
      --shadow-soft: 0 12px 28px rgba(0,0,0,.08);
      --shadow-hover: 0 18px 48px rgba(0,0,0,.14);
    }
    [data-bs-theme="dark"] {
      --shadow-soft: 0 12px 28px rgba(0,0,0,.35);
      --shadow-hover: 0 18px 48px rgba(0,0,0,.5);
    }
    body {
      min-height: 100vh;
      display: grid;
      place-items: center;
      background: linear-gradient(135deg, var(--brand) 0%, #7c3aed 50%, #0ea5e9 100%);
      padding: 24px;
    }
    .card-elev {
      border: 0; border-radius: var(--radius);
      box-shadow: var(--shadow-soft);
      transition: transform .2s ease, box-shadow .2s ease;
    }
    .card-elev:hover {
      transform: translateY(-4px);
      box-shadow: var(--shadow-hover);
    }
    .btn-pill { border-radius: 999px; }
  </style>
</head>
<body>

  <div class="container" style="max-width: 640px;">
    <div class="text-end mb-3">
      <button id="themeToggle" class="btn btn-outline-light btn-sm btn-pill" type="button" aria-label="Basculer le thème">
        <i class="bi bi-moon-stars"></i>
      </button>
    </div>

    <div class="card card-elev bg-body p-4 p-md-5 text-center">
      <div class="mb-3 fs-2">🚚</div>
      <h1 class="mb-2">Bienvenue sur <span class="text-danger">DrivnCook</span></h1>
      <p class="text-secondary mb-4">Choisissez une option pour continuer.</p>

      <div class="d-grid gap-3">
        <!-- ✅ Corrigé : liens vers register.html et login.html -->
        <a href="register.html" class="btn btn-primary btn-lg btn-pill">
          <i class="bi bi-person-plus me-1"></i> Inscription
        </a>
        <a href="login.html" class="btn btn-outline-secondary btn-lg btn-pill">
          <i class="bi bi-box-arrow-in-right me-1"></i> Connexion
        </a>
      </div>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <script>
    const html = document.documentElement;
    const key = 'pref-theme-home';
    const btn = document.getElementById('themeToggle');
    const saved = localStorage.getItem(key);
    if (saved) {
      html.setAttribute('data-bs-theme', saved);
    } else if (window.matchMedia('(prefers-color-scheme: dark)').matches) {
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
  </script>
</body>
</html>
