<?php
declare(strict_types=1);

function base_url(): string {
  $cfg = require __DIR__ . '/../../config/config.php';
  $u = rtrim((string)($cfg['app_url'] ?? ''), '/');
  if ($u !== '') return $u;

  // Auto-detect (Laragon/cPanel)
  $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (($_SERVER['SERVER_PORT'] ?? '') == '443');
  $scheme = $https ? 'https' : 'http';
  $host = $_SERVER['HTTP_HOST'] ?? 'localhost';

  return $scheme . '://' . $host;
}

function url(string $path = ''): string {
  $path = '/' . ltrim($path, '/');
  return base_url() . $path;
}

function redirect(string $path = ''): never {
  header('Location: ' . url($path));
  exit;
}
