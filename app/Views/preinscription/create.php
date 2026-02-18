<?php $csrf = Csrf::token(); ?>
<h1>Préinscription – Apprenant</h1>

<?php if (!empty($error)): ?>
  <div class="error"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<form method="post" action="/preinscription">
  <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf) ?>">

  <div style="margin:10px 0;">
    <label>Métier *</label><br>
    <select name="metier_id" required style="padding:8px;width:320px;">
      <option value="">-- Choisir --</option>
      <?php foreach ($metiers as $m): ?>
        <option value="<?= (int)$m['id'] ?>" <?= ((int)($old['metier_id'] ?? 0) === (int)$m['id']) ? 'selected' : '' ?>>
          <?= htmlspecialchars($m['nom']) ?>
        </option>
      <?php endforeach; ?>
    </select>
  </div>

  <div style="margin:10px 0;">
    <label>Nom (de famille) *</label><br>
    <input name="nom" value="<?= htmlspecialchars($old['nom'] ?? '') ?>" required style="padding:8px;width:320px;">
  </div>

  <div style="margin:10px 0;">
    <label>Prénoms *</label><br>
    <input name="prenoms" value="<?= htmlspecialchars($old['prenoms'] ?? '') ?>" required style="padding:8px;width:320px;">
  </div>

  <div style="margin:10px 0;">
    <label>Téléphone *</label><br>
    <input name="telephone" value="<?= htmlspecialchars($old['telephone'] ?? '') ?>" required style="padding:8px;width:320px;">
  </div>

  <div style="margin:10px 0;">
    <label>Date de naissance *</label><br>
    <input type="date" name="date_naissance" value="<?= htmlspecialchars($old['date_naissance'] ?? '') ?>" required style="padding:8px;width:320px;">
  </div>

  <div style="margin:10px 0;">
    <label>Sexe *</label><br>
    <select name="sexe" required style="padding:8px;width:320px;">
      <option value="">-- Choisir --</option>
      <option value="M" <?= (($old['sexe'] ?? '') === 'M') ? 'selected' : '' ?>>Masculin</option>
      <option value="F" <?= (($old['sexe'] ?? '') === 'F') ? 'selected' : '' ?>>Féminin</option>
    </select>
  </div>

  <div style="margin:10px 0;">
    <label>Niveau d’étude *</label><br>
    <input name="niveau_etude" value="<?= htmlspecialchars($old['niveau_etude'] ?? '') ?>" required style="padding:8px;width:320px;">
  </div>

  <button style="padding:10px 16px;">Créer mon dossier</button>
</form>
