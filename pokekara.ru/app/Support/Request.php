<?php
declare(strict_types=1);

namespace App\Support;

final class Request {
    public string $method;
    public string $path;
    public array $query;
    public array $post;
    public array $headers;
    public string $ip;
    public string $requestId;

    /** @var array<string,string> */
    public array $params = [];

    public static function fromGlobals(): self {
        $r = new self();
        $r->method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');

        $uri = $_SERVER['REQUEST_URI'] ?? '/';
        $qPos = strpos($uri, '?');
        $path = ($qPos === false) ? $uri : substr($uri, 0, $qPos);
        $r->path = rtrim($path, '/') ?: '/';

        // Normalize explicit front controller path (common on shared hosting)
// Some shared hostings can expose /index.php in REQUEST_URI (or /index.php/route)
if ($r->path === '/index.php' || str_starts_with($r->path, '/index.php/')) {
    $r->path = substr($r->path, strlen('/index.php'));
    if ($r->path === '' || $r->path === false) $r->path = '/';
}

        $r->query = $_GET ?? [];
        $r->post = $_POST ?? [];
        $r->headers = self::headersFromServer($_SERVER);

        $r->ip = self::clientIp($_SERVER);
        $r->requestId = bin2hex(random_bytes(8));
        return $r;
    }

    private static function headersFromServer(array $server): array {
        $h = [];
        foreach ($server as $k => $v) {
            if (str_starts_with($k, 'HTTP_')) {
                $name = str_replace('_', '-', strtolower(substr($k, 5)));
                $h[$name] = $v;
            }
        }
        if (isset($server['CONTENT_TYPE'])) $h['content-type'] = $server['CONTENT_TYPE'];
        if (isset($server['HTTP_ACCEPT'])) $h['accept'] = $server['HTTP_ACCEPT'];
        // Support explicit headers in some SAPIs
        if (isset($server['HTTP_X_PARTIAL'])) $h['x-partial'] = $server['HTTP_X_PARTIAL'];
        if (isset($server['HTTP_X_MODAL'])) $h['x-modal'] = $server['HTTP_X_MODAL'];
        return $h;
    }

    private static function clientIp(array $server): string {
        $ip = $server['REMOTE_ADDR'] ?? '0.0.0.0';
        $xf = $server['HTTP_X_FORWARDED_FOR'] ?? null;
        if ($xf && Env::get('APP_ENV') === 'local') {
            $parts = array_map('trim', explode(',', $xf));
            if (!empty($parts[0])) $ip = $parts[0];
        }
        return $ip;
    }

    public function wantsJson(): bool {
        $accept = strtolower((string)($this->headers['accept'] ?? ''));
        if (str_contains($accept, 'application/json')) return true;
        if (str_starts_with($this->path, '/api/')) return true;
        return false;
    }

    /**
     * SPA/PJAX mode: client requests only inner HTML + title (JSON payload).
     * Triggered by header X-Partial: 1 or query param ?_partial=1.
     */
    public function wantsPartial(): bool {
        $h = (string)($this->headers['x-partial'] ?? '');
        if ($h === '1') return true;
        $q = $this->query['_partial'] ?? null;
        return $q === '1' || $q === 1;
    }

    /**
     * Modal overlay mode: client requests partial HTML to be shown inside modal.
     * Triggered by header X-Modal: 1 or query param ?_modal=1.
     */
    public function wantsModal(): bool {
        $h = (string)($this->headers['x-modal'] ?? '');
        if ($h === '1') return true;
        $q = $this->query['_modal'] ?? null;
        return $q === '1' || $q === 1 || $q === true;
    }



    public function input(string $key, ?string $default = null): ?string {
        $v = $this->post[$key] ?? $this->query[$key] ?? $default;
        if ($v === null) return null;
        if (is_array($v)) return $default;
        return trim((string)$v);
    }
}
