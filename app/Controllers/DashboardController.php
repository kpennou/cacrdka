<?php
class DashboardController extends Controller {
  public function directeur(): void {
    Auth::requireRole(['DIRECTEUR','ADMIN']);
    $pdo = DB::pdo();

    $viviers = $pdo->query("SELECT * FROM vw_directeur_viviers_kpi")->fetch() ?: [
      'vivier_apprenants_total'=>0,
      'vivier_apprenants_brouillons'=>0,
      'vivier_apprenants_soumis'=>0,
      'vivier_apprenants_selectionnes'=>0,
      'vivier_apprenants_convertis'=>0,
      'vivier_apprenants_incomplets'=>0
    ];

    // debut ajout
    $formKpi = $pdo->query("SELECT * FROM vw_directeur_formateurs_kpi")->fetch() ?: [
      'vivier_formateurs_total'=>0,
      'vivier_formateurs_brouillons'=>0,
      'vivier_formateurs_soumis'=>0,
      'vivier_formateurs_retenus'=>0,
      'vivier_formateurs_rejetes'=>0,
      'vivier_formateurs_convertis'=>0,
      'equipe_formateurs_actifs'=>0,
      'vivier_formateurs_incomplets'=>0
    ];

    $formTop = $pdo->query("SELECT * FROM vw_kpi_formateurs_top_metiers")->fetchAll();
    $formDerniersSoumis = $pdo->query("SELECT * FROM vw_kpi_formateurs_derniers_soumis")->fetchAll();
// fin ajout
    
    $notesManquantes = $pdo->query("SELECT * FROM vw_kpi_notes_manquantes_top10")->fetchAll();




    $topMetiers = $pdo->query("SELECT * FROM vw_kpi_top_metiers")->fetchAll();
    $incomplets = $pdo->query("SELECT * FROM vw_kpi_incomplets_par_metier")->fetchAll();
    $derniersSoumis = $pdo->query("SELECT * FROM vw_kpi_derniers_soumis")->fetchAll();

    $this->view('dashboard/directeur', [
      'viviers'=>$viviers,
      'topMetiers'=>$topMetiers,
      'incomplets'=>$incomplets,
      'derniersSoumis'=>$derniersSoumis,
      'formKpi' => $formKpi,
      'formTop' => $formTop,
      'formDerniersSoumis' => $formDerniersSoumis,
      'notesManquantes' => $notesManquantes,

    ]);
  }
}
