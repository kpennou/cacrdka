<?php
class AdminCohortesController extends Controller
{
  private function csrfOrFail(string $redir): void {
    if (!Csrf::validate($_POST['csrf_token'] ?? null)) {
      Session::flash('error', 'CSRF: session expirée.');
      redirect($redir);
    }
  }

  public function index(): void {
    Auth::requireRole(['DIRECTEUR','ADMIN']);
    $pdo = DB::pdo();

    $rows = $pdo->query("
      SELECT c.*, m.nom AS metier
      FROM cohortes c
      JOIN metiers m ON m.id=c.metier_id
      ORDER BY c.id DESC
      LIMIT 300
    ")->fetchAll();

    $this->view('admin_cohortes/index', [
      'rows'=>$rows,
      'error'=>Session::flash('error'),
      'success'=>Session::flash('success'),
    ]);
  }

  public function create(): void {
    Auth::requireRole(['DIRECTEUR','ADMIN']);
    $pdo = DB::pdo();

    $metiers = $pdo->query("SELECT id, nom FROM metiers ORDER BY nom ASC")->fetchAll();

    $this->view('admin_cohortes/create', [
      'metiers'=>$metiers,
      'error'=>Session::flash('error'),
      'success'=>Session::flash('success'),
    ]);
  }

  public function store(): void {
    Auth::requireRole(['DIRECTEUR','ADMIN']);
    $this->csrfOrFail('/admin/cohortes/create');

    $pdo = DB::pdo();

    $metier_id = (int)($_POST['metier_id'] ?? 0);
    $libelle = trim((string)($_POST['libelle'] ?? ''));
    $date_debut = trim((string)($_POST['date_debut'] ?? ''));
    $date_fin = trim((string)($_POST['date_fin'] ?? ''));
    $capacite = trim((string)($_POST['capacite'] ?? ''));
    $statut = trim((string)($_POST['statut'] ?? 'PLANIFIEE'));

    if ($metier_id<=0 || $libelle==='' || $date_debut==='' || $date_fin==='') {
      Session::flash('error', 'Métier, libellé, date début et date fin sont requis.');
      redirect('/admin/cohortes/create');
    }

    $allowed = ['PLANIFIEE','EN_COURS','CLOTUREE'];
    if (!in_array($statut,$allowed,true)) $statut = 'PLANIFIEE';

    $cap = null;
    if ($capacite !== '') {
      $cap = (int)$capacite;
      if ($cap <= 0) $cap = null;
    }

    $st = $pdo->prepare("
      INSERT INTO cohortes (metier_id, libelle, date_debut, date_fin, capacite, statut)
      VALUES (?,?,?,?,?,?)
    ");
    $st->execute([$metier_id, $libelle, $date_debut, $date_fin, $cap, $statut]);

    $id = (int)$pdo->lastInsertId();

    Session::flash('success', "Cohorte créée (#$id).");
    redirect('/admin/cohortes');
  }

  public function edit(): void {
    Auth::requireRole(['DIRECTEUR','ADMIN']);
    $pdo = DB::pdo();

    $id = (int)($_GET['id'] ?? 0);
    if ($id<=0) redirect('/admin/cohortes');

    $c = $pdo->prepare("SELECT * FROM cohortes WHERE id=?");
    $c->execute([$id]);
    $cohorte = $c->fetch();
    if (!$cohorte) redirect('/admin/cohortes');

    $metiers = $pdo->query("SELECT id, nom FROM metiers ORDER BY nom ASC")->fetchAll();

    $this->view('admin_cohortes/edit', [
      'cohorte'=>$cohorte,
      'metiers'=>$metiers,
      'error'=>Session::flash('error'),
      'success'=>Session::flash('success'),
    ]);
  }

  public function update(): void {
    Auth::requireRole(['DIRECTEUR','ADMIN']);
    $this->csrfOrFail('/admin/cohortes');

    $pdo = DB::pdo();

    $id = (int)($_POST['id'] ?? 0);
    $metier_id = (int)($_POST['metier_id'] ?? 0);
    $libelle = trim((string)($_POST['libelle'] ?? ''));
    $date_debut = trim((string)($_POST['date_debut'] ?? ''));
    $date_fin = trim((string)($_POST['date_fin'] ?? ''));
    $capacite = trim((string)($_POST['capacite'] ?? ''));
    $statut = trim((string)($_POST['statut'] ?? 'PLANIFIEE'));

    if ($id<=0 || $metier_id<=0 || $libelle==='' || $date_debut==='' || $date_fin==='') {
      Session::flash('error', 'Champs requis manquants.');
      redirect('/admin/cohortes/edit?id='.$id);
    }

    $allowed = ['PLANIFIEE','EN_COURS','CLOTUREE'];
    if (!in_array($statut,$allowed,true)) $statut = 'PLANIFIEE';

    $cap = null;
    if ($capacite !== '') {
      $cap = (int)$capacite;
      if ($cap <= 0) $cap = null;
    }

    $st = $pdo->prepare("
      UPDATE cohortes
      SET metier_id=?, libelle=?, date_debut=?, date_fin=?, capacite=?, statut=?
      WHERE id=?
      LIMIT 1
    ");
    $st->execute([$metier_id, $libelle, $date_debut, $date_fin, $cap, $statut, $id]);

    Session::flash('success', 'Cohorte mise à jour.');
    redirect('/admin/cohortes');
  }
}
