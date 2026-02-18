<?php
class AdminVivierController extends Controller {

  private function csrfOrFail(): void {
    if (!Csrf::validate($_POST['csrf_token'] ?? null)) {
      Session::flash('error', 'CSRF: session expirée.');
      redirect('/admin/vivier-apprenants');
    }
  }

  public function index(): void {
    Auth::requireRole(['DIRECTEUR','ADMIN']);
    $pdo = DB::pdo();

    $statut = (string)($_GET['statut'] ?? 'SOUMIS');
    $allowed = ['SOUMIS','BROUILLON','SELECTIONNE','REJETE','CONVERTI'];
    if (!in_array($statut, $allowed, true)) $statut = 'SOUMIS';

    $q = $pdo->prepare("
      SELECT p.id,p.nom,p.prenoms,p.telephone,p.statut,
             m.nom AS metier_nom, c.is_complet,
             COALESCE(p.submitted_at,p.created_at) AS d
      FROM preinscriptions_apprenants p
      JOIN metiers m ON m.id=p.metier_id
      JOIN vw_preinscription_completude c ON c.preinscription_id=p.id
      WHERE p.statut=?
      ORDER BY d DESC
      LIMIT 300
    ");
    $q->execute([$statut]);

    $this->view('admin/vivier_index', [
      'rows'=>$q->fetchAll(),
      'statut'=>$statut,
      'error'=>Session::flash('error'),
      'success'=>Session::flash('success'),
    ]);
  }

  public function voir(): void {
    Auth::requireRole(['DIRECTEUR','ADMIN']);
    $id = (int)($_GET['id'] ?? 0);
    if ($id<=0) { redirect('/admin/vivier-apprenants'); }

    $pdo = DB::pdo();
    $q = $pdo->prepare("
      SELECT p.*, m.nom AS metier_nom
      FROM preinscriptions_apprenants p
      JOIN metiers m ON m.id=p.metier_id
      WHERE p.id=?
    ");
    $q->execute([$id]);
    $dossier = $q->fetch();
    if (!$dossier) { redirect('/admin/vivier-apprenants'); }

    $comp = $pdo->prepare("SELECT * FROM vw_preinscription_completude WHERE preinscription_id=?");
    $comp->execute([$id]);
    $completude = $comp->fetch() ?: ['is_complet'=>0,'nb_pieces_obligatoires'=>0,'nb_pieces_fournies'=>0];

    $qp = $pdo->prepare("SELECT * FROM vw_preinscription_pieces_list WHERE preinscription_id=? ORDER BY uploaded_at DESC");
    $qp->execute([$id]);
    $pieces = $qp->fetchAll();

    $this->view('admin/vivier_voir', [
      'dossier'=>$dossier,
      'completude'=>$completude,
      'pieces'=>$pieces,
      'error'=>Session::flash('error'),
      'success'=>Session::flash('success'),
    ]);
  }

  public function selectionner(): void {
    Auth::requireRole(['DIRECTEUR','ADMIN']);
    $this->csrfOrFail();
    $id = (int)($_POST['id'] ?? 0);

    DB::pdo()->prepare("UPDATE preinscriptions_apprenants SET statut='SELECTIONNE', is_locked=1, selected_at=NOW() WHERE id=? AND statut='SOUMIS'")
      ->execute([$id]);

    Session::flash('success', 'Dossier sélectionné.');
    redirect('/admin/vivier-apprenants/voir?id='.$id);
  }

  public function rejeter(): void {
    Auth::requireRole(['DIRECTEUR','ADMIN']);
    $this->csrfOrFail();
    $id = (int)($_POST['id'] ?? 0);

    DB::pdo()->prepare("UPDATE preinscriptions_apprenants SET statut='REJETE', is_locked=1, rejected_at=NOW() WHERE id=? AND statut IN ('SOUMIS','SELECTIONNE')")
      ->execute([$id]);

    Session::flash('success', 'Dossier rejeté.');
    redirect('/admin/vivier-apprenants/voir?id='.$id);
  }

  public function convertir(): void {
    Auth::requireRole(['DIRECTEUR','ADMIN']);
    $this->csrfOrFail();
    $id = (int)($_POST['id'] ?? 0);
    $cohorte_id = (int)($_POST['cohorte_id'] ?? 0);

    if ($cohorte_id<=0) {
      Session::flash('error', 'Cohorte requise.');
      redirect('/admin/vivier-apprenants/voir?id='.$id);
    }

    $pdo = DB::pdo();
    $pre = $pdo->prepare("SELECT * FROM preinscriptions_apprenants WHERE id=? AND statut='SELECTIONNE'");
    $pre->execute([$id]);
    $p = $pre->fetch();

    if (!$p) {
      Session::flash('error', 'Le dossier doit être SELECTIONNE.');
      redirect('/admin/vivier-apprenants/voir?id='.$id);
    }

    $pdo->beginTransaction();
    try {
      $matricule = 'APP-' . str_pad((string)$id, 6, '0', STR_PAD_LEFT);
      $financeWarning = null;

      $insA = $pdo->prepare("
        INSERT INTO apprenants (matricule, nom, prenoms, telephone, date_naissance, sexe, niveau_etude)
        VALUES (?,?,?,?,?,?,?)
      ");
      $insA->execute([
        $matricule,
        $p['nom'],
        $p['prenoms'],
        $p['telephone'],
        $p['date_naissance'],
        $p['sexe'],
        $p['niveau_etude']
      ]);
      $apprenant_id = (int)$pdo->lastInsertId();

      // 1) Inscription cohorte
      $pdo->prepare("INSERT INTO inscriptions (apprenant_id, cohorte_id, date_inscription) VALUES (?,?,CURDATE())")
          ->execute([$apprenant_id,$cohorte_id]);

      $inscription_id = (int)$pdo->lastInsertId();

      // 2) FINANCE V1 (mode permissif, sans tranches)
      // On essaie de créer le snapshot inscription_finance depuis cohorte_tarifs.
      // Si cohorte_tarifs n'existe pas, on ne bloque pas la conversion, mais on avertit.
      $bourse = 0.0; // V1 : réduction par défaut / On ajoutera un écran pour modifier la bourse ensuite.

      $stFin = $pdo->prepare("
        INSERT INTO inscription_finance (inscription_id, montant_total, bourse_montant, montant_net, date_limite_paiement)
        SELECT ?, ct.montant_total, ?, (ct.montant_total - ?), ct.date_limite_paiement
        FROM cohorte_tarifs ct
        WHERE ct.cohorte_id=?
          AND NOT EXISTS (SELECT 1 FROM inscription_finance f WHERE f.inscription_id=?)
      ");

      $stFin->execute([$inscription_id, $bourse, $bourse, $cohorte_id, $inscription_id]);

      if ($stFin->rowCount() === 0) {
        $financeWarning = "Attention : tarif de la cohorte non paramétré. Finance inactive pour l’inscription #$inscription_id.";
      }

      // 3) Conversion log
      $pdo->prepare("INSERT INTO conversions (type, source_preinscription_id, cible_apprenant_id, converted_by) VALUES ('APPRENANT',?,?,?)")
          ->execute([$id,$apprenant_id,(int)(Auth::user()['id'] ?? 0)]);

      // 4) Mise à jour dossier vivier
      $pdo->prepare("UPDATE preinscriptions_apprenants SET statut='CONVERTI', converted_at=NOW() WHERE id=?")
          ->execute([$id]);

      $pdo->commit();

      Session::flash('success', "Converti en apprenant ($matricule)." . ($financeWarning ? " $financeWarning" : ""));
    } catch (Throwable $e) {
      $pdo->rollBack();
      Session::flash('error', 'Erreur conversion: '.$e->getMessage());
    }

    redirect('/admin/vivier-apprenants/voir?id='.$id);
  }
}
