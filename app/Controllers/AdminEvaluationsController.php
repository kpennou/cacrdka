<?php
class AdminEvaluationsController extends Controller
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

    $cohorte_id = (int)($_GET['cohorte_id'] ?? 0);
    $metier_id  = (int)($_GET['metier_id'] ?? 0); // pour filtrer matières

    $cohortes = $pdo->query("SELECT id, libelle AS nom FROM cohortes ORDER BY id DESC")->fetchAll();

    $metiers  = $pdo->query("SELECT id, nom FROM metiers WHERE is_active=1 ORDER BY nom")->fetchAll();

    // matières (filtrées par métier si choisi)
    $sqlMat = "SELECT id, nom, metier_id FROM matieres WHERE is_active=1";
    $pMat = [];
    if ($metier_id > 0) { $sqlMat .= " AND metier_id=?"; $pMat[] = $metier_id; }
    $sqlMat .= " ORDER BY nom";
    $stMat = $pdo->prepare($sqlMat);
    $stMat->execute($pMat);
    $matieres = $stMat->fetchAll();

    // liste évaluations + KPI
    $sql = "SELECT * FROM vw_eval_kpi";
    $p = [];
    if ($cohorte_id > 0) { $sql .= " WHERE cohorte_id=?"; $p[] = $cohorte_id; }
    $sql .= " ORDER BY evaluation_id DESC LIMIT 200";
    $st = $pdo->prepare($sql);
    $st->execute($p);

    $this->view('admin_evaluations/index', [
      'rows' => $st->fetchAll(),
      'cohortes' => $cohortes,
      'metiers' => $metiers,
      'matieres' => $matieres,
      'filters' => ['cohorte_id'=>$cohorte_id,'metier_id'=>$metier_id],
      'error' => Session::flash('error'),
      'success' => Session::flash('success'),
    ]);
  }

  public function create(): void {
    Auth::requireRole(['DIRECTEUR','ADMIN']);
    $this->csrfOrFail('/admin/evaluations');

    $cohorte_id = (int)($_POST['cohorte_id'] ?? 0);
    $matiere_id = (int)($_POST['matiere_id'] ?? 0);
    $libelle    = trim((string)($_POST['libelle'] ?? ''));
    $date_eval  = trim((string)($_POST['date_eval'] ?? ''));
    $note_sur   = (float)($_POST['note_sur'] ?? 20);

    if ($cohorte_id<=0 || $matiere_id<=0 || $libelle==='') {
      Session::flash('error', 'Cohorte, matière et libellé sont obligatoires.');
      redirect('/admin/evaluations');
    }
    if ($note_sur <= 0) $note_sur = 20;

    $pdo = DB::pdo();

    // Cohorte existe ?
    $c = $pdo->prepare("SELECT id FROM cohortes WHERE id=?");
    $c->execute([$cohorte_id]);
    if (!$c->fetch()) { Session::flash('error','Cohorte invalide.'); redirect('/admin/evaluations'); }

    // Matière active ?
    $m = $pdo->prepare("SELECT id FROM matieres WHERE id=? AND is_active=1");
    $m->execute([$matiere_id]);
    if (!$m->fetch()) { Session::flash('error','Matière invalide/inactive.'); redirect('/admin/evaluations'); }

    $ins = $pdo->prepare("
      INSERT INTO evaluations (cohorte_id, matiere_id, libelle, date_eval, note_sur, statut)
      VALUES (?,?,?,?,?, 'BROUILLON')
    ");
    $ins->execute([$cohorte_id,$matiere_id,$libelle, ($date_eval!==''?$date_eval:null), $note_sur]);

    Session::flash('success','Évaluation créée (BROUILLON).');
    redirect('/admin/evaluations?cohorte_id='.$cohorte_id);
  }

  public function toggleStatut(): void {
    Auth::requireRole(['DIRECTEUR','ADMIN']);
    $this->csrfOrFail('/admin/evaluations');

    $id = (int)($_POST['id'] ?? 0);
    if ($id<=0) { Session::flash('error','ID invalide.'); redirect('/admin/evaluations'); }

    // Cycle : BROUILLON -> PUBLIEE -> CLOTUREE -> BROUILLON
    DB::pdo()->prepare("
      UPDATE evaluations
      SET statut = CASE statut
        WHEN 'BROUILLON' THEN 'PUBLIEE'
        WHEN 'PUBLIEE' THEN 'CLOTUREE'
        ELSE 'BROUILLON'
      END
      WHERE id=?
      LIMIT 1
    ")->execute([$id]);

    Session::flash('success','Statut évaluation mis à jour.');
    redirect('/admin/evaluations');
  }

  public function notesForm(): void {
    Auth::requireRole(['DIRECTEUR','ADMIN']);
    $pdo = DB::pdo();

    $evaluation_id = (int)($_GET['id'] ?? 0);
    if ($evaluation_id<=0) redirect('/admin/evaluations');

    $e = $pdo->prepare("SELECT * FROM vw_evaluations_list WHERE id=?");
    $e->execute([$evaluation_id]);
    $eval = $e->fetch();
    if (!$eval) redirect('/admin/evaluations');

    // Liste apprenants inscrits à la cohorte
    $st = $pdo->prepare("
      SELECT a.id AS apprenant_id,
             CONCAT(a.nom,' ',a.prenoms) AS apprenant_nom,
             n.note, n.absent, n.remarque
      FROM inscriptions i
      JOIN apprenants a ON a.id=i.apprenant_id
      LEFT JOIN notes n ON n.evaluation_id=? AND n.apprenant_id=a.id
      WHERE i.cohorte_id=?
      ORDER BY a.nom, a.prenoms
    ");
    $st->execute([$evaluation_id, $eval['cohorte_id']]);
    $lignes = $st->fetchAll();

    $this->view('admin_evaluations/notes', [
      'eval' => $eval,
      'lignes' => $lignes,
      'error' => Session::flash('error'),
      'success' => Session::flash('success'),
    ]);
  }

  public function notesSave(): void {
    Auth::requireRole(['DIRECTEUR','ADMIN']);
    $this->csrfOrFail('/admin/evaluations');

    $pdo = DB::pdo();

    $evaluation_id = (int)($_POST['evaluation_id'] ?? 0);
    if ($evaluation_id<=0) { Session::flash('error','Évaluation invalide.'); redirect('/admin/evaluations'); }

    $e = $pdo->prepare("SELECT id, note_sur FROM evaluations WHERE id=?");
    $e->execute([$evaluation_id]);
    $eval = $e->fetch();
    if (!$eval) { Session::flash('error','Évaluation introuvable.'); redirect('/admin/evaluations'); }

    $noteSur = (float)$eval['note_sur'];

    $notes = $_POST['note'] ?? [];
    $abs = $_POST['absent'] ?? [];
    $rem = $_POST['remarque'] ?? [];

    $pdo->beginTransaction();
    try {
      $upsert = $pdo->prepare("
        INSERT INTO notes (evaluation_id, apprenant_id, note, absent, remarque)
        VALUES (?,?,?,?,?)
        ON DUPLICATE KEY UPDATE
          note=VALUES(note),
          absent=VALUES(absent),
          remarque=VALUES(remarque),
          updated_at=NOW()
      ");

      foreach ($notes as $apprenant_id_str => $val) {
        $apprenant_id = (int)$apprenant_id_str;
        if ($apprenant_id<=0) continue;

        $isAbsent = isset($abs[$apprenant_id_str]) ? 1 : 0;
        $remarque = trim((string)($rem[$apprenant_id_str] ?? ''));

        $note = null;
        $val = trim((string)$val);

        if ($isAbsent === 1) {
          $note = null;
        } else if ($val !== '') {
          $n = (float)$val;
          if ($n < 0) $n = 0;
          if ($n > $noteSur) $n = $noteSur;
          $note = $n;
        } else {
          $note = null; // non saisi
        }

        $upsert->execute([$evaluation_id, $apprenant_id, $note, $isAbsent, ($remarque!==''?$remarque:null)]);
      }

      $pdo->commit();
      Session::flash('success','Notes enregistrées.');
    } catch (Throwable $e) {
      $pdo->rollBack();
      Session::flash('error','Erreur enregistrement notes: '.$e->getMessage());
    }

    redirect('/admin/evaluations/notes?id='.$evaluation_id);
  }
}
