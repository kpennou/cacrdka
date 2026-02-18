<?php $csrf = Csrf::token(); ?>
<h1>Connexion</h1>

<?php if (!empty($error)): ?>
  <div class="error"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<form method="post" action="/login">
  <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf) ?>">
  <div style="margin:10px 0;">
    <label>Username</label><br>
    <input name="username" style="padding:8px;width:280px;">
  </div>
  <div style="margin:10px 0;">
    <label>Mot de passe</label><br>
    <input type="password" name="password" style="padding:8px;width:280px;">
  </div>
  <button style="padding:10px 16px;">Se connecter</button>
</form>

<p class="muted">Après import SQL, crée le compte directeur via <code>/install/create_directeur.php</code>.</p>
