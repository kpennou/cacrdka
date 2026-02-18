<?php
$csrf = Csrf::token();
$oldMetier = $old['metier_id_raw'] ?? '';
?>

<h1>Préinscription – Formateur</h1>

<?php if (!empty($error)): ?>
  <div class="error"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<form method="post" action="<?= url('/formateur/preinscription') ?>">
  <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf) ?>">

  <!-- Métier -->
  <div style="margin:10px 0;">
    <label>Métier (domaine de compétence) *</label><br>
    <select name="metier_id" required style="padding:8px;width:320px;" id="metier_id">
      <option value="">-- Choisir --</option>

      <?php foreach ($metiers as $m): ?>
        <option value="<?= (int)$m['id'] ?>"
          <?= ($oldMetier === (string)$m['id']) ? 'selected' : '' ?>>
          <?= htmlspecialchars($m['nom']) ?>
        </option>
      <?php endforeach; ?>

      <option value="AUTRE" <?= ($oldMetier === 'AUTRE') ? 'selected' : '' ?>>
        Autre
      </option>
    </select>
  </div>

  <!-- Nom -->
  <div style="margin:10px 0;">
    <label>Nom (de famille) *</label><br>
    <input name="nom"
           value="<?= htmlspecialchars($old['nom'] ?? '') ?>"
           required
           style="padding:8px;width:320px;">
  </div>

  <!-- Prénoms -->
  <div style="margin:10px 0;">
    <label>Prénoms *</label><br>
    <input name="prenoms"
           value="<?= htmlspecialchars($old['prenoms'] ?? '') ?>"
           required
           style="padding:8px;width:320px;">
  </div>

  <!-- Téléphone -->
  <div style="margin:10px 0;">
    <label>Téléphone *</label><br>
    <input name="telephone"
           value="<?= htmlspecialchars($old['telephone'] ?? '') ?>"
           required
           style="padding:8px;width:320px;">
  </div>

  <!-- Email -->
  <div style="margin:10px 0;">
    <label>Email</label><br>
    <input name="email"
           type="email"
           value="<?= htmlspecialchars($old['email'] ?? '') ?>"
           style="padding:8px;width:320px;">
  </div>

  <!-- Spécialités -->
  <div style="margin:10px 0;">
    <label>Spécialités (obligatoire si “Autre”)</label><br>
    <input name="specialites"
           id="specialites"
           value="<?= htmlspecialchars($old['specialites'] ?? '') ?>"
           style="padding:8px;width:520px;">
    <div class="muted">
      Ex: Réseaux, Maintenance informatique, Pédagogie, Couture industrielle…
    </div>
  </div>

  <button style="padding:10px 16px;">Créer mon dossier</button>
</form>

<script>
(function(){
  const metier = document.getElementById('metier_id');
  const spec = document.getElementById('specialites');

  function sync() {
    const isAutre = (metier.value === 'AUTRE');
    spec.required = isAutre;

    if (isAutre) {
      spec.placeholder = "Obligatoire : précisez votre domaine";
      spec.style.borderColor = "#d9534f";
    } else {
      spec.placeholder = "";
      spec.style.borderColor = "";
    }
  }

  metier.addEventListener('change', sync);
  sync();
})();
</script>
