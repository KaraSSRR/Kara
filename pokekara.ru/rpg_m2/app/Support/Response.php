<?php
declare(strict_types=1);

namespace App\Support;

use Throwable;

final class Response {
    public static function html(string $html, int $status = 200): void {
        http_response_code($status);
        header('Content-Type: text/html; charset=utf-8');
        echo $html;
        exit;
    }

    public static function json(array $payload, int $status = 200): void {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($payload, JSON_UNESCAPED_UNICODE);
        exit;
    }

    public static function redirect(string $to): void {
        header('Location: ' . $to, true, 302);
        exit;
    }

    public static function exception(Throwable $e, Request $request): void {
        error_log('[req '.$request->requestId.'] '.$e->getMessage().' in '.$e->getFile().':'.$e->getLine());
        if (Env::bool('APP_DEBUG', false)) error_log($e->getTraceAsString());

        if ($request->wantsJson()) {
            self::json([
                'ok' => false,
                'error' => [
                    'code' => 'server_error',
                    'message' => Env::bool('APP_DEBUG', false) ? $e->getMessage() : 'Server error',
                ],
                'request_id' => $request->requestId,
            ], 500);
        }

        $msg = Env::bool('APP_DEBUG', false) ? htmlspecialchars($e->getMessage(), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') : 'Server error';
        self::html('<!doctype html><meta charset="utf-8"><title>Error</title><h1>500</h1><p>'.$msg.'</p>', 500);
    }

    public static function apiError(Request $request, string $code, string $message, int $status = 400, array $fields = []): void {
        self::json([
            'ok' => false,
            'error' => [
                'code' => $code,
                'message' => $message,
                'fields' => (object)$fields,
            ],
            'request_id' => $request->requestId,
        ], $status);
    }

    public static function apiOk(Request $request, array $data = [], int $status = 200): void {
        self::json([
            'ok' => true,
            'data' => $data,
            'request_id' => $request->requestId,
        ], $status);
    }
}
