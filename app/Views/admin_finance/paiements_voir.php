<?php $csrf = Csrf::token(); ?>

<h1>Dossier paiement — Inscription #<?= (int)$fiche['inscription_id'] ?></h1>

<?php if (!empty($error)): ?><div class="error"><?= htmlspecialchars($error) ?></div><?php endif; ?>
<?php if (!empty($success)): ?><div class="success"><?= htmlspecialchars($success) ?></div><?php endif; ?>

<div class="card">
  <strong><?= htmlspecialchars($fiche['nom'].' '.$fiche['prenoms']) ?></strong> — <?= htmlspecialchars((string)($fiche['telephone'] ?? '')) ?><br>
  Cohorte: <?= htmlspecialchars($fiche['cohorte']) ?><br><br>

  Montant total: <?= htmlspecialchars((string)$fiche['montant_total']) ?><br>
  Bourse (réduction): <?= htmlspecialchars((string)$fiche['bourse_montant']) ?><br>
  <strong>Montant net:</strong> <?= htmlspecialchars((string)$fiche['montant_net']) ?><br>
  <strong>Total payé:</strong> <?= htmlspecialchars((string)$fiche['total_paye']) ?><br>
  <strong>Reste:</strong> <?= htmlspecialchars((string)$fiche['reste_a_payer']) ?><br>
  Date limite: <?= htmlspecialchars((string)($fiche['date_limite_paiement'] ?? '')) ?><br>
  Statut: <span class="badge"><?= htmlspecialchars($fiche['statut']) ?></span>
</div>

<h3 style="margin-top:14px;">Bourse (réduction)</h3>

<form method="post" action="<?= url('/admin/finance/bourse/save') ?>" style="display:flex; gap:10px; align-items:end; flex-wrap:wrap">
  <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf) ?>">
  <input type="hidden" name="inscription_id" value="<?= (int)$fiche['inscription_id'] ?>">

  <div>
    <label>Montant bourse</label><br>
    <input name="bourse_montant"
           value="<?= htmlspecialchars((string)($fiche['bourse_montant'] ?? '0')) ?>"
           style="padding:8px;width:140px;">
  </div>

  <button style="padding:10px 16px;">Enregistrer bourse</button>
</form>

<p class="muted" style="margin-top:6px;">
  La bourse réduit le montant net à payer (V1).
</p>




<h3 style="margin-top:14px;">Ajouter un paiement</h3>

<form method="post" action="<?= url('/admin/finance/paiements/ajouter') ?>" style="display:flex; gap:10px; align-items:end; flex-wrap:wrap">
  <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf) ?>">
  <input type="hidden" name="inscription_id" value="<?= (int)$fiche['inscription_id'] ?>">

  <div>
    <label>Montant *</label><br>
    <input name="montant" required style="padding:8px;width:140px;">
  </div>

  <div>
    <label>Date *</label><br>
    <input type="date" name="date_paiement" required style="padding:8px;">
  </div>

  <div>
    <label>Mode</label><br>
    <select name="mode" style="padding:8px;">
      <option value="MOBILE_MONEY">MOBILE_MONEY</option>
      <option value="ESPECES">ESPECES</option>
      <option value="VIREMENT">VIREMENT</option>
      <option value="CHEQUE">CHEQUE</option>
      <option value="AUTRE">AUTRE</option>
    </select>
  </div>

  <div>
    <label>Référence</label><br>
    <input name="reference" style="padding:8px;width:160px;">
  </div>

  <div>
    <label>Commentaire</label><br>
    <input name="commentaire" style="padding:8px;width:260px;">
  </div>

  <button style="padding:10px 16px;">Ajouter</button>
</form>

<h3 style="margin-top:16px;">Historique paiements</h3>

<table>
  <thead><tr><th>Date</th><th>Montant</th><th>Mode</th><th>Référence</th><th>Commentaire</th></tr></thead>
  <tbody>
    <?php if (empty($paiements)): ?>
      <tr><td colspan="5" class="muted">Aucun paiement.</td></tr>
    <?php else: ?>
      <?php foreach ($paiements as $p): ?>
        <tr>
          <td><?= htmlspecialchars($p['date_paiement']) ?></td>
          <td><?= htmlspecialchars((string)$p['montant']) ?></td>
          <td><?= htmlspecialchars($p['mode']) ?></td>
          <td><?= htmlspecialchars((string)($p['reference'] ?? '')) ?></td>
          <td><?= htmlspecialchars((string)($p['commentaire'] ?? '')) ?></td>
        </tr>
      <?php endforeach; ?>
    <?php endif; ?>
  </tbody>
</table>

<p style="margin-top:10px;">
  <a href="<?= url('/admin/finance/paiements') ?>">← Retour liste</a>
</p>
