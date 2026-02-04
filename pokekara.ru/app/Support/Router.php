<?php
declare(strict_types=1);

namespace App\Support;

final class Router {
    /** @var array<string, array<int, array{pattern:string,regex:string,keys:array,handler:mixed}>> */
    private array $routes = [
        'GET' => [],
        'POST' => [],
    ];

    public function get(string $pattern, $handler): void { $this->add('GET', $pattern, $handler); }
    public function post(string $pattern, $handler): void { $this->add('POST', $pattern, $handler); }

    private function normalizePath(string $path): string {
        // Keep root as "/", otherwise trim trailing slashes.
        $p = rtrim($path, '/');
        return $p === '' ? '/' : $p;
    }

    private function add(string $method, string $pattern, $handler): void {
        $pattern = $this->normalizePath($pattern);

        $keys = [];
        $regex = preg_replace_callback('~\{([a-zA-Z_][a-zA-Z0-9_]*)\}~', function($m) use (&$keys) {
            $keys[] = $m[1];
            return '([^/]+)';
        }, $pattern);

        // allow optional trailing slash on URL (except it is normalized anyway)
        $regex = (string)$regex;
        $regex = $regex === '/' ? '/' : rtrim($regex, '/');

        $this->routes[$method][] = [
            'pattern' => $pattern,
            'regex' => '~^' . $regex . '$~',
            'keys' => $keys,
            'handler' => $handler,
        ];
    }

    public function dispatch(Request $request): void {
        $method = $request->method;
        $path = $this->normalizePath($request->path);

        foreach ($this->routes[$method] ?? [] as $r) {
            if (preg_match($r['regex'], $path, $m)) {
                array_shift($m);
                $params = [];
                foreach ($r['keys'] as $i => $k) $params[$k] = $m[$i] ?? '';
                $request->params = $params;
                $this->invoke($r['handler'], $request);
                return;
            }
        }

        if ($request->wantsJson()) Response::apiError($request, 'not_found', 'Not found', 404);
        Response::html('<!doctype html><meta charset="utf-8"><title>404</title><h1>404</h1><p>Not found</p>', 404);
    }

    private function invoke($handler, Request $request): void {
        if (is_array($handler) && count($handler) === 2 && is_string($handler[0])) {
            $class = $handler[0];
            $method = $handler[1];
            $obj = new $class();
            $obj->$method($request);
            return;
        }
        if (is_callable($handler)) {
            $handler($request);
            return;
        }
        throw new \RuntimeException('Invalid route handler');
    }
}
