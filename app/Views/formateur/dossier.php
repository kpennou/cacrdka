<?php $csrf = Csrf::token(); ?>
<h1>Mon dossier formateur</h1>

<?php if (!empty($error)): ?><div class="error"><?= htmlspecialchars($error) ?></div><?php endif; ?>
<?php if (!empty($success)): ?><div class="success"><?= htmlspecialchars($success) ?></div><?php endif; ?>

<div class="card">
  <strong><?= htmlspecialchars($dossier['nom'].' '.$dossier['prenoms']) ?></strong><br>
  Téléphone: <?= htmlspecialchars($dossier['telephone']) ?><br>
  Email: <?= htmlspecialchars((string)($dossier['email'] ?? '')) ?><br>
  Statut: <span class="badge"><?= htmlspecialchars($dossier['statut']) ?></span>
</div>

<p class="muted">
  Complétude: <?= (int)$completude['nb_pieces_fournies'] ?>/<?= (int)$completude['nb_pieces_obligatoires'] ?>
  — Complet: <?= ((int)$completude['is_complet']===1) ? 'Oui' : 'Non' ?>
</p>

<table>
  <thead><tr><th>Pièce</th><th>Obligatoire</th><th>Statut</th><th>Upload</th></tr></thead>
  <tbody>
    <?php foreach ($types as $t): $pid=(int)$t['id']; $ok=isset($have[$pid]); ?>
      <tr>
        <td><?= htmlspecialchars($t['libelle']) ?></td>
        <td><?= ((int)$t['is_obligatoire']===1) ? 'Oui' : 'Non' ?></td>
        <td><?= $ok ? 'Fourni' : 'Manquant' ?></td>
        <td>
          <?php if ($dossier['statut'] === 'BROUILLON'): ?>
            <form method="post" action="<?= url('/formateur/upload') ?>" enctype="multipart/form-data" style="display:flex;gap:8px;align-items:center">
              <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf) ?>">
              <input type="hidden" name="piece_type_id" value="<?= $pid ?>">
              <input type="file" name="piece" required>
              <button>Uploader</button>
            </form>
          <?php else: ?>
            <span class="muted">Lecture seule</span>
          <?php endif; ?>
        </td>
      </tr>
    <?php endforeach; ?>
  </tbody>
</table>

<?php if ($dossier['statut'] === 'BROUILLON'): ?>
  <form method="post" action="<?= url('/formateur/soumettre') ?>" style="margin-top:12px;">
    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf) ?>">
    <button style="padding:10px 16px;">Soumettre</button>
  </form>
<?php endif; ?>
