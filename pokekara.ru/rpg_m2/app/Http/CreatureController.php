<?php
declare(strict_types=1);

namespace App\Http;

use App\Support\Request;
use App\Support\Response;
use App\Support\View;
use App\Support\Security;
use App\Domain\Game\CreatureRepository;

final class CreatureController {
    private CreatureRepository $creatures;

    public function __construct() {
        $this->creatures = new CreatureRepository();
    }

    private function renderPage(Request $request, string $view, array $data): void {
        if ($request->wantsPartial()) {
            Response::apiOk($request, View::payload($view, $data));
        }
        Response::html(View::render($view, $data));
    }

    public function show(Request $request): void {
        $uid = Security::requireAuth($request);
        $id = (int)($request->params['id'] ?? 0);
        if ($id <= 0) {
            if ($request->wantsJson()) Response::apiError($request, 'validation', 'Invalid id', 422);
            Response::html('<!doctype html><meta charset="utf-8"><h1>Bad request</h1>', 400);
        }

        $c = $this->creatures->findForUserFull($uid, $id);
        if (!$c) {
            if ($request->wantsJson()) Response::apiError($request, 'not_found', 'Creature not found', 404);
            Response::html('<!doctype html><meta charset="utf-8"><h1>404</h1><p>Creature not found</p>', 404);
        }

        $title = (string)$c['species_name'];
        if (!empty($c['nickname'])) $title .= ' — ' . (string)$c['nickname'];

        $this->renderPage($request, 'creature_show', [
            'title' => $title,
            'creature' => $c,
        ]);
    }
}
