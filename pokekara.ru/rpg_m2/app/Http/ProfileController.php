<?php
declare(strict_types=1);

namespace App\Http;

use App\Support\Request;
use App\Support\Response;
use App\Support\View;
use App\Support\Security;
use App\Domain\Auth\UserRepository;
use App\Domain\Game\CreatureRepository;
use App\Domain\Game\LocationRepository;

final class ProfileController {
    private UserRepository $users;
    private CreatureRepository $creatures;
    private LocationRepository $locations;

    public function __construct() {
        $this->users = new UserRepository();
        $this->creatures = new CreatureRepository();
        $this->locations = new LocationRepository();
    }

    private function renderPage(Request $request, string $view, array $data): void {
        if ($request->wantsPartial()) {
            Response::apiOk($request, View::payload($view, $data));
        }
        Response::html(View::render($view, $data));
    }

    public function me(Request $request): void {
        $uid = Security::requireAuth($request);

        $user = $this->users->findById($uid);
        $creatures = $this->creatures->listForUser($uid);
        $loc = null;
        if ($user && $user['current_location_id']) {
            $loc = $this->locations->find((int)$user['current_location_id']);
        }

        $this->renderPage($request, 'profile', [
            'title' => 'Профиль',
            'user' => $user,
            'creatures' => $creatures,
            'location' => $loc,
        ]);
    }

    public function creatures(Request $request): void {
        $uid = Security::requireAuth($request);
        $creatures = $this->creatures->listForUser($uid);

        $this->renderPage($request, 'creatures', [
            'title' => 'Существа',
            'creatures' => $creatures,
        ]);
    }

    public function apiMe(Request $request): void {
        $uid = Security::requireAuth($request);
        $user = $this->users->findById($uid);
        $creatures = $this->creatures->listForUser($uid);
        Response::apiOk($request, ['user' => $user, 'creatures' => $creatures]);
    }
}
