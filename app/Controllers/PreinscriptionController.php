<?php
class PreinscriptionController extends Controller {

  public function createForm(): void {
    $pdo = DB::pdo();
    $metiers = $pdo->query("SELECT id, nom FROM metiers WHERE is_active=1 ORDER BY nom")->fetchAll();

    $this->view('preinscription/create', [
      'metiers'=>$metiers,
      'error'=>Session::flash('error'),
      'old'=>Session::get('old_pre', []),
    ]);
    Session::forget('old_pre');
  }

  public function store(): void {
    if (!Csrf::validate($_POST['csrf_token'] ?? null)) {
      Session::flash('error', 'CSRF: session expirée.');
      redirect('/preinscription');
    }

    $nom = trim((string)($_POST['nom'] ?? ''));               // NOM DE FAMILLE
    $prenoms = trim((string)($_POST['prenoms'] ?? ''));
    $telephone = trim((string)($_POST['telephone'] ?? ''));
    $date_naissance = trim((string)($_POST['date_naissance'] ?? ''));
    $sexe = trim((string)($_POST['sexe'] ?? ''));
    $niveau_etude = trim((string)($_POST['niveau_etude'] ?? ''));
    $metier_id = (int)($_POST['metier_id'] ?? 0);

    Session::set('old_pre', compact('nom','prenoms','telephone','date_naissance','sexe','niveau_etude','metier_id'));

    if ($nom==='' || $prenoms==='' || $telephone==='' || $date_naissance==='' || $niveau_etude==='' || $metier_id<=0) {
      Session::flash('error', 'Champs obligatoires manquants.');
      redirect('/preinscription');
    }
    if (!in_array($sexe, ['M','F'], true)) {
      Session::flash('error', 'Sexe invalide.');
      redirect('/preinscription');
    }

    $pdo = DB::pdo();
    $chk = $pdo->prepare("SELECT id FROM metiers WHERE id=? AND is_active=1");
    $chk->execute([$metier_id]);
    if (!$chk->fetch()) {
      Session::flash('error', 'Métier invalide.');
      redirect('/preinscription');
    }

    $ins = $pdo->prepare("
      INSERT INTO preinscriptions_apprenants (nom,prenoms,telephone,date_naissance,sexe,niveau_etude,metier_id)
      VALUES (?,?,?,?,?,?,?)
    ");
    $ins->execute([$nom,$prenoms,$telephone,$date_naissance,$sexe,$niveau_etude,$metier_id]);

    Session::forget('old_pre');
    Session::flash('success', 'Dossier créé. Accédez pour compléter vos pièces.');
    redirect('/candidat/acces');
  }
}
