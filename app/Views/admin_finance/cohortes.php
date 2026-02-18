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
      <th>Actions</th>
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
          <!-- Enregistrer tarif -->
          <form method="post" action="<?= url('/admin/finance/cohortes/save') ?>" style="display:flex; gap:8px; align-items:center; flex-wrap:wrap">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf) ?>">
            <input type="hidden" name="cohorte_id" value="<?= (int)$c['id'] ?>">

            <input name="montant_total"
                   placeholder="Montant"
                   value="<?= htmlspecialchars((string)($c['montant_total'] ?? '')) ?>"
                   style="padding:6px;width:120px;">

            <input type="date"
                   name="date_limite_paiement"
                   value="<?= htmlspecialchars((string)($c['date_limite_paiement'] ?? '')) ?>"
                   style="padding:6px;">

            <button type="submit">Enregistrer</button>
          </form>

          <!-- Rebuild snapshots manquants -->
          <form method="post" action="<?= url('/admin/finance/snapshot/rebuild') ?>" style="margin-top:6px;">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf) ?>">
            <input type="hidden" name="cohorte_id" value="<?= (int)$c['id'] ?>">
            <button type="submit" onclick="return confirm('Générer les snapshots manquants pour cette cohorte ?');">
              Générer snapshots manquants
            </button>
          </form>

          <div class="muted" style="margin-top:6px;">
            Utilise ce bouton si des inscriptions existent déjà avant paramétrage.
          </div>
        </td>
      </tr>
    <?php endforeach; ?>
  </tbody>
</table>

<p class="muted" style="margin-top:10px;">
  La date limite sert à calculer les retards (impayés).
</p>
