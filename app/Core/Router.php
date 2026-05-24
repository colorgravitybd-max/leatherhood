<?php
declare(strict_types=1);

namespace LH\Core;

/**
 * Tiny regex router. Supports {param} placeholders.
 * Designed for the storefront front-controller (public/index.php).
 */
final class Router
{
    /** @var array<int,array{0:string,1:string,2:callable}> */
    private array $routes = [];
    private $notFound;

    public function add(string $method, string $path, callable $handler): void
    {
        $this->routes[] = [strtoupper($method), $path, $handler];
    }
    public function get(string $p, callable $h)  { $this->add('GET',$p,$h); }
    public function post(string $p, callable $h) { $this->add('POST',$p,$h); }
    public function any(string $p, callable $h)  { $this->add('ANY',$p,$h); }

    public function notFound(callable $fn): void { $this->notFound = $fn; }

    public function dispatch(string $method, string $uri): void
    {
        $path = parse_url($uri, PHP_URL_PATH) ?: '/';
        $path = '/' . ltrim($path, '/');

        foreach ($this->routes as [$m, $p, $h]) {
            if ($m !== 'ANY' && $m !== $method) continue;
            $regex = '#^'.preg_replace('#\{([a-z_][a-z0-9_]*)\}#i', '(?P<$1>[^/]+)', $p).'$#';
            if (preg_match($regex, $path, $match)) {
                $params = array_filter($match, fn($k) => !is_int($k), ARRAY_FILTER_USE_KEY);
                $h($params);
                return;
            }
        }

        if ($this->notFound) { ($this->notFound)(); return; }
        http_response_code(404);
        echo '404 Not Found';
    }
}
