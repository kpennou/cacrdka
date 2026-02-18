<?php
class AdminAffectationsController extends Controller
{
  private function csrfOrFail(string $redir = '/admin/affectations'): void {
    if (!Csrf::validate($_POST['csrf_token'] ?? null)) {
      Session::flash('error', 'CSRF: session expirée.');
      redirect($redir);
    }
  }

  public function index(): void {
    Auth::requireRole(['DIRECTEUR','ADMIN']);
    $pdo = DB::pdo();

    $metier_id = (int)($_GET['metier_id'] ?? 0); // filtre par métier des matières
    $formateur_id = (int)($_GET['formateur_id'] ?? 0);

    $metiers = $pdo->query("SELECT id, nom FROM metiers WHERE is_active=1 ORDER BY nom")->fetchAll();
    $formateurs = $pdo->query("
      SELECT id, matricule, nom, prenoms
      FROM formateurs
      WHERE statut='ACTIF'
      ORDER BY nom, prenoms
      LIMIT 500
    ")->fetchAll();

    // matières filtrées par métier
    $sqlMat = "SELECT id, nom, metier_id FROM matieres WHERE is_active=1";
    $paramsMat = [];
    if ($metier_id > 0) { $sqlMat .= " AND metier_id=?"; $paramsMat[] = $metier_id; }
    $sqlMat .= " ORDER BY nom";
    $matStmt = $pdo->prepare($sqlMat);
    $matStmt->execute($paramsMat);
    $matieres = $matStmt->fetchAll();

    // liste affectations
    $sql = "SELECT * FROM vw_formateur_matieres_list";
    $params = [];
    $where = [];
    if ($metier_id > 0) { $where[] = "matiere_metier_id=?"; $params[] = $metier_id; }
    if ($formateur_id > 0) { $where[] = "formateur_id=?"; $params[] = $formateur_id; }
    if ($where) $sql .= " WHERE " . implode(" AND ", $where);
    $sql .= " ORDER BY matiere_metier_nom, matiere_nom, formateur_nom";

    $q = $pdo->prepare($sql);
    $q->execute($params);
    $rows = $q->fetchAll();

    $this->view('admin_affectations/index', [
      'metiers' => $metiers,
      'formateurs' => $formateurs,
      'matieres' => $matieres,
      'rows' => $rows,
      'filters' => [
        'metier_id' => $metier_id,
        'formateur_id' => $formateur_id,
      ],
      'error' => Session::flash('error'),
      'success' => Session::flash('success'),
    ]);
  }

  public function create(): void {
    Auth::requireRole(['DIRECTEUR','ADMIN']);
    $this->csrfOrFail('/admin/affectations');

    $formateur_id = (int)($_POST['formateur_id'] ?? 0);
    $matiere_id = (int)($_POST['matiere_id'] ?? 0);

    if ($formateur_id <= 0 || $matiere_id <= 0) {
      Session::flash('error', 'Formateur et matière requis.');
      redirect('/admin/affectations');
    }

    $pdo = DB::pdo();

    // Vérif formateur actif
    $f = $pdo->prepare("SELECT id FROM formateurs WHERE id=? AND statut='ACTIF'");
    $f->execute([$formateur_id]);
    if (!$f->fetch()) {
      Session::flash('error', 'Formateur invalide ou inactif.');
      redirect('/admin/affectations');
    }

    // Vérif matière active
    $m = $pdo->prepare("SELECT id FROM matieres WHERE id=? AND is_active=1");
    $m->execute([$matiere_id]);
    if (!$m->fetch()) {
      Session::flash('error', 'Matière invalide ou inactive.');
      redirect('/admin/affectations');
    }

    try {
      $ins = $pdo->prepare("INSERT INTO formateur_matieres (formateur_id, matiere_id) VALUES (?,?)");
      $ins->execute([$formateur_id, $matiere_id]);
      Session::flash('success', 'Affectation créée.');
    } catch (Throwable $e) {
      Session::flash('error', 'Affectation déjà existante.');
    }

    redirect('/admin/affectations');
  }

  public function toggle(): void {
    Auth::requireRole(['DIRECTEUR','ADMIN']);
    $this->csrfOrFail('/admin/affectations');

    $id = (int)($_POST['id'] ?? 0);
    if ($id <= 0) { Session::flash('error','ID invalide.'); redirect('/admin/affectations'); }

    DB::pdo()->prepare("UPDATE formateur_matieres SET statut = IF(statut='ACTIF','INACTIF','ACTIF') WHERE id=? LIMIT 1")
      ->execute([$id]);

    Session::flash('success', 'Statut affectation mis à jour.');
    redirect('/admin/affectations');
  }

  public function delete(): void {
    Auth::requireRole(['DIRECTEUR','ADMIN']);
    $this->csrfOrFail('/admin/affectations');

    $id = (int)($_POST['id'] ?? 0);
    if ($id <= 0) { Session::flash('error','ID invalide.'); redirect('/admin/affectations'); }

    DB::pdo()->prepare("DELETE FROM formateur_matieres WHERE id=? LIMIT 1")->execute([$id]);

    Session::flash('success', 'Affectation supprimée.');
    redirect('/admin/affectations');
  }
}
