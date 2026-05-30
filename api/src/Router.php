<?php
// ============================================================
//  src/Router.php
//  Tiny regex router — maps GET /api/<path> to callbacks
// ============================================================
namespace App;

use App\Response\JsonResponse;

class Router
{
    /** @var array<int, array{method: string, pattern: string, callback: callable}> */
    private array $routes = [];

    public function get(string $pattern, callable $callback): void
    {
        $this->routes[] = ['method' => 'GET', 'pattern' => $pattern, 'callback' => $callback];
    }

    /**
     * Match the current request and dispatch.
     * $pattern may contain named groups: /orders/(?P<id>[^/]+)
     */
    public function dispatch(): void
    {
        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        $uri    = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
        $uri    = rtrim((string) $uri, '/') ?: '/';

        // Strip the project base-path prefix: /dashboard/api
        $base = '/dashboard/api';
        if (str_starts_with($uri, $base)) {
            $uri = substr($uri, strlen($base)) ?: '/';
        }

        foreach ($this->routes as $route) {
            if ($route['method'] !== $method) {
                continue;
            }

            $regex = '#^' . $route['pattern'] . '$#';
            if (preg_match($regex, $uri, $matches)) {
                // Strip numeric keys from named groups
                $params = array_filter(
                    $matches,
                    static fn (int|string $k): bool => is_string($k),
                    ARRAY_FILTER_USE_KEY
                );
                ($route['callback'])($params);
                return;
            }
        }

        JsonResponse::error("Route not found: $method $uri", 404);
    }
}
