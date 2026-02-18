<?php
class FormateurPreinscriptionController extends Controller
{
  public function createForm(): void
  {
    $pdo = DB::pdo();
    $metiers = $pdo->query("SELECT id, nom FROM metiers WHERE is_active=1 ORDER BY nom")->fetchAll();

    $this->view('formateur/preinscription_create', [
      'metiers' => $metiers,
      'error'   => Session::flash('error'),
      'old'     => Session::get('old_form', []),
    ]);

    Session::forget('old_form');
  }

  public function store(): void
  {
    if (!Csrf::validate($_POST['csrf_token'] ?? null)) {
      Session::flash('error', 'CSRF: session expirée.');
      redirect('/formateur/preinscription');
    }

    $pdo = DB::pdo();

    // Champs
    $metier_id_raw = trim((string)($_POST['metier_id'] ?? '')); // id ou "AUTRE"
    $nom         = trim((string)($_POST['nom'] ?? ''));         // NOM (famille)
    $prenoms     = trim((string)($_POST['prenoms'] ?? ''));
    $telephone   = trim((string)($_POST['telephone'] ?? ''));
    $email       = trim((string)($_POST['email'] ?? ''));
    $specialites = trim((string)($_POST['specialites'] ?? ''));

    // Old
    Session::set('old_form', [
      'metier_id_raw' => $metier_id_raw,
      'nom' => $nom,
      'prenoms' => $prenoms,
      'telephone' => $telephone,
      'email' => $email,
      'specialites' => $specialites,
    ]);

    // Validations de base
    if ($metier_id_raw === '' || $nom === '' || $prenoms === '' || $telephone === '') {
      Session::flash('error', 'Veuillez renseigner le métier, le nom, les prénoms et le téléphone.');
      redirect('/formateur/preinscription');
    }

    // Interprétation metier_id
    $metier_id = null;
    if ($metier_id_raw !== 'AUTRE') {
      $metier_id = (int)$metier_id_raw;
      if ($metier_id <= 0) {
        Session::flash('error', 'Métier invalide.');
        redirect('/formateur/preinscription');
      }
    }

    // Règle "Autre" => spécialités obligatoire
    if ($metier_id_raw === 'AUTRE') {
      $metier_id = null; // NULL en DB
      if ($specialites === '') {
        Session::flash('error', 'Le champ "Spécialités" est obligatoire si vous choisissez "Autre".');
        redirect('/formateur/preinscription');
      }
    } else {
      // Vérifier que le métier existe et actif
      $chk = $pdo->prepare("SELECT id FROM metiers WHERE id=? AND is_active=1");
      $chk->execute([$metier_id]);
      if (!$chk->fetch()) {
        Session::flash('error', 'Métier invalide (inexistant ou inactif).');
        redirect('/formateur/preinscription');
      }
    }

    // Insert
    $ins = $pdo->prepare("
      INSERT INTO preinscriptions_formateurs (metier_id, nom, prenoms, telephone, email, specialites)
      VALUES (?,?,?,?,?,?)
    ");
    $ins->execute([
      $metier_id,
      $nom,
      $prenoms,
      $telephone,
      ($email !== '' ? $email : null),
      ($specialites !== '' ? $specialites : null),
    ]);

    Session::forget('old_form');
    Session::flash('success', 'Dossier formateur créé. Accédez pour déposer votre CV.');
    redirect('/formateur/acces');
  }
}
