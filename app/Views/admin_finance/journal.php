<?php $csrf = Csrf::token(); ?>

<h1>Finance — Journal de caisse</h1>

<?php if (!empty($error)): ?><div class="error"><?= htmlspecialchars($error) ?></div><?php endif; ?>
<?php if (!empty($success)): ?><div class="success"><?= htmlspecialchars($success) ?></div><?php endif; ?>

<form method="get" action="<?= url('/admin/finance/journal') ?>" style="margin:10px 0; display:flex; gap:10px; align-items:end; flex-wrap:wrap">
  <div>
    <label>Date début</label><br>
    <input type="date" name="date_debut" value="<?= htmlspecialchars($filters['date_debut']) ?>" style="padding:8px;">
  </div>

  <div>
    <label>Date fin</label><br>
    <input type="date" name="date_fin" value="<?= htmlspecialchars($filters['date_fin']) ?>" style="padding:8px;">
  </div>

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
    <label>Montant compté (contrôle)</label><br>
    <input name="montant_compte"
            value="<?= htmlspecialchars((string)($filters['montant_compte'] ?? '')) ?>"
            placeholder="Ex: 150000"
            style="padding:8px;width:180px;">
  </div>


  <button style="padding:10px 16px;">Afficher</button>

  <a class="btn"
     href="<?= url('/admin/finance/export/journal?date_debut='.urlencode($filters['date_debut']).'&date_fin='.urlencode($filters['date_fin']).'&cohorte_id='.(int)$filters['cohorte_id']) ?>"
     style="padding:10px 16px; display:inline-block;">
     Export CSV
  </a>
</form>

<div class="card" style="margin:12px 0;">
  <strong>Total période :</strong> <?= htmlspecialchars((string)$totaux['total']) ?><br>
  ESPECES: <?= htmlspecialchars((string)$totaux['total_especes']) ?> |
  MOBILE_MONEY: <?= htmlspecialchars((string)$totaux['total_mobile']) ?> |
  VIREMENT: <?= htmlspecialchars((string)$totaux['total_virement']) ?> |
  CHEQUE: <?= htmlspecialchars((string)$totaux['total_cheque']) ?> |
  AUTRE: <?= htmlspecialchars((string)$totaux['total_autre']) ?>
</div>

<h3 style="margin-top:16px;">Contrôle de caisse</h3>

<div class="card" style="margin:12px 0;">
  <div><strong>Total théorique (sur période)</strong> : <?= htmlspecialchars((string)$totaux['total']) ?></div>

  <?php if (!empty($controle) && $controle['montant_compte'] !== null): ?>
    <div style="margin-top:6px;">
      <strong>Montant compté</strong> : <?= htmlspecialchars((string)$controle['montant_compte']) ?>
    </div>
    <div style="margin-top:6px;">
      <strong>Écart (compté − théorique)</strong> :
      <span class="badge">
        <?= htmlspecialchars((string)$controle['ecart']) ?>
      </span>
    </div>
    <p class="muted" style="margin-top:6px;">
      Écart = 0 : OK. Écart négatif : manque. Écart positif : surplus.
    </p>
  <?php else: ?>
    <p class="muted" style="margin-top:6px;">
      Renseigne “Montant compté” puis clique “Afficher” pour calculer l’écart.
    </p>
  <?php endif; ?>
</div>



<h3 style="margin-top:16px;">Totaux par jour</h3>

<table>
  <thead>
    <tr>
      <th>Date</th>
      <th>Nb paiements</th>
      <th>Total</th>
      <th>ESPECES</th>
      <th>MOBILE_MONEY</th>
      <th>VIREMENT</th>
      <th>CHEQUE</th>
      <th>AUTRE</th>
    </tr>
  </thead>
  <tbody>
    <?php if (empty($totauxParJour)): ?>
      <tr><td colspan="8" class="muted">Aucun paiement sur la période.</td></tr>
    <?php else: ?>
      <?php foreach ($totauxParJour as $t): ?>
        <tr>
          <td><?= htmlspecialchars($t['date_paiement']) ?></td>
          <td><?= (int)$t['nb_paiements'] ?></td>
          <td><strong><?= htmlspecialchars((string)$t['total']) ?></strong></td>
          <td><?= htmlspecialchars((string)$t['total_especes']) ?></td>
          <td><?= htmlspecialchars((string)$t['total_mobile']) ?></td>
          <td><?= htmlspecialchars((string)$t['total_virement']) ?></td>
          <td><?= htmlspecialchars((string)$t['total_cheque']) ?></td>
          <td><?= htmlspecialchars((string)$t['total_autre']) ?></td>
        </tr>
      <?php endforeach; ?>
    <?php endif; ?>
  </tbody>
</table>

<h3 style="margin-top:16px;">Liste détaillée des paiements</h3>

<table>
  <thead>
    <tr>
      <th>ID</th>
      <th>Date</th>
      <th>Montant</th>
      <th>Mode</th>
      <th>Cohorte</th>
      <th>Inscription</th>
      <th>Apprenant</th>
      <th>Téléphone</th>
      <th>Référence</th>
      <th>Commentaire</th>
    </tr>
  </thead>
  <tbody>
    <?php if (empty($rows)): ?>
      <tr><td colspan="10" class="muted">Aucun paiement sur la période.</td></tr>
    <?php else: ?>
      <?php foreach ($rows as $r): ?>
        <tr>
          <td>#<?= (int)$r['paiement_id'] ?></td>
          <td><?= htmlspecialchars($r['date_paiement']) ?></td>
          <td><?= htmlspecialchars((string)$r['montant']) ?></td>
          <td><span class="badge"><?= htmlspecialchars($r['mode']) ?></span></td>
          <td><?= htmlspecialchars($r['cohorte']) ?></td>
          <td>#<?= (int)$r['inscription_id'] ?></td>
          <td><?= htmlspecialchars($r['apprenant']) ?></td>
          <td><?= htmlspecialchars((string)($r['telephone'] ?? '')) ?></td>
          <td><?= htmlspecialchars((string)($r['reference'] ?? '')) ?></td>
          <td><?= htmlspecialchars((string)($r['commentaire'] ?? '')) ?></td>
        </tr>
      <?php endforeach; ?>
    <?php endif; ?>
  </tbody>
</table>


