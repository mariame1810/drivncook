<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <title>Connexion Admin - Driv'n Cook</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light d-flex justify-content-center align-items-center vh-100">

  <div class="card p-4 shadow" style="width: 400px;">
    <h3 class="text-center mb-3">🔐 Espace Admin</h3>

    <?php if (isset($_GET['error'])): ?>
      <div class="alert alert-danger"><?= htmlspecialchars($_GET['error']) ?></div>
    <?php endif; ?>

    <form action="../php/admin_check_login.php" method="POST">
      <div class="mb-3">
        <label for="email" class="form-label">Email :</label>
        <input type="email" name="email" class="form-control" required>
      </div>

      <div class="mb-3">
        <label for="mot_de_passe" class="form-label">Mot de passe :</label>
        <input type="password" name="mot_de_passe" class="form-control" required>
      </div>

      <div class="d-grid">
        <button type="submit" class="btn btn-dark">Connexion</button>
      </div>
    </form>
  </div>

</body>
</html>
