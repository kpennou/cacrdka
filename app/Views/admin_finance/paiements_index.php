<?php $csrf = Csrf::token(); ?>

<h1>Finance — Encaissements</h1>

<?php if (!empty($error)): ?><div class="error"><?= htmlspecialchars($error) ?></div><?php endif; ?>
<?php if (!empty($success)): ?><div class="success"><?= htmlspecialchars($success) ?></div><?php endif; ?>

<form method="get" action="<?= url('/admin/finance/paiements') ?>" style="margin:10px 0; display:flex; gap:10px; align-items:end; flex-wrap:wrap">
  <div>
    <label>Cohorte</label><br>
    <select name="cohorte_id" style="padding:8px;width:280px;">
      <option value="0">Toutes</option>
      <?php foreach ($cohortes as $c): ?>
        <option value="<?= (int)$c['id'] ?>" <?= ((int)$filters['cohorte_id']===(int)$c['id'])?'selected':'' ?>>
          <?= htmlspecialchars($c['libelle']) ?>
        </option>
      <?php endforeach; ?>
    </select>
  </div>

  <div>
    <label>Recherche (nom / prénoms / téléphone)</label><br>
    <input name="q" value="<?= htmlspecialchars($filters['q'] ?? '') ?>" style="padding:8px;width:320px;">
  </div>

  <button style="padding:10px 16px;">Filtrer</button>
</form>

<table>
  <thead>
    <tr>
      <th>Inscription</th>
      <th>Apprenant</th>
      <th>Téléphone</th>
      <th>Cohorte</th>
      <th>Net</th>
      <th>Payé</th>
      <th>Reste</th>
      <th>Date limite</th>
      <th>Retard</th>
      <th>Action</th>
    </tr>
  </thead>
  <tbody>
    <?php if (empty($rows)): ?>
      <tr><td colspan="10" class="muted">Aucune donnée (ou snapshot finance manquant).</td></tr>
    <?php else: ?>
      <?php foreach ($rows as $r): ?>
        <tr>
          <td>#<?= (int)$r['inscription_id'] ?></td>
          <td><?= htmlspecialchars($r['nom'].' '.$r['prenoms']) ?></td>
          <td><?= htmlspecialchars((string)($r['telephone'] ?? '')) ?></td>
          <td><?= htmlspecialchars($r['cohorte']) ?></td>
          <td><?= htmlspecialchars((string)$r['montant_net']) ?></td>
          <td><?= htmlspecialchars((string)$r['total_paye']) ?></td>
          <td><strong><?= htmlspecialchars((string)$r['reste_a_payer']) ?></strong></td>
          <td><?= htmlspecialchars((string)($r['date_limite_paiement'] ?? '')) ?></td>
          <td><?= ((int)$r['en_retard']===1) ? 'Oui' : 'Non' ?></td>
          <td><a href="<?= url('/admin/finance/paiements/voir?id='.(int)$r['inscription_id']) ?>">Voir / Ajouter</a></td>
        </tr>
      <?php endforeach; ?>
    <?php endif; ?>
  </tbody>
</table>
