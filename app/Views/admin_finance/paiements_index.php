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
      <th>Statut finance</th>
      <th>Action</th>
    </tr>
  </thead>
  <tbody>
    <?php if (empty($rows)): ?>
      <tr><td colspan="11" class="muted">Aucune donnée.</td></tr>
    <?php else: ?>
      <?php foreach ($rows as $r): ?>
        <?php $sansSnapshot = (($r['statut_finance'] ?? '') === 'SANS_SNAPSHOT'); ?>
        <tr>
          <td>#<?= (int)$r['inscription_id'] ?></td>
          <td><?= htmlspecialchars($r['nom'].' '.$r['prenoms']) ?></td>
          <td><?= htmlspecialchars((string)($r['telephone'] ?? '')) ?></td>
          <td><?= htmlspecialchars($r['cohorte']) ?></td>

          <td><?= $sansSnapshot ? '—' : htmlspecialchars((string)$r['montant_net']) ?></td>
          <td><?= htmlspecialchars((string)$r['total_paye']) ?></td>

          <td>
            <?php if ($sansSnapshot): ?>
              <span class="badge">SANS_SNAPSHOT</span><br>
              <a href="<?= url('/admin/finance/cohortes') ?>">Paramétrer tarif</a>
            <?php else: ?>
              <strong><?= htmlspecialchars((string)$r['reste_a_payer']) ?></strong>
            <?php endif; ?>
          </td>

          <td><?= $sansSnapshot ? '—' : htmlspecialchars((string)($r['date_limite_paiement'] ?? '')) ?></td>
          <td><?= $sansSnapshot ? '—' : (((int)$r['en_retard']===1) ? 'Oui' : 'Non') ?></td>

          <td>
            <span class="badge"><?= htmlspecialchars((string)($r['statut_finance'] ?? '')) ?></span>
          </td>

          <td>
            <?php if ($sansSnapshot): ?>
              <a href="<?= url('/admin/finance/cohortes') ?>">Paramétrer + Générer</a>
            <?php else: ?>
              <a href="<?= url('/admin/finance/paiements/voir?id='.(int)$r['inscription_id']) ?>">Voir / Ajouter</a>
            <?php endif; ?>
          </td>
        </tr>
      <?php endforeach; ?>
    <?php endif; ?>
  </tbody>
</table>

<p class="muted" style="margin-top:10px;">
  Si une inscription est en <strong>SANS_SNAPSHOT</strong>, paramétrez d'abord le tarif de la cohorte puis utilisez
  <em>"Générer snapshots manquants"</em> sur la page Tarifs.
</p>
