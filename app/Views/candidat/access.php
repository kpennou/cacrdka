<?php $csrf = Csrf::token(); ?>
<h1>Accès candidat</h1>

<?php if (!empty($error)): ?><div class="error"><?= htmlspecialchars($error) ?></div><?php endif; ?>
<?php if (!empty($success)): ?><div class="success"><?= htmlspecialchars($success) ?></div><?php endif; ?>

<form method="post" action="/candidat/acces">
  <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf) ?>">
  <div style="margin:10px 0;">
    <label>Téléphone *</label><br>
    <input name="telephone" required style="padding:8px;width:280px;">
  </div>
  <div style="margin:10px 0;">
    <label>Nom (de famille) *</label><br>
    <input name="nom" required style="padding:8px;width:280px;">
  </div>
  <button style="padding:10px 16px;">Accéder</button>
</form>
