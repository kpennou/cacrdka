<?php
class Router {
  private array $routes = ['GET'=>[], 'POST'=>[]];

  public function get(string $p, callable $h): void { $this->routes['GET'][$p] = $h; }
  public function post(string $p, callable $h): void { $this->routes['POST'][$p] = $h; }

  public function dispatch(string $method, string $uri): void {
    $path = parse_url($uri, PHP_URL_PATH) ?: '/';
    $handler = $this->routes[$method][$path] ?? null;

    if (!$handler) {
      http_response_code(404);
      echo "404 - Route introuvable";
      return;
    }
    $handler();
  }
}
