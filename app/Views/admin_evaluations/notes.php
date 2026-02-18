<?php $csrf = Csrf::token(); ?>

<h1>Saisie notes</h1>

<?php if (!empty($error)): ?><div class="error"><?= htmlspecialchars($error) ?></div><?php endif; ?>
<?php if (!empty($success)): ?><div class="success"><?= htmlspecialchars($success) ?></div><?php endif; ?>

<div class="card">
  <strong><?= htmlspecialchars($eval['libelle']) ?></strong><br>
  Cohorte: <?= htmlspecialchars($eval['cohorte']) ?><br>
  Matière: <?= htmlspecialchars($eval['matiere']) ?><br>
  Note sur: <?= htmlspecialchars((string)$eval['note_sur']) ?><br>
  Statut: <span class="badge"><?= htmlspecialchars($eval['statut']) ?></span>
</div>

<form method="post" action="<?= url('/admin/evaluations/notes/save') ?>" style="margin-top:12px;">
  <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf) ?>">
  <input type="hidden" name="evaluation_id" value="<?= (int)$eval['id'] ?>">

  <table>
    <thead>
      <tr>
        <th>Apprenant</th>
        <th>Absent</th>
        <th>Note</th>
        <th>Remarque</th>
      </tr>
    </thead>
    <tbody>
      <?php if (empty($lignes)): ?>
        <tr><td colspan="4" class="muted">Aucun apprenant inscrit à cette cohorte.</td></tr>
      <?php else: ?>
        <?php foreach ($lignes as $l): ?>
          <tr>
            <td><?= htmlspecialchars($l['apprenant_nom']) ?></td>
            <td>
              <input type="checkbox"
                     name="absent[<?= (int)$l['apprenant_id'] ?>]"
                     <?= ((int)($l['absent'] ?? 0)===1)?'checked':'' ?>>
            </td>
            <td>
              <input name="note[<?= (int)$l['apprenant_id'] ?>]"
                     value="<?= htmlspecialchars((string)($l['note'] ?? '')) ?>"
                     style="padding:6px;width:90px;">
            </td>
            <td>
              <input name="remarque[<?= (int)$l['apprenant_id'] ?>]"
                     value="<?= htmlspecialchars((string)($l['remarque'] ?? '')) ?>"
                     style="padding:6px;width:320px;">
            </td>
          </tr>
        <?php endforeach; ?>
      <?php endif; ?>
    </tbody>
  </table>

  <button style="padding:10px 16px; margin-top:10px;">Enregistrer</button>
</form>

<p style="margin-top:10px;">
  <a href="<?= url('/admin/evaluations') ?>">← Retour évaluations</a>
</p>
