<?php $csrf = Csrf::token(); ?>
<h1>Vivier formateurs (Admin)</h1>

<?php if (!empty($error)): ?><div class="error"><?= htmlspecialchars($error) ?></div><?php endif; ?>
<?php if (!empty($success)): ?><div class="success"><?= htmlspecialchars($success) ?></div><?php endif; ?>

<form method="get" action="<?= url('/admin/vivier-formateurs') ?>" style="margin:10px 0;">
  <label>Statut:</label>
  <select name="statut">
    <?php foreach (['SOUMIS','BROUILLON','RETENU','REJETE','CONVERTI'] as $s): ?>
      <option value="<?= $s ?>" <?= ($statut===$s)?'selected':'' ?>><?= $s ?></option>
    <?php endforeach; ?>
  </select>
  <button>Filtrer</button>
</form>

<table>
  <!-- <thead><tr><th>ID</th><th>Nom</th><th>Téléphone</th><th>Email</th><th>Statut</th><th>Complet</th><th>Action</th></tr></thead> -->
  <thead><tr>
    <th>ID</th><th>Nom</th><th>Téléphone</th><th>Email</th><th>Métier</th><th>Statut</th><th>Complet</th><th>Action</th>
  </tr></thead>

  <tbody>
    <?php if (empty($rows)): ?>
      <tr><td colspan="7" class="muted">Aucun élément.</td></tr>
    <?php else: ?>
      <?php foreach ($rows as $r): ?>
        <tr>
          <td><?= (int)$r['id'] ?></td>
          <td><?= htmlspecialchars($r['nom'].' '.$r['prenoms']) ?></td>
          <td><?= htmlspecialchars($r['telephone']) ?></td>
          <td><?= htmlspecialchars((string)($r['email'] ?? '')) ?></td>
          <td><?= htmlspecialchars($r['metier_nom']) ?></td>
          <td><?= htmlspecialchars($r['statut']) ?></td>
          <td><?= ((int)$r['is_complet']===1) ? 'Oui' : 'Non' ?></td>
          <td><a href="<?= url('/admin/vivier-formateurs/voir?id='.(int)$r['id']) ?>">Voir</a></td>
        </tr>
      <?php endforeach; ?>
    <?php endif; ?>
  </tbody>
</table>
