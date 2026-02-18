<?php $csrf = Csrf::token(); ?>

<h1>Affectations formateurs → matières</h1>

<?php if (!empty($error)): ?><div class="error"><?= htmlspecialchars($error) ?></div><?php endif; ?>
<?php if (!empty($success)): ?><div class="success"><?= htmlspecialchars($success) ?></div><?php endif; ?>

<!-- Filtres -->
<form method="get" action="<?= url('/admin/affectations') ?>" style="margin:10px 0; display:flex; gap:10px; align-items:end; flex-wrap:wrap">
  <div>
    <label>Métier (matières)</label><br>
    <select name="metier_id" style="padding:8px;width:260px;">
      <option value="0">Tous</option>
      <?php foreach ($metiers as $m): ?>
        <option value="<?= (int)$m['id'] ?>" <?= ((int)$filters['metier_id']===(int)$m['id'])?'selected':'' ?>>
          <?= htmlspecialchars($m['nom']) ?>
        </option>
      <?php endforeach; ?>
    </select>
  </div>

  <div>
    <label>Formateur</label><br>
    <select name="formateur_id" style="padding:8px;width:320px;">
      <option value="0">Tous</option>
      <?php foreach ($formateurs as $f): ?>
        <option value="<?= (int)$f['id'] ?>" <?= ((int)$filters['formateur_id']===(int)$f['id'])?'selected':'' ?>>
          <?= htmlspecialchars($f['matricule'].' - '.$f['nom'].' '.$f['prenoms']) ?>
        </option>
      <?php endforeach; ?>
    </select>
  </div>

  <button style="padding:10px 16px;">Filtrer</button>
</form>

<!-- Création -->
<div class="card" style="margin:12px 0;">
  <h3>Créer une affectation</h3>
  <form method="post" action="<?= url('/admin/affectations/create') ?>" style="display:flex; gap:10px; align-items:end; flex-wrap:wrap">
    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf) ?>">

    <div>
      <label>Formateur (ACTIF) *</label><br>
      <select name="formateur_id" required style="padding:8px;width:320px;">
        <option value="">-- choisir --</option>
        <?php foreach ($formateurs as $f): ?>
          <option value="<?= (int)$f['id'] ?>">
            <?= htmlspecialchars($f['matricule'].' - '.$f['nom'].' '.$f['prenoms']) ?>
          </option>
        <?php endforeach; ?>
      </select>
    </div>

    <div>
      <label>Matière (active) *</label><br>
      <select name="matiere_id" required style="padding:8px;width:320px;">
        <option value="">-- choisir --</option>
        <?php foreach ($matieres as $m): ?>
          <option value="<?= (int)$m['id'] ?>"><?= htmlspecialchars($m['nom']) ?></option>
        <?php endforeach; ?>
      </select>
      <div class="muted">Astuce: filtre par métier pour réduire la liste.</div>
    </div>

    <button style="padding:10px 16px;">Affecter</button>
  </form>
</div>

<!-- Liste -->
<table>
  <thead>
    <tr>
      <th>Métier (matière)</th>
      <th>Matière</th>
      <th>Formateur</th>
      <th>Statut</th>
      <th>Actions</th>
    </tr>
  </thead>
  <tbody>
    <?php if (empty($rows)): ?>
      <tr><td colspan="5" class="muted">Aucune affectation.</td></tr>
    <?php else: ?>
      <?php foreach ($rows as $r): ?>
        <tr>
          <td><?= htmlspecialchars($r['matiere_metier_nom']) ?></td>
          <td><?= htmlspecialchars($r['matiere_nom']) ?></td>
          <td><?= htmlspecialchars($r['matricule'].' - '.$r['formateur_nom']) ?></td>
          <td><span class="badge"><?= htmlspecialchars($r['statut']) ?></span></td>
          <td>
            <form method="post" action="<?= url('/admin/affectations/toggle') ?>" style="display:inline">
              <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf) ?>">
              <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
              <button><?= ($r['statut']==='ACTIF')?'Désactiver':'Activer' ?></button>
            </form>
            <form method="post" action="<?= url('/admin/affectations/delete') ?>" style="display:inline" onsubmit="return confirm('Supprimer cette affectation ?');">
              <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf) ?>">
              <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
              <button>Supprimer</button>
            </form>
          </td>
        </tr>
      <?php endforeach; ?>
    <?php endif; ?>
  </tbody>
</table>
