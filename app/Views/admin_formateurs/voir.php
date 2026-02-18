<?php $csrf = Csrf::token(); ?>
<h1>Formateur – Dossier #<?= (int)$dossier['id'] ?></h1>

<?php if (!empty($error)): ?><div class="error"><?= htmlspecialchars($error) ?></div><?php endif; ?>
<?php if (!empty($success)): ?><div class="success"><?= htmlspecialchars($success) ?></div><?php endif; ?>

<div class="card">
  <strong><?= htmlspecialchars($dossier['nom'].' '.$dossier['prenoms']) ?></strong><br>
  Téléphone: <?= htmlspecialchars($dossier['telephone']) ?><br>
  Email: <?= htmlspecialchars((string)($dossier['email'] ?? '')) ?><br>
  Métier: <?= htmlspecialchars($dossier['metier_nom']) ?><br>
  Spécialités: <?= htmlspecialchars((string)($dossier['specialites'] ?? '')) ?><br>
  Statut: <span class="badge"><?= htmlspecialchars($dossier['statut']) ?></span><br>
  Complétude: <?= (int)$completude['nb_pieces_fournies'] ?>/<?= (int)$completude['nb_pieces_obligatoires'] ?>
  — <?= ((int)$completude['is_complet']===1) ? 'Complet' : 'Incomplet' ?>
</div>

<h3>Pièces fournies</h3>
<table>
  <thead><tr><th>Pièce</th><th>Obligatoire</th><th>Fichier</th><th>Date</th><th>Action</th></tr></thead>
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
          <td><a href="<?= url('/admin/formateur-piece/download?id='.(int)$p['piece_id']) ?>">Télécharger</a></td>
        </tr>
      <?php endforeach; ?>
    <?php endif; ?>
  </tbody>
</table>

<form method="post" action="<?= url('/admin/vivier-formateurs/retenir') ?>" style="display:inline-block;margin-right:8px;margin-top:10px;">
  <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf) ?>">
  <input type="hidden" name="id" value="<?= (int)$dossier['id'] ?>">
  <button <?= ($dossier['statut']!=='SOUMIS')?'disabled':'' ?>>Retenir</button>
</form>

<form method="post" action="<?= url('/admin/vivier-formateurs/rejeter') ?>" style="display:inline-block;margin-top:10px;">
  <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf) ?>">
  <input type="hidden" name="id" value="<?= (int)$dossier['id'] ?>">
  <button <?= (!in_array($dossier['statut'],['SOUMIS','RETENU'],true))?'disabled':'' ?>>Rejeter</button>
</form>


<?php if ($dossier['statut']==='RETENU'): ?>
  <form method="post" action="<?= url('/admin/vivier-formateurs/convertir') ?>" style="display:inline-block;margin-left:8px;margin-top:10px;">
    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf) ?>">
    <input type="hidden" name="id" value="<?= (int)$dossier['id'] ?>">
    <button>Convertir en formateur (équipe)</button>
  </form>
<?php endif; ?>


<p style="margin-top:14px;"><a href="<?= url('/admin/vivier-formateurs') ?>">← Retour liste</a></p>
