<?php $csrf = Csrf::token(); ?>

<h1>Équipe formateurs</h1>

<?php if (!empty($error)): ?><div class="error"><?= htmlspecialchars($error) ?></div><?php endif; ?>
<?php if (!empty($success)): ?><div class="success"><?= htmlspecialchars($success) ?></div><?php endif; ?>

<form method="get" action="<?= url('/admin/formateurs') ?>" style="margin:10px 0; display:flex; gap:10px; align-items:end; flex-wrap:wrap">
  <div>
    <label>Métier</label><br>
    <select name="metier_id" style="padding:8px; width:260px;">
      <option value="0">Tous</option>
      <?php foreach ($metiers as $m): ?>
        <option value="<?= (int)$m['id'] ?>" <?= ((int)$filters['metier_id'] === (int)$m['id']) ? 'selected' : '' ?>>
          <?= htmlspecialchars($m['nom']) ?>
        </option>
      <?php endforeach; ?>
    </select>
  </div>

  <div>
    <label>Statut</label><br>
    <select name="statut" style="padding:8px; width:160px;">
      <option value="" <?= ($filters['statut'] === '') ? 'selected' : '' ?>>Tous</option>
      <option value="ACTIF" <?= ($filters['statut'] === 'ACTIF') ? 'selected' : '' ?>>ACTIF</option>
      <option value="INACTIF" <?= ($filters['statut'] === 'INACTIF') ? 'selected' : '' ?>>INACTIF</option>
    </select>
  </div>

  <button style="padding:10px 16px;">Filtrer</button>
</form>

<table>
  <thead>
    <tr>
      <th>Matricule</th>
      <th>Nom</th>
      <th>Téléphone</th>
      <th>Email</th>
      <th>Métier</th>
      <th>Spécialités</th>
      <th>Statut</th>
      <th>Action</th>
    </tr>
  </thead>
  <tbody>
    <?php if (empty($rows)): ?>
      <tr><td colspan="8" class="muted">Aucun formateur.</td></tr>
    <?php else: ?>
      <?php foreach ($rows as $r): ?>
        <tr>
          <td><?= htmlspecialchars($r['matricule']) ?></td>
          <td><?= htmlspecialchars($r['nom'].' '.$r['prenoms']) ?></td>
          <td><?= htmlspecialchars((string)($r['telephone'] ?? '')) ?></td>
          <td><?= htmlspecialchars((string)($r['email'] ?? '')) ?></td>
          <td><?= htmlspecialchars((string)($r['metier_nom'] ?? '')) ?></td>
          <td><?= htmlspecialchars((string)($r['specialites'] ?? '')) ?></td>
          <td>
            <span class="badge"><?= htmlspecialchars($r['statut']) ?></span>
          </td>
          <td>
            <form method="post" action="<?= url('/admin/formateurs/toggle') ?>" style="display:inline">
              <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf) ?>">
              <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
              <button>
                <?= ($r['statut'] === 'ACTIF') ? 'Désactiver' : 'Activer' ?>
              </button>
            </form>
          </td>
        </tr>
      <?php endforeach; ?>
    <?php endif; ?>
  </tbody>
</table>
