<?php
class AdminVivierFormateursController extends Controller
{
  private function csrfOrFail(): void {
    if (!Csrf::validate($_POST['csrf_token'] ?? null)) {
      Session::flash('error', 'CSRF: session expirée.');
      redirect('/admin/vivier-formateurs');
    }
  }

  public function index(): void {
    Auth::requireRole(['DIRECTEUR','ADMIN']);
    $pdo = DB::pdo();

    $statut = (string)($_GET['statut'] ?? 'SOUMIS');
    $allowed = ['SOUMIS','BROUILLON','RETENU','REJETE','CONVERTI'];
    if (!in_array($statut, $allowed, true)) $statut = 'SOUMIS';

    $q = $pdo->prepare("
      SELECT
        f.id, f.nom, f.prenoms, f.telephone, f.email, f.statut,
        COALESCE(m.nom,'Autre') AS metier_nom,
        c.is_complet,
        COALESCE(f.submitted_at,f.created_at) AS d
      FROM preinscriptions_formateurs f
      LEFT JOIN metiers m ON m.id=f.metier_id
      JOIN vw_formateur_completude c ON c.preinscription_id=f.id
      WHERE f.statut=?
      ORDER BY d DESC
      LIMIT 300
    ");
    $q->execute([$statut]);

    $this->view('admin_formateurs/index', [
      'rows' => $q->fetchAll(),
      'statut' => $statut,
      'error' => Session::flash('error'),
      'success' => Session::flash('success'),
    ]);
  }

  public function voir(): void {
    Auth::requireRole(['DIRECTEUR','ADMIN']);
    $id = (int)($_GET['id'] ?? 0);
    if ($id <= 0) redirect('/admin/vivier-formateurs');

    $pdo = DB::pdo();

    $q = $pdo->prepare("
      SELECT f.*,
             COALESCE(m.nom,'Autre') AS metier_nom
      FROM preinscriptions_formateurs f
      LEFT JOIN metiers m ON m.id=f.metier_id
      WHERE f.id=?
      LIMIT 1
    ");
    $q->execute([$id]);
    $dossier = $q->fetch();
    if (!$dossier) redirect('/admin/vivier-formateurs');

    $comp = $pdo->prepare("SELECT * FROM vw_formateur_completude WHERE preinscription_id=?");
    $comp->execute([$id]);
    $completude = $comp->fetch() ?: ['is_complet'=>0,'nb_pieces_obligatoires'=>0,'nb_pieces_fournies'=>0];

    $qp = $pdo->prepare("SELECT * FROM vw_formateur_pieces_list WHERE preinscription_id=? ORDER BY uploaded_at DESC");
    $qp->execute([$id]);
    $pieces = $qp->fetchAll();

    $this->view('admin_formateurs/voir', [
      'dossier' => $dossier,
      'completude' => $completude,
      'pieces' => $pieces,
      'error' => Session::flash('error'),
      'success' => Session::flash('success'),
    ]);
  }

  public function retenir(): void {
    Auth::requireRole(['DIRECTEUR','ADMIN']);
    $this->csrfOrFail();

    $id = (int)($_POST['id'] ?? 0);

    DB::pdo()->prepare("
      UPDATE preinscriptions_formateurs
      SET statut='RETENU', is_locked=1, retenu_at=NOW()
      WHERE id=? AND statut='SOUMIS'
    ")->execute([$id]);

    Session::flash('success', 'Candidat formateur RETENU.');
    redirect('/admin/vivier-formateurs/voir?id='.$id);
  }

  public function rejeter(): void {
    Auth::requireRole(['DIRECTEUR','ADMIN']);
    $this->csrfOrFail();

    $id = (int)($_POST['id'] ?? 0);

    DB::pdo()->prepare("
      UPDATE preinscriptions_formateurs
      SET statut='REJETE', is_locked=1, rejected_at=NOW()
      WHERE id=? AND statut IN ('SOUMIS','RETENU')
    ")->execute([$id]);

    Session::flash('success', 'Candidat formateur REJETE.');
    redirect('/admin/vivier-formateurs/voir?id='.$id);
  }

  /** Conversion : RETENU -> formateurs (équipe) */
  public function convertir(): void {
    Auth::requireRole(['DIRECTEUR','ADMIN']);
    $this->csrfOrFail();

    $id = (int)($_POST['id'] ?? 0);
    if ($id <= 0) redirect('/admin/vivier-formateurs');

    $pdo = DB::pdo();

    $pre = $pdo->prepare("SELECT * FROM preinscriptions_formateurs WHERE id=? AND statut='RETENU' LIMIT 1");
    $pre->execute([$id]);
    $f = $pre->fetch();

    if (!$f) {
      Session::flash('error', 'Le dossier doit être RETENU pour être converti.');
      redirect('/admin/vivier-formateurs/voir?id='.$id);
    }

    $pdo->beginTransaction();
    try {
      $matricule = 'FOR-' . str_pad((string)$id, 6, '0', STR_PAD_LEFT);

      // éviter doublon matricule (rare)
      $chk = $pdo->prepare("SELECT id FROM formateurs WHERE matricule=? LIMIT 1");
      $chk->execute([$matricule]);
      if ($chk->fetch()) {
        throw new RuntimeException("Matricule déjà existant: $matricule");
      }

      $ins = $pdo->prepare("
        INSERT INTO formateurs (matricule, metier_id, nom, prenoms, telephone, email, specialites, statut)
        VALUES (?,?,?,?,?,?,?, 'ACTIF')
      ");
      $ins->execute([
        $matricule,
        $f['metier_id'] ?: null,
        $f['nom'],
        $f['prenoms'],
        $f['telephone'] ?: null,
        $f['email'] ?: null,
        $f['specialites'] ?: null,
      ]);

      $pdo->prepare("
        UPDATE preinscriptions_formateurs
        SET statut='CONVERTI', converted_at=NOW()
        WHERE id=?
      ")->execute([$id]);

      $pdo->commit();
      Session::flash('success', "Converti en formateur (équipe) : $matricule");
    } catch (Throwable $e) {
      $pdo->rollBack();
      Session::flash('error', 'Erreur conversion: '.$e->getMessage());
    }

    redirect('/admin/vivier-formateurs/voir?id='.$id);
  }
}
