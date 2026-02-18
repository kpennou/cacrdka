<?php
class Auth {
  public static function user(): ?array { return Session::get('auth_user'); }
  public static function check(): bool { return self::user() !== null; }

  public static function login(array $u): void {
    Session::set('auth_user', [
      'id' => $u['id'],
      'username' => $u['username'],
      'nom' => $u['nom'],
      'prenoms' => $u['prenoms'] ?? '',
      'role_code' => $u['role_code'],
    ]);
  }

  public static function logout(): void { Session::forget('auth_user'); }

  public static function requireAuth(): void {
    if (!self::check()) { header('Location: /login'); exit; }
  }

  public static function requireRole(array $roles): void {
    self::requireAuth();
    $r = self::user()['role_code'] ?? '';
    if (!in_array($r, $roles, true)) {
      http_response_code(403);
      exit('403 Accès interdit');
    }
  }
}
