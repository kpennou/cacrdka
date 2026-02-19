<?php $csrf = Csrf::token(); ?>

<h1>Nouvelle cohorte</h1>

<?php if (!empty($error)): ?><div class="error"><?= htmlspecialchars($error) ?></div><?php endif; ?>

<form method="post" action="<?= url('/admin/cohortes/store') ?>" style="max-width:720px;">
  <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf) ?>">

  <label>Métier *</label><br>
  <select name="metier_id" required style="padding:8px;width:100%;">
    <option value="">— Choisir —</option>
    <?php foreach ($metiers as $m): ?>
      <option value="<?= (int)$m['id'] ?>"><?= htmlspecialchars($m['nom']) ?></option>
    <?php endforeach; ?>
  </select><br><br>

  <label>Libellé *</label><br>
  <input name="libelle" required style="padding:8px;width:100%;" placeholder="Ex: Informatique — Cohorte Jan 2026"><br><br>

  <label>Date début *</label><br>
  <input type="date" name="date_debut" required style="padding:8px;"><br><br>

  <label>Date fin *</label><br>
  <input type="date" name="date_fin" required style="padding:8px;"><br><br>

  <label>Capacité</label><br>
  <input name="capacite" style="padding:8px;width:200px;" placeholder="Ex: 30"><br><br>

  <label>Statut</label><br>
  <select name="statut" style="padding:8px;">
    <option value="PLANIFIEE">PLANIFIEE</option>
    <option value="EN_COURS">EN_COURS</option>
    <option value="CLOTUREE">CLOTUREE</option>
  </select><br><br>

  <button style="padding:10px 16px;">Créer</button>
  <a href="<?= url('/admin/cohortes') ?>" style="margin-left:10px;">Annuler</a>
</form>
