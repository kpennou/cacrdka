<?php
class Session {
  public static function start(): void {
    if (session_status() === PHP_SESSION_NONE) session_start();
  }
  public static function set(string $k, mixed $v): void { $_SESSION[$k] = $v; }
  public static function get(string $k, mixed $d = null): mixed { return $_SESSION[$k] ?? $d; }
  public static function forget(string $k): void { unset($_SESSION[$k]); }

  public static function flash(string $k, ?string $v = null): ?string {
    if ($v !== null) { $_SESSION['_flash_'.$k] = $v; return null; }
    $x = $_SESSION['_flash_'.$k] ?? null;
    unset($_SESSION['_flash_'.$k]);
    return $x;
  }
}
