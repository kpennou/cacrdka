<?php
class CandidatController extends Controller {
  private function preId(): ?int { return Session::get('candidat_pre_id'); }

  public function accessForm(): void {
    $this->view('candidat/access', [
      'error'=>Session::flash('error'),
      'success'=>Session::flash('success'),
    ]);
  }

  public function accessSubmit(): void {
    if (!Csrf::validate($_POST['csrf_token'] ?? null)) {
      Session::flash('error', 'CSRF: session expirée.');
      redirect('/candidat/acces');
    }

    $tel = trim((string)($_POST['telephone'] ?? ''));
    $nom = trim((string)($_POST['nom'] ?? '')); // NOM DE FAMILLE

    if ($tel==='' || $nom==='') {
      Session::flash('error', 'Téléphone et nom requis.');
      redirect('/candidat/acces');
    }

    $pdo = DB::pdo();
    $st = $pdo->prepare("
      SELECT id
      FROM preinscriptions_apprenants
      WHERE telephone=? AND nom=?
      ORDER BY created_at DESC
      LIMIT 1
    ");
    $st->execute([$tel, $nom]);
    $row = $st->fetch();

    if (!$row) {
      Session::flash('error', 'Dossier introuvable. Vérifiez téléphone + nom.');
      redirect('/candidat/acces');
    }

    Session::set('candidat_pre_id', (int)$row['id']);
    redirect('/candidat/dossier');
  }

  public function dossier(): void {
    $id = $this->preId();
    if (!$id) { redirect('/candidat/acces'); }

    $pdo = DB::pdo();
    $d = $pdo->prepare("
      SELECT p.*, m.nom AS metier_nom
      FROM preinscriptions_apprenants p
      JOIN metiers m ON m.id=p.metier_id
      WHERE p.id=?
    ");
    $d->execute([$id]);
    $dossier = $d->fetch();

    if (!$dossier) {
      Session::forget('candidat_pre_id');
      redirect('/candidat/acces');
    }

    $comp = $pdo->prepare("SELECT * FROM vw_preinscription_completude WHERE preinscription_id=?");
    $comp->execute([$id]);
    $completude = $comp->fetch() ?: ['is_complet'=>0,'nb_pieces_obligatoires'=>0,'nb_pieces_fournies'=>0];

    $types = $pdo->query("SELECT id, libelle, is_obligatoire, ordre FROM preinscription_piece_types WHERE is_active=1 ORDER BY ordre")->fetchAll();

    $pieces = $pdo->prepare("SELECT piece_type_id FROM preinscription_pieces WHERE preinscription_id=?");
    $pieces->execute([$id]);
    $have = [];
    foreach ($pieces->fetchAll() as $r) $have[(int)$r['piece_type_id']] = true;

    $this->view('candidat/dossier', [
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
    if (!$id) { redirect('/candidat/acces'); }

    if (!Csrf::validate($_POST['csrf_token'] ?? null)) {
      Session::flash('error', 'CSRF: session expirée.');
      redirect('/candidat/dossier');
    }

    $type = (int)($_POST['piece_type_id'] ?? 0);
    if ($type<=0 || !isset($_FILES['piece'])) {
      Session::flash('error', 'Pièce invalide.');
      redirect('/candidat/dossier');
    }

    $pdo = DB::pdo();
    $st = $pdo->prepare("SELECT statut FROM preinscriptions_apprenants WHERE id=?");
    $st->execute([$id]);
    $s = $st->fetch();

    if (!$s || $s['statut'] !== 'BROUILLON') {
      Session::flash('error', 'Dossier verrouillé (lecture seule).');
      redirect('/candidat/dossier');
    }

    $cfg = require __DIR__ . '/../../config/config.php';
    $max = (int)$cfg['max_upload_mb'] * 1024 * 1024;
    $dest = rtrim($cfg['upload_dir'], '/\\') . DIRECTORY_SEPARATOR . 'preinscriptions' . DIRECTORY_SEPARATOR . $id;

    $res = Upload::save($_FILES['piece'], $dest, ['pdf','jpg','jpeg','png'], $max);
    if (!$res['ok']) {
      Session::flash('error', $res['error']);
      redirect('/candidat/dossier');
    }

    $path = str_replace('\\','/', $res['path']);
    $orig = $res['original'];

    // upsert (1 pièce / type)
    $ex = $pdo->prepare("SELECT id FROM preinscription_pieces WHERE preinscription_id=? AND piece_type_id=?");
    $ex->execute([$id,$type]);
    $row = $ex->fetch();

    if ($row) {
      $upd = $pdo->prepare("UPDATE preinscription_pieces SET fichier_path=?, fichier_nom_original=?, uploaded_at=NOW() WHERE id=?");
      $upd->execute([$path,$orig,(int)$row['id']]);
    } else {
      $ins = $pdo->prepare("INSERT INTO preinscription_pieces (preinscription_id,piece_type_id,fichier_path,fichier_nom_original) VALUES (?,?,?,?)");
      $ins->execute([$id,$type,$path,$orig]);
    }

    Session::flash('success', 'Pièce enregistrée.');
    redirect('/candidat/dossier');
  }

  public function soumettre(): void {
    $id = $this->preId();
    if (!$id) { redirect('/candidat/acces'); }

    if (!Csrf::validate($_POST['csrf_token'] ?? null)) {
      Session::flash('error', 'CSRF: session expirée.');
      redirect('/candidat/dossier');
    }

    $pdo = DB::pdo();
    $comp = $pdo->prepare("SELECT is_complet, nb_pieces_obligatoires, nb_pieces_fournies FROM vw_preinscription_completude WHERE preinscription_id=?");
    $comp->execute([$id]);
    $c = $comp->fetch();

    if (!$c || (int)$c['is_complet'] !== 1) {
      Session::flash('error', "Pièces manquantes ({$c['nb_pieces_fournies']}/{$c['nb_pieces_obligatoires']}).");
      redirect('/candidat/dossier');
    }

    $pdo->prepare("UPDATE preinscriptions_apprenants SET statut='SOUMIS', is_locked=1, submitted_at=NOW() WHERE id=? AND statut='BROUILLON'")
        ->execute([$id]);

    Session::flash('success', 'Dossier soumis. Il est maintenant en lecture seule.');
    redirect('/candidat/dossier');
  }
}
