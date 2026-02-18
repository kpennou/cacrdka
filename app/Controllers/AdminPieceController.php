<?php
class AdminPieceController extends Controller {

  public function download(): void {
    Auth::requireRole(['DIRECTEUR','ADMIN']);

    $id = (int)($_GET['id'] ?? 0);
    if ($id <= 0) { http_response_code(400); exit('Bad Request'); }

    $pdo = DB::pdo();
    $q = $pdo->prepare("SELECT * FROM vw_preinscription_pieces_list WHERE piece_id=? LIMIT 1");
    $q->execute([$id]);
    $p = $q->fetch();

    if (!$p) { http_response_code(404); exit('Not Found'); }

    // Chemin fichier sur disque
    $fullPath = $p['fichier_path'];

    // Si jamais tu as stocké un chemin relatif en DB, décommente ça :
    // $fullPath = __DIR__ . '/../../' . ltrim($p['fichier_path'], '/');

    if (!is_file($fullPath)) { http_response_code(404); exit('Fichier introuvable'); }

    $downloadName = $p['fichier_nom_original'] ?: ('piece_'.$id);
    $mime = $p['mime_type'] ?: 'application/octet-stream';
    $size = filesize($fullPath);

    header('Content-Type: ' . $mime);
    header('Content-Length: ' . $size);
    header('Content-Disposition: attachment; filename="' . basename($downloadName) . '"');
    header('X-Content-Type-Options: nosniff');

    readfile($fullPath);
    exit;
  }
}
