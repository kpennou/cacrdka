<?php $csrf = Csrf::token(); ?>
<h1>Dossier #<?= (int)$dossier['id'] ?></h1>

<?php if (!empty($error)): ?><div class="error"><?= htmlspecialchars($error) ?></div><?php endif; ?>
<?php if (!empty($success)): ?><div class="success"><?= htmlspecialchars($success) ?></div><?php endif; ?>

<div class="card">
  <strong><?= htmlspecialchars($dossier['nom'].' '.$dossier['prenoms']) ?></strong><br>
  Téléphone: <?= htmlspecialchars($dossier['telephone']) ?><br>
  Métier: <?= htmlspecialchars($dossier['metier_nom']) ?><br>
  Statut: <span class="badge"><?= htmlspecialchars($dossier['statut']) ?></span><br>
  Complétude: <?= (int)$completude['nb_pieces_fournies'] ?>/<?= (int)$completude['nb_pieces_obligatoires'] ?>
  — <?= ((int)$completude['is_complet']===1) ? 'Complet' : 'Incomplet' ?>
</div>

<h3>Pièces fournies</h3>

<table>
  <thead>
    <tr>
      <th>Pièce</th>
      <th>Obligatoire</th>
      <th>Fichier</th>
      <th>Date</th>
      <th>Action</th>
    </tr>
  </thead>
  <tbody>
    <?php if (empty($pieces)): ?>
      <tr><td colspan="5" class="muted">Aucune pièce uploadée.</td></tr>
    <?php else: ?>
      <?php foreach ($pieces as $p): ?>
        <tr>
          <td><?= htmlspecialchars($p['libelle']) ?></td>
          <td><?= ((int)$p['is_obligatoire']===1)?'Oui':'Non' ?></td>
          <td><?= htmlspecialchars($p['fichier_nom_original']) ?></td>
          <td><?= htmlspecialchars($p['uploaded_at']) ?></td>
          <td>
            <a href="<?= url('/admin/piece/download?id='.(int)$p['piece_id']) ?>">Télécharger</a>
          </td>
        </tr>
      <?php endforeach; ?>
    <?php endif; ?>
  </tbody>
</table>


<form method="post" action="/admin/vivier-apprenants/selectionner" style="display:inline-block;margin-right:8px;">
  <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf) ?>">
  <input type="hidden" name="id" value="<?= (int)$dossier['id'] ?>">
  <button <?= ($dossier['statut']!=='SOUMIS')?'disabled':'' ?>>Sélectionner</button>
</form>

<form method="post" action="/admin/vivier-apprenants/rejeter" style="display:inline-block;margin-right:8px;">
  <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf) ?>">
  <input type="hidden" name="id" value="<?= (int)$dossier['id'] ?>">
  <button <?= (!in_array($dossier['statut'],['SOUMIS','SELECTIONNE'],true))?'disabled':'' ?>>Rejeter</button>
</form>

<?php if ($dossier['statut']==='SELECTIONNE'): ?>
  <form method="post" action="/admin/vivier-apprenants/convertir" style="margin-top:10px;">
    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf) ?>">
    <input type="hidden" name="id" value="<?= (int)$dossier['id'] ?>">
    <label>Cohorte:</label>
    <select name="cohorte_id" required>
      <option value="">-- choisir --</option>
      <?php foreach (DB::pdo()->query("SELECT id, libelle FROM cohortes WHERE metier_id=".(int)$dossier['metier_id']." ORDER BY date_debut DESC")->fetchAll() as $c): ?>
        <option value="<?= (int)$c['id'] ?>"><?= htmlspecialchars($c['libelle']) ?></option>
      <?php endforeach; ?>
    </select>
    <button>Convertir</button>
  </form>
<?php else: ?>
  <p class="muted">Conversion possible uniquement si statut = SELECTIONNE.</p>
<?php endif; ?>

<p style="margin-top:14px;"><a href="/admin/vivier-apprenants">← Retour liste</a></p>
