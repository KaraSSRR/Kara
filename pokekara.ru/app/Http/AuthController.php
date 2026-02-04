<?php
declare(strict_types=1);

namespace App\Http;

use App\Support\Request;
use App\Support\Response;
use App\Support\View;
use App\Support\Security;
use App\Domain\Auth\UserRepository;
use App\Domain\Auth\AuthService;
use App\Domain\Game\CreatureRepository;
use App\Domain\Game\ItemRepository;
use App\Domain\Game\LocationRepository;

final class AuthController {
    private UserRepository $users;
    private AuthService $auth;
    private CreatureRepository $creatures;
    private ItemRepository $items;
    private LocationRepository $locations;

    public function __construct() {
        $this->users = new UserRepository();
        $this->auth = new AuthService($this->users);
        $this->creatures = new CreatureRepository();
        $this->items = new ItemRepository();
        $this->locations = new LocationRepository();
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

    public function home(Request $request): void {
        $uid = Security::userId();
        if ($uid) {
            $user = $this->users->findById((int)$uid);
            $this->renderPage($request, 'home_dashboard', [
                'title' => 'Главная',
                'user' => $user,
            ]);
            return;
        }

        $this->renderPage($request, 'home', ['title' => 'RPG (v2) — вход']);
    }

    public function showLogin(Request $request): void {
        if (Security::userId()) $this->redirect($request, '/me');
        $this->renderPage($request, 'login', ['title' => 'Вход']);
    }

    public function showRegister(Request $request): void {
        if (Security::userId()) $this->redirect($request, '/me');
        $this->renderPage($request, 'register', ['title' => 'Регистрация']);
    }

    public function login(Request $request): void {
        Security::requireCsrf($request);

        if (!Security::rateLimit('auth', $request->ip . '|login', 10, 300)) {
            Security::flash('err', 'Слишком много попыток. Попробуй позже.');
            $this->redirect($request, '/login');
        }

        $login = (string)$request->input('login', '');
        $password = (string)$request->input('password', '');

        $res = $this->auth->login($login, $password);
        if (!$res['ok']) {
            Security::flash('err', (string)$res['error']);
            $this->redirect($request, '/login');
        }

        session_regenerate_id(true);
        $_SESSION['uid'] = (int)$res['user_id'];
        $this->users->touchLogin((int)$res['user_id']);

        Security::flash('ok', 'Добро пожаловать!');
        $this->redirect($request, '/me');
    }

    public function register(Request $request): void {
        Security::requireCsrf($request);

        if (!Security::rateLimit('auth', $request->ip . '|register', 5, 300)) {
            Security::flash('err', 'Слишком много регистраций с этого IP. Попробуй позже.');
            $this->redirect($request, '/register');
        }

        $username = (string)$request->input('username', '');
        $email = (string)$request->input('email', '');
        $password = (string)$request->input('password', '');

        $res = $this->auth->register($username, $email, $password, 1);
        if (!$res['ok']) {
            $errText = implode(' · ', array_values($res['errors'] ?? []));
            Security::flash('err', $errText ?: 'Ошибка регистрации');
            $this->redirect($request, '/register');
        }

        $userId = (int)$res['user_id'];
        $this->creatures->createStarter($userId, 1, '');
        $this->items->grant($userId, 1, 3);

        session_regenerate_id(true);
        $_SESSION['uid'] = $userId;

        Security::flash('ok', 'Аккаунт создан. Удачной игры!');
        $this->redirect($request, '/me');
    }

    public function logout(Request $request): void {
        Security::requireCsrf($request);
        $_SESSION = [];
        if (session_status() === PHP_SESSION_ACTIVE) session_destroy();
        $this->redirect($request, '/');
    }

    /** JSON API */
    public function apiLogin(Request $request): void {
        if (!Security::rateLimit('auth', $request->ip . '|api_login', 10, 300)) {
            Response::apiError($request, 'rate_limited', 'Too many attempts', 429);
        }

        $login = (string)$request->input('login', '');
        $password = (string)$request->input('password', '');

        $res = $this->auth->login($login, $password);
        if (!$res['ok']) Response::apiError($request, 'auth', (string)$res['error'], 401);

        session_regenerate_id(true);
        $_SESSION['uid'] = (int)$res['user_id'];
        $this->users->touchLogin((int)$res['user_id']);

        Response::apiOk($request, ['user_id' => (int)$res['user_id']]);
    }

    public function apiRegister(Request $request): void {
        if (!Security::rateLimit('auth', $request->ip . '|api_register', 5, 300)) {
            Response::apiError($request, 'rate_limited', 'Too many attempts', 429);
        }

        $username = (string)$request->input('username', '');
        $email = (string)$request->input('email', '');
        $password = (string)$request->input('password', '');

        $res = $this->auth->register($username, $email, $password, 1);
        if (!$res['ok']) Response::apiError($request, 'validation', 'Validation error', 422, $res['errors'] ?? []);

        $userId = (int)$res['user_id'];
        $this->creatures->createStarter($userId, 1, '');
        $this->items->grant($userId, 1, 3);

        session_regenerate_id(true);
        $_SESSION['uid'] = $userId;

        Response::apiOk($request, ['user_id' => $userId], 201);
    }

    public function apiLogout(Request $request): void {
        $_SESSION = [];
        if (session_status() === PHP_SESSION_ACTIVE) session_destroy();
        Response::apiOk($request, ['logged_out' => true]);
    }
}
