<?php
class AdminMatieresController extends Controller
{
  private function csrfOrFail(string $redir = '/admin/matieres'): void {
    if (!Csrf::validate($_POST['csrf_token'] ?? null)) {
      Session::flash('error', 'CSRF: session expirée.');
      redirect($redir);
    }
  }

  public function index(): void {
    Auth::requireRole(['DIRECTEUR','ADMIN']);
    $pdo = DB::pdo();

    $metier_id = (int)($_GET['metier_id'] ?? 0);

    $metiers = $pdo->query("SELECT id, nom FROM metiers WHERE is_active=1 ORDER BY nom")->fetchAll();

    $sql = "SELECT * FROM vw_matieres_list";
    $params = [];
    if ($metier_id > 0) { $sql .= " WHERE metier_id=?"; $params[] = $metier_id; }
    $sql .= " ORDER BY metier_nom, nom";

    $q = $pdo->prepare($sql);
    $q->execute($params);

    $this->view('admin_matieres/index', [
      'metiers' => $metiers,
      'rows' => $q->fetchAll(),
      'filters' => ['metier_id' => $metier_id],
      'error' => Session::flash('error'),
      'success' => Session::flash('success'),
    ]);
  }

  public function create(): void {
    Auth::requireRole(['DIRECTEUR','ADMIN']);
    $this->csrfOrFail('/admin/matieres');

    $metier_id = (int)($_POST['metier_id'] ?? 0);
    $nom = trim((string)($_POST['nom'] ?? ''));

    if ($metier_id <= 0 || $nom === '') {
      Session::flash('error', 'Métier et nom de matière requis.');
      redirect('/admin/matieres');
    }

    $pdo = DB::pdo();

    // metier valide
    $chk = $pdo->prepare("SELECT id FROM metiers WHERE id=? AND is_active=1");
    $chk->execute([$metier_id]);
    if (!$chk->fetch()) {
      Session::flash('error', 'Métier invalide.');
      redirect('/admin/matieres');
    }

    try {
      $ins = $pdo->prepare("INSERT INTO matieres (metier_id, nom) VALUES (?,?)");
      $ins->execute([$metier_id, $nom]);
      Session::flash('success', 'Matière créée.');
    } catch (Throwable $e) {
      Session::flash('error', 'Erreur création matière (doublon ?).');
    }

    redirect('/admin/matieres?metier_id='.$metier_id);
  }

  public function toggle(): void {
    Auth::requireRole(['DIRECTEUR','ADMIN']);
    $this->csrfOrFail('/admin/matieres');

    $id = (int)($_POST['id'] ?? 0);
    if ($id <= 0) { Session::flash('error', 'ID invalide.'); redirect('/admin/matieres'); }

    DB::pdo()->prepare("UPDATE matieres SET is_active = IF(is_active=1,0,1) WHERE id=? LIMIT 1")
      ->execute([$id]);

    Session::flash('success', 'Statut matière mis à jour.');
    redirect('/admin/matieres');
  }
}
