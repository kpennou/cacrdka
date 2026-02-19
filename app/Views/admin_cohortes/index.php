<h1>Admin — Cohortes</h1>

<?php if (!empty($error)): ?><div class="error"><?= htmlspecialchars($error) ?></div><?php endif; ?>
<?php if (!empty($success)): ?><div class="success"><?= htmlspecialchars($success) ?></div><?php endif; ?>

<p>
  <a href="<?= url('/admin/cohortes/create') ?>" class="btn">+ Nouvelle cohorte</a>
</p>

<table>
  <thead>
    <tr>
      <th>ID</th>
      <th>Métier</th>
      <th>Libellé</th>
      <th>Début</th>
      <th>Fin</th>
      <th>Capacité</th>
      <th>Statut</th>
      <th>Action</th>
    </tr>
  </thead>
  <tbody>
    <?php if (empty($rows)): ?>
      <tr><td colspan="8" class="muted">Aucune cohorte.</td></tr>
    <?php else: ?>
      <?php foreach ($rows as $r): ?>
        <tr>
          <td>#<?= (int)$r['id'] ?></td>
          <td><?= htmlspecialchars($r['metier']) ?></td>
          <td><?= htmlspecialchars($r['libelle']) ?></td>
          <td><?= htmlspecialchars($r['date_debut']) ?></td>
          <td><?= htmlspecialchars($r['date_fin']) ?></td>
          <td><?= htmlspecialchars((string)($r['capacite'] ?? '')) ?></td>
          <td><span class="badge"><?= htmlspecialchars($r['statut']) ?></span></td>
          <td><a href="<?= url('/admin/cohortes/edit?id='.(int)$r['id']) ?>">Modifier</a></td>
        </tr>
      <?php endforeach; ?>
    <?php endif; ?>
  </tbody>
</table>
