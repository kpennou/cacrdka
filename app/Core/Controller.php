<?php
class Controller {
  protected function view(string $path, array $data = []): void {
    extract($data);
    $viewFile = __DIR__ . '/../Views/' . $path . '.php';
    if (!file_exists($viewFile)) {
      http_response_code(500);
      echo "Vue introuvable: $path";
      return;
    }
    require __DIR__ . '/../Views/layouts/main.php';
  }
}
