<?php $csrf = Csrf::token(); ?>

<h1>Finance — Tarifs par cohorte</h1>

<?php if (!empty($error)): ?><div class="error"><?= htmlspecialchars($error) ?></div><?php endif; ?>
<?php if (!empty($success)): ?><div class="success"><?= htmlspecialchars($success) ?></div><?php endif; ?>

<table>
  <thead>
    <tr>
      <th>ID</th>
      <th>Cohorte</th>
      <th>Début</th>
      <th>Fin</th>
      <th>Montant total</th>
      <th>Date limite paiement</th>
      <th>Action</th>
    </tr>
  </thead>
  <tbody>
    <?php foreach ($cohortes as $c): ?>
      <tr>
        <td><?= (int)$c['id'] ?></td>
        <td><?= htmlspecialchars($c['libelle']) ?></td>
        <td><?= htmlspecialchars($c['date_debut']) ?></td>
        <td><?= htmlspecialchars($c['date_fin']) ?></td>

        <td><?= htmlspecialchars((string)($c['montant_total'] ?? '')) ?></td>
        <td><?= htmlspecialchars((string)($c['date_limite_paiement'] ?? '')) ?></td>

        <td>
          <form method="post" action="<?= url('/admin/finance/cohortes/save') ?>" style="display:flex; gap:8px; align-items:center; flex-wrap:wrap">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf) ?>">
            <input type="hidden" name="cohorte_id" value="<?= (int)$c['id'] ?>">

            <input name="montant_total" placeholder="Montant" value="<?= htmlspecialchars((string)($c['montant_total'] ?? '')) ?>" style="padding:6px;width:120px;">
            <input type="date" name="date_limite_paiement" value="<?= htmlspecialchars((string)($c['date_limite_paiement'] ?? '')) ?>" style="padding:6px;">
            <button>Enregistrer</button>
          </form>
        </td>
      </tr>
    <?php endforeach; ?>
  </tbody>
</table>

<p class="muted" style="margin-top:10px;">
  La date limite sert à calculer les retards (impayés).
</p>
