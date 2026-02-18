<?php
class Csrf {
  public static function token(): string {
    $t = Session::get('csrf_token');
    if (!$t) { $t = bin2hex(random_bytes(32)); Session::set('csrf_token', $t); }
    return $t;
  }
  public static function validate(?string $token): bool {
    return is_string($token) && hash_equals(Session::get('csrf_token',''), $token);
  }
}
