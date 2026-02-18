<?php
class FormateurCandidatController extends Controller {
  private function preId(): ?int { return Session::get('formateur_pre_id'); }

  public function accessForm(): void {
    $this->view('formateur/access', [
      'error' => Session::flash('error'),
      'success' => Session::flash('success'),
    ]);
  }

  public function accessSubmit(): void {
    if (!Csrf::validate($_POST['csrf_token'] ?? null)) {
      Session::flash('error', 'CSRF: session expirée.');
      redirect('/formateur/acces');
    }

    $tel = trim((string)($_POST['telephone'] ?? ''));
    $nom = trim((string)($_POST['nom'] ?? '')); // nom famille

    if ($tel==='' || $nom==='') {
      Session::flash('error', 'Téléphone et nom requis.');
      redirect('/formateur/acces');
    }

    $pdo = DB::pdo();
    $st = $pdo->prepare("
      SELECT id
      FROM preinscriptions_formateurs
      WHERE telephone=? AND nom=?
      ORDER BY created_at DESC
      LIMIT 1
    ");
    $st->execute([$tel,$nom]);
    $row = $st->fetch();

    if (!$row) {
      Session::flash('error', 'Dossier introuvable. Vérifiez téléphone + nom.');
      redirect('/formateur/acces');
    }

    Session::set('formateur_pre_id', (int)$row['id']);
    redirect('/formateur/dossier');
  }

  public function dossier(): void {
    $id = $this->preId();
    if (!$id) redirect('/formateur/acces');

    $pdo = DB::pdo();
    $q = $pdo->prepare("SELECT * FROM preinscriptions_formateurs WHERE id=?");
    $q->execute([$id]);
    $dossier = $q->fetch();
    if (!$dossier) { Session::forget('formateur_pre_id'); redirect('/formateur/acces'); }

    $comp = $pdo->prepare("SELECT * FROM vw_formateur_completude WHERE preinscription_id=?");
    $comp->execute([$id]);
    $completude = $comp->fetch() ?: ['is_complet'=>0,'nb_pieces_obligatoires'=>0,'nb_pieces_fournies'=>0];

    $types = $pdo->query("SELECT id, libelle, is_obligatoire, ordre FROM formateur_piece_types WHERE is_active=1 ORDER BY ordre")->fetchAll();

    $pieces = $pdo->prepare("SELECT piece_type_id FROM formateur_pieces WHERE preinscription_id=?");
    $pieces->execute([$id]);
    $have = [];
    foreach ($pieces->fetchAll() as $r) $have[(int)$r['piece_type_id']] = true;

    $this->view('formateur/dossier', [
      'dossier'=>$dossier,
      'types'=>$types,
      'have'=>$have,
      'completude'=>$completude,
      'error'=>Session::flash('error'),
      'success'=>Session::flash('success'),
    ]);
  }

  public function uploadPiece(): void {
    $id = $this->preId();
    if (!$id) redirect('/formateur/acces');

    if (!Csrf::validate($_POST['csrf_token'] ?? null)) {
      Session::flash('error', 'CSRF: session expirée.');
      redirect('/formateur/dossier');
    }

    $type = (int)($_POST['piece_type_id'] ?? 0);
    if ($type<=0 || !isset($_FILES['piece'])) {
      Session::flash('error', 'Pièce invalide.');
      redirect('/formateur/dossier');
    }

    $pdo = DB::pdo();
    $st = $pdo->prepare("SELECT statut FROM preinscriptions_formateurs WHERE id=?");
    $st->execute([$id]);
    $s = $st->fetch();
    if (!$s || $s['statut'] !== 'BROUILLON') {
      Session::flash('error', 'Dossier verrouillé (lecture seule).');
      redirect('/formateur/dossier');
    }

    $cfg = require __DIR__ . '/../../config/config.php';
    $max = (int)$cfg['max_upload_mb'] * 1024 * 1024;
    $dest = rtrim($cfg['upload_dir'], '/\\') . DIRECTORY_SEPARATOR . 'formateurs' . DIRECTORY_SEPARATOR . $id;

    $res = Upload::save($_FILES['piece'], $dest, ['pdf','jpg','jpeg','png'], $max);
    if (!$res['ok']) {
      Session::flash('error', $res['error']);
      redirect('/formateur/dossier');
    }

    $path = str_replace('\\','/', $res['path']);
    $orig = $res['original'];

    // upsert
    $ex = $pdo->prepare("SELECT id FROM formateur_pieces WHERE preinscription_id=? AND piece_type_id=?");
    $ex->execute([$id,$type]);
    $row = $ex->fetch();

    if ($row) {
      $upd = $pdo->prepare("UPDATE formateur_pieces SET fichier_path=?, fichier_nom_original=?, uploaded_at=NOW() WHERE id=?");
      $upd->execute([$path,$orig,(int)$row['id']]);
    } else {
      $ins = $pdo->prepare("INSERT INTO formateur_pieces (preinscription_id,piece_type_id,fichier_path,fichier_nom_original) VALUES (?,?,?,?)");
      $ins->execute([$id,$type,$path,$orig]);
    }

    Session::flash('success', 'Pièce enregistrée.');
    redirect('/formateur/dossier');
  }

  public function soumettre(): void {
    $id = $this->preId();
    if (!$id) redirect('/formateur/acces');

    if (!Csrf::validate($_POST['csrf_token'] ?? null)) {
      Session::flash('error', 'CSRF: session expirée.');
      redirect('/formateur/dossier');
    }

    $pdo = DB::pdo();
    $comp = $pdo->prepare("SELECT is_complet, nb_pieces_obligatoires, nb_pieces_fournies FROM vw_formateur_completude WHERE preinscription_id=?");
    $comp->execute([$id]);
    $c = $comp->fetch();

    if (!$c || (int)$c['is_complet'] !== 1) {
      Session::flash('error', "CV manquant ({$c['nb_pieces_fournies']}/{$c['nb_pieces_obligatoires']}).");
      redirect('/formateur/dossier');
    }

    $pdo->prepare("UPDATE preinscriptions_formateurs SET statut='SOUMIS', is_locked=1, submitted_at=NOW() WHERE id=? AND statut='BROUILLON'")
        ->execute([$id]);

    Session::flash('success', 'Dossier soumis. Lecture seule.');
    redirect('/formateur/dossier');
  }
}
