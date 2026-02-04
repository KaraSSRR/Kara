<?php
/**
 * HTTP client для микросервиса pokemon-showdown (showdown-sim).
 */
class ShowdownClient {
    private $baseUrl;
    private $timeout;

    public function __construct(string $baseUrl, int $timeoutSeconds = 8) {
        $this->baseUrl = rtrim($baseUrl, '/');
        $this->timeout = max(1, $timeoutSeconds);
    }

    public function step(array $payload): array {
        return $this->postJson('/v1/battle/step', $payload);
    }

    private function postJson(string $path, array $payload): array {
        $ch = curl_init();
        $url = $this->baseUrl . $path;

        $json = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Accept: application/json',
            ],
            CURLOPT_POSTFIELDS => $json,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => $this->timeout,
            CURLOPT_TIMEOUT => $this->timeout,
        ]);

        $raw = curl_exec($ch);
        $errno = curl_errno($ch);
        $err = curl_error($ch);
        $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($errno) {
            return ['ok' => false, 'error' => 'curl_errno=' . $errno . ' ' . $err];
        }
        if ($code < 200 || $code >= 300) {
            return ['ok' => false, 'error' => 'http=' . $code . ' body=' . (string)$raw];
        }

        $data = json_decode((string)$raw, true);
        if (!is_array($data)) {
            return ['ok' => false, 'error' => 'invalid_json'];
        }
        return $data;
    }
}
