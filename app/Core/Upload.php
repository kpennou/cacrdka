<?php
class Upload {
  public static function save(array $file, string $destDir, array $allowedExt, int $maxBytes): array {
    if (!isset($file['error']) || is_array($file['error'])) return ['ok'=>false,'error'=>'Fichier invalide'];
    if ($file['error'] !== UPLOAD_ERR_OK) return ['ok'=>false,'error'=>'Erreur upload: '.$file['error']];
    if (($file['size'] ?? 0) > $maxBytes) return ['ok'=>false,'error'=>'Fichier trop volumineux'];

    $original = $file['name'] ?? 'fichier';
    $ext = strtolower(pathinfo($original, PATHINFO_EXTENSION));
    if (!in_array($ext, $allowedExt, true)) return ['ok'=>false,'error'=>'Extension non autorisée'];

    if (!is_dir($destDir)) {
      if (!mkdir($destDir, 0775, true) && !is_dir($destDir)) return ['ok'=>false,'error'=>'Impossible de créer le dossier upload'];
    }

    $safe = bin2hex(random_bytes(16)).'.'.$ext;
    $dest = rtrim($destDir, '/\\') . DIRECTORY_SEPARATOR . $safe;

    if (!move_uploaded_file($file['tmp_name'], $dest)) return ['ok'=>false,'error'=>'Impossible de déplacer le fichier'];

    return ['ok'=>true,'path'=>$dest,'original'=>$original,'mime'=>($file['type'] ?? null),'size'=>(int)($file['size'] ?? 0)];
  }
}
