<?php
declare(strict_types=1);

namespace App\Http;

use App\Support\Request;
use App\Support\Response;
use App\Support\View;
use App\Support\Security;
use App\Domain\Auth\UserRepository;
use App\Domain\Game\LocationRepository;
use App\Domain\Battle\BattleRepository;

final class LocationController {
    private LocationRepository $locations;
    private UserRepository $users;
    private BattleRepository $battles;

    public function __construct() {
        $this->locations = new LocationRepository();
        $this->users = new UserRepository();
        $this->battles = new BattleRepository();
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
        $user = $this->users->findById($uid);
        $locs = $this->locations->listAll();

        $this->renderPage($request, 'locations', [
            'title' => 'Локации',
            'user' => $user,
            'locations' => $locs,
        ]);
    }

    public function show(Request $request): void {
        $uid = Security::requireAuth($request);
        $id = (int)($request->params['id'] ?? 0);
        $loc = $this->locations->find($id);
        if (!$loc) {
            if ($request->wantsJson()) Response::apiError($request, 'not_found', 'Location not found', 404);
            Response::html('<!doctype html><meta charset="utf-8"><h1>404</h1><p>Локация не найдена</p>', 404);
        }

        $user = $this->users->findById($uid);

        $this->renderPage($request, 'location_show', [
            'title' => 'Локация',
            'user' => $user,
            'location' => $loc,
        ]);
    }

    public function travel(Request $request): void {
        $uid = Security::requireAuth($request);
        Security::requireCsrf($request);

        // If the user is in an active battle, travelling would break the "battle at location" mental model.
        $active = $this->battles->findActiveForUser($uid);
        if ($active) {
            Security::flash('err', 'Нельзя путешествовать во время боя.');
            if ($request->wantsPartial()) { $this->redirect($request, '/battle/' . (int)$active['id']); }
            if ($request->wantsJson()) Response::apiOk($request, ['redirect' => '/battle/' . (int)$active['id']]);
            Response::redirect('/battle/' . (int)$active['id']);
        }

        $id = (int)($request->params['id'] ?? 0);
        $loc = $this->locations->find($id);
        if (!$loc) Response::apiError($request, 'not_found', 'Location not found', 404);

        $this->users->setLocation($uid, $id);

        Security::flash('ok', 'Переход выполнен.');

        if ($request->wantsPartial()) $this->redirect($request, '/me');
        if ($request->wantsJson()) Response::apiOk($request, ['current_location_id' => $id]);

        Response::redirect('/me');
    }
}
