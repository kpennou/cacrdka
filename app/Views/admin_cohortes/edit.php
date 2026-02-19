<?php $csrf = Csrf::token(); ?>

<h1>Modifier cohorte #<?= (int)$cohorte['id'] ?></h1>

<?php if (!empty($error)): ?><div class="error"><?= htmlspecialchars($error) ?></div><?php endif; ?>
<?php if (!empty($success)): ?><div class="success"><?= htmlspecialchars($success) ?></div><?php endif; ?>

<form method="post" action="<?= url('/admin/cohortes/update') ?>" style="max-width:720px;">
  <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf) ?>">
  <input type="hidden" name="id" value="<?= (int)$cohorte['id'] ?>">

  <label>Métier *</label><br>
  <select name="metier_id" required style="padding:8px;width:100%;">
    <?php foreach ($metiers as $m): ?>
      <option value="<?= (int)$m['id'] ?>" <?= ((int)$m['id']===(int)$cohorte['metier_id'])?'selected':'' ?>>
        <?= htmlspecialchars($m['nom']) ?>
      </option>
    <?php endforeach; ?>
  </select><br><br>

  <label>Libellé *</label><br>
  <input name="libelle" required style="padding:8px;width:100%;" value="<?= htmlspecialchars($cohorte['libelle']) ?>"><br><br>

  <label>Date début *</label><br>
  <input type="date" name="date_debut" required style="padding:8px;" value="<?= htmlspecialchars($cohorte['date_debut']) ?>"><br><br>

  <label>Date fin *</label><br>
  <input type="date" name="date_fin" required style="padding:8px;" value="<?= htmlspecialchars($cohorte['date_fin']) ?>"><br><br>

  <label>Capacité</label><br>
  <input name="capacite" style="padding:8px;width:200px;" value="<?= htmlspecialchars((string)($cohorte['capacite'] ?? '')) ?>"><br><br>

  <label>Statut</label><br>
  <select name="statut" style="padding:8px;">
    <option value="PLANIFIEE" <?= ($cohorte['statut']==='PLANIFIEE')?'selected':'' ?>>PLANIFIEE</option>
    <option value="EN_COURS" <?= ($cohorte['statut']==='EN_COURS')?'selected':'' ?>>EN_COURS</option>
    <option value="CLOTUREE" <?= ($cohorte['statut']==='CLOTUREE')?'selected':'' ?>>CLOTUREE</option>
  </select><br><br>

  <button style="padding:10px 16px;">Enregistrer</button>
  <a href="<?= url('/admin/cohortes') ?>" style="margin-left:10px;">Retour</a>
</form>
