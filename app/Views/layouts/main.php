<?php /** @var string $viewFile */ ?>
<!doctype html>
<html lang="fr">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>CACRDKA V1</title>
  <style>
    body{font-family:Arial,sans-serif;margin:20px;}
    nav{display:flex;gap:12px;align-items:center;margin-bottom:16px;flex-wrap:wrap}
    .card{border:1px solid #ddd;border-radius:10px;padding:12px}
    .cards{display:grid;grid-template-columns:repeat(4,1fr);gap:12px}
    table{width:100%;border-collapse:collapse;margin-top:10px}
    th,td{border:1px solid #ddd;padding:8px;font-size:14px}
    th{background:#f5f5f5;text-align:left}
    .error{background:#ffe2e2;border:1px solid #ffb1b1;padding:10px;border-radius:8px;margin:10px 0}
    .success{background:#e8ffe6;border:1px solid #a8e7a1;padding:10px;border-radius:8px;margin:10px 0}
    .muted{color:#666}
    .badge{display:inline-block;padding:2px 8px;border:1px solid #ddd;border-radius:999px;font-size:12px}
  </style>
</head>
<body>
<nav>
  <strong>CACRDKA</strong>
  <a href="<?= url('/preinscription') ?>">Préinscription</a>
  <a href="<?= url('/candidat/acces') ?>">Accès candidat</a>

  <a href="<?= url('/formateur/preinscription') ?>">Préinscription formateur</a>
  <a href="<?= url('/formateur/acces') ?>">Accès formateur</a>


  <span class="muted">|</span>
  <?php if (Auth::check()): ?>

    <a href="<?= url('/directeur') ?>">Dashboard</a>


    <a href="<?= url('/admin/vivier-apprenants') ?>">Vivier (Admin)</a>

    <a href="<?= url('/admin/vivier-formateurs') ?>">Vivier formateurs</a>
    
    <a href="<?= url('/admin/formateurs') ?>">Équipe formateurs</a>
    
    <a href="<?= url('/admin/matieres') ?>">Matières</a>
    <a href="<?= url('/admin/affectations') ?>">Affectations</a>
    
    <a href="<?= url('/admin/evaluations') ?>">Évaluations</a>

    <a href="<?= url('/admin/finance/cohortes') ?>">Finance: Tarifs</a>
    <a href="<?= url('/admin/finance/paiements') ?>">Finance: Paiements</a>

    <a href="<?= url('/admin/finance/journal') ?>">Finance: Journal de caisse</a>

    <a href="<?= url('/logout') ?>">Déconnexion</a>

    <span class="muted">— <?= htmlspecialchars((Auth::user()['nom'] ?? '').' '.(Auth::user()['prenoms'] ?? '')) ?> (<?= htmlspecialchars(Auth::user()['role_code'] ?? '') ?>)</span>
  <?php else: ?>

    <a href="<?= url('/login') ?>">Connexion</a>

  <?php endif; ?>
</nav>

<?php require $viewFile; ?>
</body>
</html>
