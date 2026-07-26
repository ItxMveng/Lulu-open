<?php
declare(strict_types=1);

final class Router
{
    private static array $routes = [];

    public static function get(string $pattern, callable|string $handler, array $middlewares = []): void
    {
        self::add('GET', $pattern, $handler, $middlewares);
    }

    public static function post(string $pattern, callable|string $handler, array $middlewares = []): void
    {
        self::add('POST', $pattern, $handler, $middlewares);
    }

    public static function put(string $pattern, callable|string $handler, array $middlewares = []): void
    {
        self::add('PUT', $pattern, $handler, $middlewares);
    }

    public static function delete(string $pattern, callable|string $handler, array $middlewares = []): void
    {
        self::add('DELETE', $pattern, $handler, $middlewares);
    }

    public static function add(string $method, string $pattern, callable|string $handler, array $middlewares = []): void
    {
        self::$routes[strtoupper($method)][] = [
            'pattern' => self::normalizePattern($pattern),
            'handler' => $handler,
            'middlewares' => $middlewares,
        ];
    }

    public static function dispatch(): void
    {
        $path = request_path();
        $method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');

        if ($method === 'POST' && isset($_POST['_method'])) {
            $spoofedMethod = strtoupper((string) $_POST['_method']);
            if (in_array($spoofedMethod, ['PUT', 'DELETE'], true)) {
                $method = $spoofedMethod;
            }
        }

        $allowedMethods = [];

        foreach (self::$routes as $routeMethod => $routes) {
            foreach ($routes as $route) {
                $match = self::match($route['pattern'], $path);
                if ($match === null) {
                    continue;
                }

                if ($routeMethod !== $method) {
                    $allowedMethods[] = $routeMethod;
                    continue;
                }

                self::runMiddlewares($route['middlewares']);
                self::invoke($route['handler'], $match);
                return;
            }
        }

        if ($allowedMethods !== []) {
            header('Allow: ' . implode(', ', array_unique($allowedMethods)));
            abort(405, 'Méthode HTTP non autorisée.');
        }

        abort(404, 'Page introuvable.');
    }

    private static function invoke(callable|string $handler, array $parameters): void
    {
        if (is_callable($handler)) {
            call_user_func_array($handler, $parameters);
            return;
        }

        if (!str_contains($handler, '@')) {
            throw new RuntimeException('Handler de route invalide.');
        }

        [$controllerName, $method] = explode('@', $handler, 2);

        if (!class_exists($controllerName)) {
            throw new RuntimeException(sprintf('Contrôleur introuvable: %s', $controllerName));
        }

        $controller = new $controllerName();

        if (!method_exists($controller, $method)) {
            throw new RuntimeException(sprintf('Méthode introuvable: %s::%s', $controllerName, $method));
        }

        call_user_func_array([$controller, $method], $parameters);
    }

    private static function runMiddlewares(array $middlewares): void
    {
        foreach ($middlewares as $middleware) {
            if ($middleware === 'auth') {
                AuthMiddleware::requireAuth();
                continue;
            }

            if ($middleware === 'guest') {
                AuthMiddleware::requireGuest();
                continue;
            }

            if (str_starts_with($middleware, 'role:')) {
                $roles = array_filter(array_map('trim', explode(',', substr($middleware, 5))));
                AuthMiddleware::requireRole($roles);
                continue;
            }

            if (is_callable($middleware)) {
                $middleware();
            }
        }
    }

    private static function match(string $pattern, string $path): ?array
    {
        if ($pattern === $path) {
            return [];
        }

        $regex = preg_replace_callback(
            '/\{([a-zA-Z_][a-zA-Z0-9_]*)\}/',
            static fn (array $matches): string => '(?P<' . $matches[1] . '>[^/]+)',
            $pattern
        );

        if ($regex === null) {
            return null;
        }

        if (!preg_match('#^' . $regex . '$#', $path, $matches)) {
            return null;
        }

        $parameters = [];
        foreach ($matches as $key => $value) {
            if (!is_int($key)) {
                $parameters[] = $value;
            }
        }

        return $parameters;
    }

    private static function normalizePattern(string $pattern): string
    {
        $pattern = '/' . trim($pattern, '/');
        return $pattern === '//' ? '/' : (rtrim($pattern, '/') ?: '/');
    }
}