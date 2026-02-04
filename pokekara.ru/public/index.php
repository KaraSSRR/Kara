<?php
declare(strict_types=1);

require __DIR__ . '/../app/bootstrap.php';

use App\Support\Router;
use App\Support\Response;
use App\Support\Request;
use App\Http\AuthController;
use App\Http\ProfileController;
use App\Http\CreatureController;
use App\Http\InventoryController;
use App\Http\LocationController;
use App\Http\BattleController;
use App\Http\ChatController;

$router = new Router();

/** Public pages ("витрина") */
$router->get('/', [AuthController::class, 'home']);
$router->get('/index.php', [AuthController::class, 'home']);

$router->get('/login', [AuthController::class, 'showLogin']);
$router->post('/login', [AuthController::class, 'login']);
$router->get('/register', [AuthController::class, 'showRegister']);
$router->post('/register', [AuthController::class, 'register']);
$router->post('/logout', [AuthController::class, 'logout']);

/** Authenticated pages */
$router->get('/me', [ProfileController::class, 'me']);

$router->get('/creatures', [ProfileController::class, 'creatures']);
$router->get('/creatures/{id}', [CreatureController::class, 'show']);

$router->get('/battle', [BattleController::class, 'index']);
$router->post('/battle/start', [BattleController::class, 'start']);
$router->get('/battle/{id}', [BattleController::class, 'show']);
$router->post('/battle/{id}/move', [BattleController::class, 'move']);

$router->get('/inventory', [InventoryController::class, 'index']);

$router->get('/locations', [LocationController::class, 'index']);
$router->get('/locations/panel', [LocationController::class, 'panel']);
$router->get('/locations/{id}', [LocationController::class, 'show']);
$router->post('/locations/{id}/travel', [LocationController::class, 'travel']);

/** API (JSON) */
$router->post('/api/login', [AuthController::class, 'apiLogin']);
$router->post('/api/register', [AuthController::class, 'apiRegister']);
$router->post('/api/logout', [AuthController::class, 'apiLogout']);
$router->get('/api/me', [ProfileController::class, 'apiMe']);
$router->post('/api/battle/start', [BattleController::class, 'apiStart']);
$router->post('/api/battle/{id}/turn', [BattleController::class, 'apiTurn']);
$router->get('/api/battle/{id}/state', [BattleController::class, 'apiState']);
$router->post('/api/battle/{id}/forfeit', [BattleController::class, 'apiForfeit']);
$router->get('/api/battle/{id}', [BattleController::class, 'apiReplay']);
$router->get('/api/chat/meta', [ChatController::class, 'meta']);
$router->get('/api/chat/poll', [ChatController::class, 'poll']);
$router->post('/api/chat/send', [ChatController::class, 'send']);
$router->get('/api/chat/dm/peers', [ChatController::class, 'dmPeers']);

$request = Request::fromGlobals();


// Variant A (game as world background): direct links open as modal over /locations
if (! $request->wantsPartial()
    && $request->method === 'GET'
    && isset($_SESSION['uid']) && (int)$_SESSION['uid'] > 0
    && $request->path !== '/locations'
    && $request->path !== '/'
    && $request->path !== '/login'
    && $request->path !== '/register'
    && $request->path !== '/index.php'
    && !str_starts_with($request->path, '/api/')
) {
    $full = (string)($_SERVER['REQUEST_URI'] ?? $request->path);
    Response::redirect('/locations?open=' . rawurlencode($full));
}

try {
    $router->dispatch($request);
} catch (Throwable $e) {
    Response::exception($e, $request);
}
