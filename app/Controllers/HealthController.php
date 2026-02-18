<?php
class HealthController extends Controller {
  public function index(): void {
    try {
      $pdo = DB::pdo();
      $pdo->query("SELECT 1")->fetchColumn();

      $viewsOk = true;
      try { $pdo->query("SELECT * FROM vw_directeur_viviers_kpi")->fetch(); }
      catch (Throwable $e) { $viewsOk = false; }

      header('Content-Type: application/json; charset=utf-8');
      echo json_encode([
        'ok' => $viewsOk,
        'php' => PHP_VERSION,
        'db' => 'OK',
        'views' => $viewsOk ? 'OK' : 'KO',
      ], JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT);
    } catch (Throwable $e) {
      http_response_code(500);
      header('Content-Type: application/json; charset=utf-8');
      echo json_encode(['ok'=>false,'error'=>$e->getMessage()], JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT);
    }
  }
}
