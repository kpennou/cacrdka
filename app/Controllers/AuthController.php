<?php
class AuthController extends Controller {

  public function showLogin(): void {
    $error = Session::flash('error');
    $this->view('auth/login', ['error'=>$error]);
  }

  public function login(): void {
    if (!Csrf::validate($_POST['csrf_token'] ?? null)) {
      Session::flash('error', 'CSRF: session expirée.');
      redirect('/login');
    }

    $username = trim((string)($_POST['username'] ?? ''));
    $password = (string)($_POST['password'] ?? '');

    if ($username === '' || $password === '') {
      Session::flash('error', 'Champs requis.');
      redirect('/login');
    }

    $pdo = DB::pdo();
    $stmt = $pdo->prepare("
      SELECT u.id,u.username,u.password_hash,u.nom,u.prenoms,r.code AS role_code
      FROM users u JOIN roles r ON r.id=u.role_id
      WHERE u.username=? AND u.is_active=1
      LIMIT 1
    ");
    $stmt->execute([$username]);
    $u = $stmt->fetch();

    if (!$u || !password_verify($password, $u['password_hash'])) {
      Session::flash('error', 'Identifiants invalides.');
      redirect('/login');
    }

    Auth::login($u);
    redirect('/directeur');
  }

  public function logout(): void {
    Auth::logout();
    redirect('/login');
  }
}
