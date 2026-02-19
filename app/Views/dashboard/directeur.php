<h1>Vue 360° Directeur (V1)</h1>

<div class="cards">
  <div class="card"><strong>Vivier total</strong><br><?= (int)$viviers['vivier_apprenants_total'] ?></div>
  <div class="card"><strong>Brouillons</strong><br><?= (int)$viviers['vivier_apprenants_brouillons'] ?></div>
  <div class="card"><strong>Soumis</strong><br><?= (int)$viviers['vivier_apprenants_soumis'] ?></div>
  <div class="card"><strong>Incomplets</strong><br><?= (int)$viviers['vivier_apprenants_incomplets'] ?></div>
</div>

<h3 style="margin-top:18px;">Top métiers (volume)</h3>
<table>
  <thead>
    <tr>
      <th>Métier</th>
      <th>Total dossiers</th>
      <th>Soumis</th>
      <th>Brouillons</th>
      <th>Sélectionnés</th>
      <th>Rejetés</th>
      <th>Convertis</th>
    </tr>
  </thead>
  <tbody>
    <?php if (empty($topMetiers)): ?>
      <tr><td colspan="7" class="muted">Aucune donnée.</td></tr>
    <?php else: ?>
      <?php foreach ($topMetiers as $r): ?>
        <tr>
          <td><?= htmlspecialchars($r['metier']) ?></td>
          <td><?= (int)$r['nb_dossiers'] ?></td>
          <td><?= (int)$r['nb_soumis'] ?></td>
          <td><?= (int)$r['nb_brouillons'] ?></td>
          <td><?= (int)$r['nb_selectionnes'] ?></td>
          <td><?= (int)$r['nb_rejetes'] ?></td>
          <td><?= (int)$r['nb_convertis'] ?></td>
        </tr>
      <?php endforeach; ?>
    <?php endif; ?>
  </tbody>
</table>

<h3 style="margin-top:18px;">Alertes : dossiers incomplets (brouillons + soumis incomplets)</h3>
<table>
  <thead><tr><th>Métier</th><th>Incomplets</th></tr></thead>
  <tbody>
    <?php if (empty($incomplets)): ?>
      <tr><td colspan="2" class="muted">Aucune alerte.</td></tr>
    <?php else: ?>
      <?php foreach ($incomplets as $r): ?>
        <tr>
          <td><?= htmlspecialchars($r['metier']) ?></td>
          <td><?= (int)$r['nb_incomplets'] ?></td>
        </tr>
      <?php endforeach; ?>
    <?php endif; ?>
  </tbody>
</table>

<h3 style="margin-top:18px;">Derniers dossiers soumis (10)</h3>
<table>
  <thead><tr><th>ID</th><th>Candidat</th><th>Téléphone</th><th>Métier</th><th>Soumis le</th></tr></thead>
  <tbody>
    <?php if (empty($derniersSoumis)): ?>
      <tr><td colspan="5" class="muted">Aucun dossier soumis.</td></tr>
    <?php else: ?>
      <?php foreach ($derniersSoumis as $r): ?>
        <tr>
          <td><?= (int)$r['id'] ?></td>
          <td><?= htmlspecialchars($r['nom'].' '.$r['prenoms']) ?></td>
          <td><?= htmlspecialchars($r['telephone']) ?></td>
          <td><?= htmlspecialchars($r['metier']) ?></td>
          <td><?= htmlspecialchars($r['submitted_at']) ?></td>
        </tr>
      <?php endforeach; ?>
    <?php endif; ?>
  </tbody>
</table>

<!-- Ajout donnees formateur -->
<h2 style="margin-top:22px;">Formateurs – Vue 360°</h2>

<div class="cards">
  <div class="card"><strong>Vivier total</strong><br><?= (int)$formKpi['vivier_formateurs_total'] ?></div>
  <div class="card"><strong>Soumis</strong><br><?= (int)$formKpi['vivier_formateurs_soumis'] ?></div>
  <div class="card"><strong>Incomplets</strong><br><?= (int)$formKpi['vivier_formateurs_incomplets'] ?></div>
  <div class="card"><strong>Équipe actifs</strong><br><?= (int)$formKpi['equipe_formateurs_actifs'] ?></div>
</div>

<h3 style="margin-top:18px;">Top métiers (formateurs)</h3>
<table>
  <thead><tr><th>Métier</th><th>Total</th><th>Soumis</th><th>Retenus</th><th>Convertis</th></tr></thead>
  <tbody>
    <?php if (empty($formTop)): ?>
      <tr><td colspan="5" class="muted">Aucune donnée.</td></tr>
    <?php else: ?>
      <?php foreach ($formTop as $r): ?>
        <tr>
          <td><?= htmlspecialchars($r['metier']) ?></td>
          <td><?= (int)$r['nb_dossiers'] ?></td>
          <td><?= (int)$r['nb_soumis'] ?></td>
          <td><?= (int)$r['nb_retenus'] ?></td>
          <td><?= (int)$r['nb_convertis'] ?></td>
        </tr>
      <?php endforeach; ?>
    <?php endif; ?>
  </tbody>
</table>

<h3 style="margin-top:18px;">Derniers formateurs soumis (10)</h3>
<table>
  <thead><tr><th>ID</th><th>Nom</th><th>Téléphone</th><th>Métier</th><th>Soumis le</th></tr></thead>
  <tbody>
    <?php if (empty($formDerniersSoumis)): ?>
      <tr><td colspan="5" class="muted">Aucun.</td></tr>
    <?php else: ?>
      <?php foreach ($formDerniersSoumis as $r): ?>
        <tr>
          <td><?= (int)$r['id'] ?></td>
          <td><?= htmlspecialchars($r['nom'].' '.$r['prenoms']) ?></td>
          <td><?= htmlspecialchars($r['telephone']) ?></td>
          <td><?= htmlspecialchars($r['metier']) ?></td>
          <td><?= htmlspecialchars($r['submitted_at']) ?></td>
        </tr>
      <?php endforeach; ?>
    <?php endif; ?>
  </tbody>
</table>
<!-- Find Ajout donnees formateur -->


<h3 style="margin-top:18px;">Alertes – Notes manquantes (top 10)</h3>
<table>
  <thead><tr><th>Cohorte</th><th>Matière</th><th>Évaluation</th><th>Manquantes</th><th>Action</th></tr></thead>
  <tbody>
    <?php if (empty($notesManquantes)): ?>
      <tr><td colspan="5" class="muted">Aucune alerte.</td></tr>
    <?php else: ?>
      <?php foreach ($notesManquantes as $r): ?>
        <tr>
          <td><?= htmlspecialchars($r['cohorte']) ?></td>
          <td><?= htmlspecialchars($r['matiere']) ?></td>
          <td><?= htmlspecialchars($r['libelle']) ?></td>
          <td><?= (int)$r['nb_notes_manquantes'] ?></td>
          <td><a href="<?= url('/admin/evaluations/notes?id='.(int)$r['evaluation_id']) ?>">Saisir notes</a></td>
        </tr>
      <?php endforeach; ?>
    <?php endif; ?>
  </tbody>
</table>



<h2 style="margin-top:22px;">Finance — Vue 360°</h2>

<div class="cards">
  <div class="card"><strong>Total à encaisser</strong><br><?= htmlspecialchars((string)$kpiFinance['total_a_encaisser']) ?></div>
  <div class="card"><strong>Total encaissé</strong><br><?= htmlspecialchars((string)$kpiFinance['total_encaisse']) ?></div>
  <div class="card"><strong>Total restant</strong><br><?= htmlspecialchars((string)$kpiFinance['total_restant']) ?></div>
  <div class="card"><strong>Total bourses</strong><br><?= htmlspecialchars((string)$kpiBourses['total_bourses']) ?></div>

</div>

<h3 style="margin-top:18px;">Alertes – Impayés (top 10 retards)</h3>
<table>
  <thead><tr><th>Cohorte</th><th>Inscription</th><th>Reste</th><th>Date limite</th></tr></thead>
  <tbody>
    <?php if (empty($impayes)): ?>
      <tr><td colspan="4" class="muted">Aucune alerte.</td></tr>
    <?php else: ?>
      <?php foreach ($impayes as $r): ?>
        <tr>
          <td><?= htmlspecialchars($r['cohorte']) ?></td>
          <td>#<?= (int)$r['inscription_id'] ?></td>
          <td><strong><?= htmlspecialchars((string)$r['reste_a_payer']) ?></strong></td>
          <td><?= htmlspecialchars((string)($r['date_limite_paiement'] ?? '')) ?></td>
        </tr>
      <?php endforeach; ?>
    <?php endif; ?>
  </tbody>
</table>

<p style="margin-top:12px;">
  <a href="<?= url('/admin/finance/export/cohortes') ?>" class="btn">Exporter CSV (Finance par cohorte)</a>
</p>

<h3 style="margin-top:18px;">Finance — KPI par cohorte</h3>

<table>
  <thead>
    <tr>
      <th>Cohorte</th>
      <th>Statut</th>
      <th>Inscrits</th>
      <th>Snapshots</th>
      <th>À encaisser (net)</th>
      <th>Encaissé</th>
      <th>Reste</th>
      <th>Bourses</th>
      <th>Retards</th>
      <th>Date limite</th>
    </tr>
  </thead>
  <tbody>
    <?php if (empty($financeCohortes)): ?>
      <tr><td colspan="10" class="muted">Aucune donnée.</td></tr>
    <?php else: ?>
      <?php foreach ($financeCohortes as $r): ?>
        <tr>
          <!-- <td><?= htmlspecialchars($r['cohorte']) ?></td> -->
          <td>
            <a href="<?= url('/admin/finance/paiements?cohorte_id='.(int)$r['cohorte_id']) ?>">
              <?= htmlspecialchars($r['cohorte']) ?>
            </a>
          </td> 
          <td><span class="badge"><?= htmlspecialchars($r['statut']) ?></span></td>
          <td><?= (int)$r['nb_inscrits'] ?></td>
          <td><?= (int)$r['nb_snapshots'] ?></td>
          <td><?= htmlspecialchars((string)$r['total_a_encaisser']) ?></td>
          <td><?= htmlspecialchars((string)$r['total_encaisse']) ?></td>
          <td><strong><?= htmlspecialchars((string)$r['total_restant']) ?></strong></td>
          <td><?= htmlspecialchars((string)$r['total_bourses']) ?></td>
          <td><?= (int)$r['nb_retards'] ?></td>
          <td><?= htmlspecialchars((string)($r['date_limite_paiement'] ?? '')) ?></td>
        </tr>
      <?php endforeach; ?>
    <?php endif; ?>
  </tbody>
</table>


<p class="muted" style="margin-top:10px;">
  Pour agir : menu <strong>Vivier (Admin) / Vivier formateurs</strong>.
</p>

