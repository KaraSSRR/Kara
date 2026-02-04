<?php
declare(strict_types=1);

namespace App\Support;

final class View {
    /**
     * Full page (layout + inner view).
     */
    public static function render(string $view, array $data = []): string {
        [$viewFile, $data] = self::prepare($view, $data);

        extract($data, EXTR_SKIP);

        ob_start();
        require __DIR__ . '/../Views/layout.php';
        return (string)ob_get_clean();
    }

    /**
     * Inner view only (no layout).
     */
    public static function renderInner(string $view, array $data = []): string {
        [$viewFile, $data] = self::prepare($view, $data);

        extract($data, EXTR_SKIP);

        ob_start();
        require $viewFile;
        return (string)ob_get_clean();
    }

    /**
     * Payload for SPA navigation.
     * Returns: { title, html, flash? }
     *
     * @return array{title:string, html:string, flash:array|null}
     */
    public static function payload(string $view, array $data = []): array {
        [$viewFile, $data] = self::prepare($view, $data);

        $flash = null;
        if (isset($data['_flash']) && is_array($data['_flash']) && isset($data['_flash']['type'], $data['_flash']['message'])) {
            $flash = [
                'type' => (string)$data['_flash']['type'],
                'message' => (string)$data['_flash']['message'],
            ];
        }

        extract($data, EXTR_SKIP);

        ob_start();
        require $viewFile;
        $html = (string)ob_get_clean();

        $title = (string)($data['title'] ?? 'RPG v2');

        return [
            'title' => $title,
            'html' => $html,
            'flash' => $flash,
        ];
    }

    /**
     * @return array{0:string,1:array}
     */
    private static function prepare(string $view, array $data): array {
        $viewFile = __DIR__ . '/../Views/' . $view . '.php';
        if (!is_file($viewFile)) throw new \RuntimeException('View not found: ' . $view);

        $data['_csrf'] = Security::csrfToken();
        $data['_flash'] = Security::pullFlash();
        $data['_uid'] = Security::userId();

        return [$viewFile, $data];
    }
}
