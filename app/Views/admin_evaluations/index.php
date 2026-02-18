<?php $csrf = Csrf::token(); ?>

<h1>Évaluations</h1>

<?php if (!empty($error)): ?><div class="error"><?= htmlspecialchars($error) ?></div><?php endif; ?>
<?php if (!empty($success)): ?><div class="success"><?= htmlspecialchars($success) ?></div><?php endif; ?>

<form method="get" action="<?= url('/admin/evaluations') ?>" style="margin:10px 0; display:flex; gap:10px; align-items:end; flex-wrap:wrap">
  <div>
    <label>Cohorte</label><br>
    <select name="cohorte_id" style="padding:8px;width:260px;">
      <option value="0">Toutes</option>
      <?php foreach ($cohortes as $c): ?>
        <option value="<?= (int)$c['id'] ?>" <?= ((int)$filters['cohorte_id']===(int)$c['id'])?'selected':'' ?>>
          <!-- <?= htmlspecialchars($c['nom']) ?> -->
        <?= htmlspecialchars($c['libelle']) ?>
        </option>
      <?php endforeach; ?>
    </select>
  </div>

  <div>
    <label>Métier (pour filtrer matières)</label><br>
    <select name="metier_id" style="padding:8px;width:260px;">
      <option value="0">Tous</option>
      <?php foreach ($metiers as $m): ?>
        <option value="<?= (int)$m['id'] ?>" <?= ((int)$filters['metier_id']===(int)$m['id'])?'selected':'' ?>>
          <?= htmlspecialchars($m['nom']) ?>
        </option>
      <?php endforeach; ?>
    </select>
  </div>

  <button style="padding:10px 16px;">Filtrer</button>
</form>

<div class="card" style="margin:12px 0;">
  <h3>Créer une évaluation</h3>
  <form method="post" action="<?= url('/admin/evaluations/create') ?>" style="display:flex; gap:10px; align-items:end; flex-wrap:wrap">
    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf) ?>">

    <div>
      <label>Cohorte *</label><br>
      <select name="cohorte_id" required style="padding:8px;width:260px;">
        <option value="">-- choisir --</option>
        <?php foreach ($cohortes as $c): ?>
          <!-- <option value="<?= (int)$c['id'] ?>"><?= htmlspecialchars($c['nom']) ?></option> -->
          <option value="<?= (int)$c['id'] ?>"><?= htmlspecialchars($c['libelle']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>

    <div>
      <label>Matière *</label><br>
      <select name="matiere_id" required style="padding:8px;width:260px;">
        <option value="">-- choisir --</option>
        <?php foreach ($matieres as $m): ?>
          <option value="<?= (int)$m['id'] ?>"><?= htmlspecialchars($m['nom']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>

    <div>
      <label>Libellé *</label><br>
      <input name="libelle" required style="padding:8px;width:260px;">
    </div>

    <div>
      <label>Date</label><br>
      <input type="date" name="date_eval" style="padding:8px;">
    </div>

    <div>
      <label>Note /</label><br>
      <input name="note_sur" value="20" style="padding:8px;width:80px;">
    </div>

    <button style="padding:10px 16px;">Créer</button>
  </form>
</div>

<table>
  <thead>
    <tr>
      <th>ID</th><th>Cohorte</th><th>Matière</th><th>Libellé</th><th>Statut</th>
      <th>Inscrits</th><th>Notes saisies</th><th>Manquantes</th><th>Actions</th>
    </tr>
  </thead>
  <tbody>
    <?php if (empty($rows)): ?>
      <tr><td colspan="9" class="muted">Aucune évaluation.</td></tr>
    <?php else: ?>
      <?php foreach ($rows as $r): ?>
        <tr>
          <td><?= (int)$r['evaluation_id'] ?></td>
          <td><?= htmlspecialchars($r['cohorte']) ?></td>
          <td><?= htmlspecialchars($r['matiere']) ?></td>
          <td><?= htmlspecialchars($r['libelle']) ?></td>
          <td><span class="badge"><?= htmlspecialchars($r['statut']) ?></span></td>
          <td><?= (int)$r['nb_inscrits'] ?></td>
          <td><?= (int)$r['nb_notes_saisies'] ?></td>
          <td><?= (int)$r['nb_notes_manquantes'] ?></td>
          <td>
            <a href="<?= url('/admin/evaluations/notes?id='.(int)$r['evaluation_id']) ?>">Saisir notes</a>
            &nbsp;|&nbsp;
            <form method="post" action="<?= url('/admin/evaluations/toggle') ?>" style="display:inline">
              <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf) ?>">
              <input type="hidden" name="id" value="<?= (int)$r['evaluation_id'] ?>">
              <button>Cycle statut</button>
            </form>
          </td>
        </tr>
      <?php endforeach; ?>
    <?php endif; ?>
  </tbody>
</table>
