<?php
class AdminEquipeFormateursController extends Controller
{
  private function csrfOrFail(): void {
    if (!Csrf::validate($_POST['csrf_token'] ?? null)) {
      Session::flash('error', 'CSRF: session expirée.');
      redirect('/admin/formateurs');
    }
  }

  public function index(): void {
    Auth::requireRole(['DIRECTEUR','ADMIN']);
    $pdo = DB::pdo();

    // filtres
    $metier_id = (int)($_GET['metier_id'] ?? 0);
    $statut = trim((string)($_GET['statut'] ?? '')); // ACTIF / INACTIF / ''

    // options filtre
    $metiers = $pdo->query("SELECT id, nom FROM metiers WHERE is_active=1 ORDER BY nom")->fetchAll();

    $where = [];
    $params = [];

    if ($metier_id > 0) { $where[] = "metier_id=?"; $params[] = $metier_id; }
    if (in_array($statut, ['ACTIF','INACTIF'], true)) { $where[] = "statut=?"; $params[] = $statut; }

    $sql = "SELECT * FROM vw_formateurs_list";
    if ($where) $sql .= " WHERE " . implode(" AND ", $where);
    $sql .= " ORDER BY created_at DESC LIMIT 500";

    $q = $pdo->prepare($sql);
    $q->execute($params);
    $rows = $q->fetchAll();

    $this->view('admin_equipe_formateurs/index', [
      'rows' => $rows,
      'metiers' => $metiers,
      'filters' => [
        'metier_id' => $metier_id,
        'statut' => $statut,
      ],
      'error' => Session::flash('error'),
      'success' => Session::flash('success'),
    ]);
  }

  public function toggleStatut(): void {
    Auth::requireRole(['DIRECTEUR','ADMIN']);
    $this->csrfOrFail();

    $id = (int)($_POST['id'] ?? 0);
    if ($id <= 0) {
      Session::flash('error', 'ID invalide.');
      redirect('/admin/formateurs');
    }

    $pdo = DB::pdo();

    // Toggle safe : ACTIF <-> INACTIF
    $pdo->prepare("
      UPDATE formateurs
      SET statut = IF(statut='ACTIF','INACTIF','ACTIF')
      WHERE id=?
      LIMIT 1
    ")->execute([$id]);

    Session::flash('success', 'Statut mis à jour.');
    redirect('/admin/formateurs');
  }
}
