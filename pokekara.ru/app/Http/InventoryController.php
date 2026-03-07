<?php
declare(strict_types=1);

namespace App\Http;

use App\Support\Request;
use App\Support\Response;
use App\Support\View;
use App\Support\Security;
use App\Domain\Game\ItemRepository;

final class InventoryController {
    private ItemRepository $items;

    public function __construct() {
        $this->items = new ItemRepository();
    }

    private function renderPage(Request $request, string $view, array $data): void {
        if ($request->wantsPartial()) {
            Response::apiOk($request, View::payload($view, $data));
        }
        Response::html(View::render($view, $data));
    }

    public function index(Request $request): void {
        $uid = Security::requireAuth($request);
        $inv = $this->items->listInventory($uid);

        $this->renderPage($request, 'inventory', [
            'title' => 'Инвентарь',
            'items' => $inv,
        ]);
    }
}
