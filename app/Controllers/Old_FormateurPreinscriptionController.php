<?php
class FormateurPreinscriptionController extends Controller {

  public function createForm(): void {
  $pdo = DB::pdo();
  $metiers = $pdo->query("SELECT id, nom FROM metiers WHERE is_active=1 ORDER BY nom")->fetchAll();
  $this->view('formateur/preinscription_create', [
    'metiers' => $metiers,
    'error' => Session::flash('error'),
    'old' => Session::get('old_form', []),
  ]);
    Session::forget('old_form');
  }

  public function store(): void {
    if (!Csrf::validate($_POST['csrf_token'] ?? null)) {
      Session::flash('error', 'CSRF: session expirée.');
      redirect('/formateur/preinscription');
    }

    $nom        = trim((string)($_POST['nom'] ?? '')); // nom famille
    $prenoms    = trim((string)($_POST['prenoms'] ?? ''));
    $telephone  = trim((string)($_POST['telephone'] ?? ''));
    $email      = trim((string)($_POST['email'] ?? ''));
    $specialites= trim((string)($_POST['specialites'] ?? ''));

    

    Session::set('old_form', compact('nom','prenoms','telephone','email','specialites'));

    if ($nom==='' || $prenoms==='' || $telephone==='') {
      Session::flash('error', 'Nom, prénoms et téléphone sont obligatoires.');
      redirect('/formateur/preinscription');
    }

    $pdo = DB::pdo();
    $ins = $pdo->prepare("
      INSERT INTO preinscriptions_formateurs (nom, prenoms, telephone, email, specialites)
      VALUES (?,?,?,?,?)
    ");
    $ins->execute([$nom,$prenoms,$telephone,$email ?: null,$specialites ?: null]);

    Session::forget('old_form');
    Session::flash('success', 'Dossier formateur créé. Accédez pour déposer votre CV.');
    redirect('/formateur/acces');
  }
}
