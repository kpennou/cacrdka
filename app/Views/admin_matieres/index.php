<?php $csrf = Csrf::token(); ?>

<h1>Matières (par métier)</h1>

<?php if (!empty($error)): ?><div class="error"><?= htmlspecialchars($error) ?></div><?php endif; ?>
<?php if (!empty($success)): ?><div class="success"><?= htmlspecialchars($success) ?></div><?php endif; ?>

<!-- Filtre -->
<form method="get" action="<?= url('/admin/matieres') ?>" style="margin:10px 0; display:flex; gap:10px; align-items:end; flex-wrap:wrap">
  <div>
    <label>Métier</label><br>
    <select name="metier_id" style="padding:8px;width:260px;">
      <option value="0">Tous</option>
      <?php foreach ($metiers as $m): ?>
        <option value="<?= (int)$m['id'] ?>" <?= ((int)$filters['metier_id']===(int)$m['id'])?'selected':'' ?>>
          <?= htmlspecialchars($m['nom']) ?>
        </option>
      <?php endforeach; ?>
    </select>
  </div>
  <button style="padding:10px 16px;">Filtrer</button>
</form>

<!-- Création -->
<div class="card" style="margin:12px 0;">
  <h3>Créer une matière</h3>
  <form method="post" action="<?= url('/admin/matieres/create') ?>" style="display:flex; gap:10px; align-items:end; flex-wrap:wrap">
    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf) ?>">
    <div>
      <label>Métier *</label><br>
      <select name="metier_id" required style="padding:8px;width:260px;">
        <option value="">-- choisir --</option>
        <?php foreach ($metiers as $m): ?>
          <option value="<?= (int)$m['id'] ?>"><?= htmlspecialchars($m['nom']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div>
      <label>Nom matière *</label><br>
      <input name="nom" required style="padding:8px;width:260px;">
    </div>
    <button style="padding:10px 16px;">Créer</button>
  </form>
</div>

<!-- Liste -->
<table>
  <thead><tr><th>Métier</th><th>Matière</th><th>Active</th><th>Action</th></tr></thead>
  <tbody>
    <?php if (empty($rows)): ?>
      <tr><td colspan="4" class="muted">Aucune matière.</td></tr>
    <?php else: ?>
      <?php foreach ($rows as $r): ?>
        <tr>
          <td><?= htmlspecialchars($r['metier_nom']) ?></td>
          <td><?= htmlspecialchars($r['nom']) ?></td>
          <td><?= ((int)$r['is_active']===1)?'Oui':'Non' ?></td>
          <td>
            <form method="post" action="<?= url('/admin/matieres/toggle') ?>" style="display:inline">
              <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf) ?>">
              <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
              <button><?= ((int)$r['is_active']===1)?'Désactiver':'Activer' ?></button>
            </form>
          </td>
        </tr>
      <?php endforeach; ?>
    <?php endif; ?>
  </tbody>
</table>
