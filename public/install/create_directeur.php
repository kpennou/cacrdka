<?php
declare(strict_types=1);

require __DIR__ . '/../../app/Core/Session.php';
require __DIR__ . '/../../app/Core/DB.php';

Session::start();

/** .env */
$envPath = __DIR__ . '/../../.env';
if (file_exists($envPath)) {
  foreach (file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
    $line = trim($line);
    if ($line === '' || $line[0] === '#') continue;
    if (strpos($line, '=') === false) continue;
    [$k,$v] = explode('=', $line, 2);
    putenv(trim($k) . '=' . trim($v));
  }
}

$pdo = DB::pdo();

$exists = $pdo->prepare("SELECT id FROM users WHERE username='directeur' LIMIT 1");
$exists->execute();
if ($exists->fetch()) {
  header('Content-Type: text/plain; charset=utf-8');
  echo "Utilisateur 'directeur' existe déjà.\n";
  exit;
}

$role = $pdo->query("SELECT id FROM roles WHERE code='DIRECTEUR'")->fetch();
if (!$role) die("Role DIRECTEUR introuvable. Importer le SQL d'abord.");

$hash = password_hash('admin123', PASSWORD_DEFAULT);

$ins = $pdo->prepare("INSERT INTO users (role_id, nom, prenoms, username, password_hash, is_active) VALUES (?,?,?,?,?,1)");
$ins->execute([(int)$role['id'], 'DIRECTEUR', 'CACRDKA', 'directeur', $hash]);

header('Content-Type: text/plain; charset=utf-8');
echo "OK. Compte créé : directeur / admin123\nSupprime ce fichier en production.\n";
