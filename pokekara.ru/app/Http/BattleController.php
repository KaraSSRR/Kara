<?php
declare(strict_types=1);

namespace App\Http;

use App\Support\Request;
use App\Support\Response;
use App\Support\View;
use App\Support\Security;
use App\Domain\Game\CreatureRepository;
use App\Domain\Game\LocationRepository;
use App\Domain\Battle\BattleRepository;
use App\Domain\Battle\BattleService;

final class BattleController {
    private CreatureRepository $creatures;
    private LocationRepository $locations;
    private BattleRepository $battles;
    private BattleService $service;

    public function __construct() {
        $this->creatures = new CreatureRepository();
        $this->locations = new LocationRepository();
        $this->battles = new BattleRepository();
        $this->service = new BattleService($this->battles, $this->creatures);
    }

    private function renderPage(Request $request, string $view, array $data): void {
        if ($request->wantsPartial()) {
            Response::apiOk($request, View::payload($view, $data));
        }
        Response::html(View::render($view, $data));
    }

    private function redirect(Request $request, string $to): void {
        if ($request->wantsPartial()) {
            Response::apiOk($request, ['redirect' => $to]);
        }
        Response::redirect($to);
    }

    public function index(Request $request): void {
        $uid = Security::requireAuth($request);
        $active = $this->battles->findActiveForUser($uid);
        $creatures = $this->creatures->listForUser($uid);

        $this->renderPage($request, 'battle_index', [
            'title' => 'Бой',
            'active_battle' => $active,
            'active_location' => ($active && !empty($active['location_id'])) ? $this->locations->find((int)$active['location_id']) : null,
            'creatures' => $creatures,
        ]);
    }

    public function start(Request $request): void {
        $uid = Security::requireAuth($request);
        Security::requireCsrf($request);

        $creatureId = (int)$request->input('creature_id', 0);
        if ($creatureId <= 0) {
            Security::flash('err', 'Выбери существо.');
            $this->redirect($request, '/battle');
        }

        try {
            $id = $this->service->start1v1($uid, $creatureId);
            Security::flash('ok', 'Бой создан.');
            $this->redirect($request, '/battle/' . $id);
        } catch (\Throwable $e) {
            Security::flash('err', $e->getMessage());
            $this->redirect($request, '/battle');
        }
    }

    public function show(Request $request): void {
        $uid = Security::requireAuth($request);
        $id = (int)($request->params['id'] ?? 0);
        if ($id <= 0) {
            if ($request->wantsJson()) Response::apiError($request, 'not_found', 'Not found', 404);
            Response::html('<!doctype html><meta charset="utf-8"><h1>404</h1>', 404);
        }

        $battle = $this->battles->findByIdForUser($uid, $id);
        if (!$battle) {
            if ($request->wantsJson()) Response::apiError($request, 'not_found', 'Not found', 404);
            Response::html('<!doctype html><meta charset="utf-8"><h1>404</h1>', 404);
        }

        $state = BattleRepository::decodeJson($battle['state']);
        $logs = $this->battles->listLogs($id, 180);
        $location = (!empty($battle['location_id'])) ? $this->locations->find((int)$battle['location_id']) : null;

        $this->renderPage($request, 'battle_show', [
            'title' => 'Бой #' . $id,
            'battle' => $battle,
            'state' => $state,
            'logs' => $logs,
            'location' => $location,
        ]);
    }

    public function move(Request $request): void {
        $uid = Security::requireAuth($request);
        Security::requireCsrf($request);

        $id = (int)($request->params['id'] ?? 0);
        $slot = (int)$request->input('slot', 0);
        if ($id <= 0 || $slot <= 0) {
            Security::flash('err', 'Некорректный ход.');
            $this->redirect($request, '/battle');
        }

        try {
            $this->service->playerMove($uid, $id, $slot);
            $this->redirect($request, '/battle/' . $id);
        } catch (\Throwable $e) {
            Security::flash('err', $e->getMessage());
            $this->redirect($request, '/battle/' . $id);
        }
    }

    public function apiReplay(Request $request): void {
        $uid = Security::requireAuth($request);
        $id = (int)($request->params['id'] ?? 0);
        $battle = $this->battles->findByIdForUser($uid, $id);
        if (!$battle) Response::apiError($request, 'not_found', 'Not found', 404);

        $state = BattleRepository::decodeJson($battle['state']);
        $result = BattleRepository::decodeJson($battle['result']);
        $actions = $this->battles->listActions($id);
        $logs = $this->battles->listLogs($id, 500);

        Response::apiOk($request, [
            'battle' => [
                'id' => (int)$battle['id'],
                'seed' => (int)$battle['seed'],
                'status' => (string)$battle['status'],
                'turn' => (int)$battle['turn'],
                'created_at' => $battle['created_at'],
                'finished_at' => $battle['finished_at'],
            ],
            'state' => $state,
            'result' => $result,
            'actions' => $actions,
            'logs' => $logs,
        ]);
    }
}
