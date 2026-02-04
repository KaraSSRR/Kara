<?php

/**
 * PokeMarketNpc
 *
 * Modern Pokemarket UI (buy + sell + bulk sell) rendered inside the new NPC dialog.
 * Backward-compatible: works via /do/Npc/ endpoint and keeps legacy NPC scripts intact.
 */
class PokeMarketNpc
{
    // Default buyback rate (can be adjusted per item type/rarity below)
    const DEFAULT_SELL_RATE = 0.50;
    const MIN_SELL_RATE = 0.10;

    // Item types that should never be sold back in the Pokemarket.
    // (Quest/event items should not be monetizable through the shop.)
    private static $NO_SELL_TYPES = ['quest', 'skin', 'plane_ticket', 'egg', 'boxes'];

    // Per-type base rate overrides (applied before rarity modifiers)
    private static $SELL_RATE_BY_TYPE = [
        'ball'         => 0.50,
        'potion'       => 0.50,
        'berry'        => 0.50,
        'other'        => 0.40,
        'tm'           => 0.35,
        'evolver'      => 0.30,
        'craft'        => 0.30,
        'trophy'       => 0.20,
        'modificator'  => 0.35,
        'cloth'        => 0.25,
        'card'         => 0.25,
    ];

    // Rarity adjustment (subtract from rate). Keys match base_items.rait_it in the dump.
    private static $SELL_RATE_RARITY_DELTA = [
        'normal' => 0.00,
        'rare'   => 0.05,
        'epic'   => 0.10,
        'legend' => 0.15,
    ];

    private static function esc($s)
    {
        return htmlspecialchars((string)$s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

    private static function db()
    {
        return Work::$sql; // project standard
    }

    private static function uid()
    {
        return isset($_SESSION['id']) ? (int)$_SESSION['id'] : 0;
    }

    private static function baseItemsColumns()
    {
        static $cols = null;
        if ($cols !== null) return $cols;
        $cols = [];
        $q = self::db()->query("SHOW COLUMNS FROM `base_items`");
        if ($q) {
            while ($r = $q->fetch_assoc()) {
                if (!empty($r['Field'])) $cols[$r['Field']] = true;
            }
        }
        return $cols;
    }

    private static function baseItemsHas($col)
    {
        $cols = self::baseItemsColumns();
        return isset($cols[$col]);
    }

    private static function userLocationId($uid)
    {
        $uid = (int)$uid;
        $q = self::db()->query("SELECT `location` FROM `users` WHERE `id` = {$uid} LIMIT 1");
        if (!$q) return 0;
        $r = $q->fetch_assoc();
        return $r ? (int)$r['location'] : 0;
    }

    private static function npcRow($npcId)
    {
        $npcId = (int)$npcId;
        $q = self::db()->query("SELECT * FROM `base_npc` WHERE `id` = {$npcId} LIMIT 1");
        return $q ? $q->fetch_assoc() : null;
    }

    private static function itemCount($uid, $itemId)
    {
        $uid = (int)$uid; $itemId = (int)$itemId;
        $q = self::db()->query("SELECT `count` FROM `items_users` WHERE `user` = {$uid} AND `item_id` = {$itemId} LIMIT 1");
        if (!$q) return 0;
        $r = $q->fetch_assoc();
        return $r ? (int)$r['count'] : 0;
    }

    private static function shopRows($npcId, $locId)
    {
        $npcId = (int)$npcId; $locId = (int)$locId;

        $selectExtra = '';
        if (self::baseItemsHas('type'))    $selectExtra .= ", bi.`type` AS `item_kind`";
        if (self::baseItemsHas('trade'))   $selectExtra .= ", bi.`trade` AS `item_trade`";
        if (self::baseItemsHas('lombard')) $selectExtra .= ", bi.`lombard` AS `item_lombard`";
        if (self::baseItemsHas('rait_it')) $selectExtra .= ", bi.`rait_it` AS `item_rarity`";

        $sql = "SELECT n.`id` AS `nid`, n.`item_id`, n.`item_price`, n.`item_count`, n.`item_type`,
                       bi.`name` AS `item_name`, cur.`name` AS `cur_name`
                       {$selectExtra}
                FROM `items_npc` n
                LEFT JOIN `base_items` bi  ON bi.`id`  = n.`item_id`
                LEFT JOIN `base_items` cur ON cur.`id` = n.`item_type`
                WHERE n.`npc_id` = {$npcId} AND n.`location_id` = {$locId}
                ORDER BY n.`item_id` ASC";

        $q = self::db()->query($sql);
        $rows = [];
        if ($q) { while ($r = $q->fetch_assoc()) { $rows[] = $r; } }

        // Fallback (in case location is not filled for some reason)
        if (!$rows) {
            $sql2 = "SELECT n.`id` AS `nid`, n.`item_id`, n.`item_price`, n.`item_count`, n.`item_type`,
                            bi.`name` AS `item_name`, cur.`name` AS `cur_name`
                            {$selectExtra}
                     FROM `items_npc` n
                     LEFT JOIN `base_items` bi  ON bi.`id`  = n.`item_id`
                     LEFT JOIN `base_items` cur ON cur.`id` = n.`item_type`
                     WHERE n.`npc_id` = {$npcId}
                     ORDER BY n.`item_id` ASC";
            $q2 = self::db()->query($sql2);
            if ($q2) { while ($r = $q2->fetch_assoc()) { $rows[] = $r; } }
        }

        return $rows;
    }

    private static function currencyIdsFromRows(array $rows)
    {
        $ids = [];
        foreach ($rows as $r) {
            $cid = isset($r['item_type']) ? (int)$r['item_type'] : 0;
            if ($cid > 0) $ids[$cid] = true;
        }
        return array_keys($ids);
    }

    private static function balancesMap($uid, array $currencyIds)
    {
        $out = [];
        foreach ($currencyIds as $cid) {
            $out[(int)$cid] = self::itemCount($uid, (int)$cid);
        }
        return $out;
    }

    private static function renderBalancesInline($uid, array $currencyIds, array $currencyNames = [])
    {
        if (!$currencyIds) return '';
        $parts = [];
        foreach ($currencyIds as $cid) {
            $cid = (int)$cid;
            $cnt = self::itemCount($uid, $cid);
            $name = isset($currencyNames[$cid]) ? $currencyNames[$cid] : '';
            $parts[] = '<span class="npc-shop-balance" data-cur="'.$cid.'">'
                     .   '<img src="/img/world/items/little/'.$cid.'.png" alt="">'
                     .   '<span class="npc-shop-balance-name">'.self::esc($name).'</span>'
                     .   '<b data-balance>'.number_format($cnt, 0, '.', '.').'</b>'
                     . '</span>';
        }
        return '<div class="npc-shop-balances">'.implode('', $parts).'</div>';
    }

    private static function isSellAllowed(array $row)
    {
        // If the shop schema does not expose metadata, stay conservative but functional.
        $kind = isset($row['item_kind']) ? (string)$row['item_kind'] : '';

        if ($kind && in_array($kind, self::$NO_SELL_TYPES, true)) return false;

        if (isset($row['item_trade'])) {
            $trade = (string)$row['item_trade'];
            if ($trade === 'false' || $trade === '0') return false;
        }

        if (isset($row['item_lombard'])) {
            $l = (string)$row['item_lombard'];
            if ($l === 'false' || $l === '0') return false;
        }

        return true;
    }

    private static function sellRateForRow(array $row)
    {
        $rate = self::DEFAULT_SELL_RATE;

        $kind = isset($row['item_kind']) ? (string)$row['item_kind'] : '';
        if ($kind && isset(self::$SELL_RATE_BY_TYPE[$kind])) {
            $rate = (float)self::$SELL_RATE_BY_TYPE[$kind];
        }

        $rar = isset($row['item_rarity']) ? (string)$row['item_rarity'] : '';
        if ($rar && isset(self::$SELL_RATE_RARITY_DELTA[$rar])) {
            $rate -= (float)self::$SELL_RATE_RARITY_DELTA[$rar];
        }

        if ($rate < self::MIN_SELL_RATE) $rate = self::MIN_SELL_RATE;
        if ($rate > 0.90) $rate = 0.90;

        return $rate;
    }

    private static function sellPricePerRow(array $row)
    {
        $buyPrice = (int)($row['item_price'] ?? 0);
        $rate = self::sellRateForRow($row);
        $sell = (int)floor($buyPrice * $rate);
        if ($sell < 0) $sell = 0;
        return $sell;
    }

    private static function noteHtml($note)
    {
        if (!$note) {
            return '<div class="npc-shop-note" data-shop-note style="display:none"></div>';
        }
        return '<div class="npc-shop-note" data-shop-note>'.self::esc($note).'</div>';
    }

    private static function renderWelcome(array &$response, $npcId)
    {
        $npc = self::npcRow($npcId);
        $response['name']  = $npc && !empty($npc['name']) ? $npc['name'] : 'Продавец';
        if ($npc && !empty($npc['image'])) $response['image'] = $npc['image'];
        $response['question'] = 'Добро пожаловать в Покемаркет! Чем могу помочь?';
        $response['answer'] = [
            1 => 'Купить',
            2 => 'Продать',
            0 => 'До встречи'
        ];
    }

    private static function renderBuy(array &$response, $npcId, $note = null)
    {
        $uid = self::uid();
        $locId = self::userLocationId($uid);
        $npc = self::npcRow($npcId);
        $response['name']  = $npc && !empty($npc['name']) ? $npc['name'] : 'Продавец';
        if ($npc && !empty($npc['image'])) $response['image'] = $npc['image'];

        $rows = self::shopRows($npcId, $locId);

        $currencyNames = [];
        foreach ($rows as $r) {
            $cid = (int)$r['item_type'];
            if ($cid > 0 && !isset($currencyNames[$cid])) $currencyNames[$cid] = (string)($r['cur_name'] ?? '');
        }
        $currencies = self::currencyIdsFromRows($rows);

        $cards = '';
        foreach ($rows as $r) {
            $itemId = (int)$r['item_id'];
            $itemName = $r['item_name'] ? $r['item_name'] : ('Предмет #'.$itemId);
            $price = (int)$r['item_price'];
            $curId = (int)$r['item_type'];
            $stock = (int)$r['item_count'];

            $stockLabel = ($stock < 0) ? '∞' : (string)$stock;
            $disabled = ($stock === 0);

            $cards .= '<div class="npc-shop-card" data-item-id="'.$itemId.'" data-name="'.self::esc($itemName).'">'
                    .   '<img class="npc-shop-img" src="/img/world/items/little/'.$itemId.'.png" alt="">'
                    .   '<div class="npc-shop-meta">'
                    .     '<div class="npc-shop-name">'.self::esc($itemName).'</div>'
                    .     '<div class="npc-shop-sub npc-shop-stock">В наличии: <b data-stock>'.self::esc($stockLabel).'</b></div>'
                    .     '<div class="npc-shop-row">'
                    .       '<div class="npc-shop-price"><img src="/img/world/items/little/'.$curId.'.png" alt="">'.number_format($price, 0, '.', '.').'</div>'
                    .       '<div class="npc-shop-qty"><input data-qty type="number" min="1" step="1" value="1" inputmode="numeric"></div>'
                    .       '<button class="npc-shop-action" data-shop-action="buy" data-item-id="'.$itemId.'" '.($disabled?'disabled':'').'>Купить</button>'
                    .     '</div>'
                    .   '</div>'
                    . '</div>';
        }

        $html = '<div class="npc-shop" data-shop-mode="buy" data-npc-id="'.(int)$npcId.'">'
              .   '<div class="npc-shop-toolbar">'
              .     '<div class="npc-shop-tabs">'
              .       '<button class="npc-tab active" onclick="NpcDialog('.(int)$npcId.',1,event);">Купить</button>'
              .       '<button class="npc-tab" onclick="NpcDialog('.(int)$npcId.',2,event);">Продать</button>'
              .     '</div>'
              .     '<div class="npc-shop-search"><input data-shop-search type="text" placeholder="Поиск предмета"></div>'
              .     self::renderBalancesInline($uid, $currencies, $currencyNames)
              .   '</div>'
              .   self::noteHtml($note)
              .   '<div class="npc-shop-grid">'.$cards.'</div>'
              . '</div>';

        $response['question'] = $html;
        $response['answer'] = [
            0 => 'Назад',
            2 => 'Продать'
        ];
    }

    private static function renderSell(array &$response, $npcId, $note = null)
    {
        $uid = self::uid();
        $locId = self::userLocationId($uid);
        $npc = self::npcRow($npcId);
        $response['name']  = $npc && !empty($npc['name']) ? $npc['name'] : 'Продавец';
        if ($npc && !empty($npc['image'])) $response['image'] = $npc['image'];

        $rows = self::shopRows($npcId, $locId);

        $currencyNames = [];
        foreach ($rows as $r) {
            $cid = (int)$r['item_type'];
            if ($cid > 0 && !isset($currencyNames[$cid])) $currencyNames[$cid] = (string)($r['cur_name'] ?? '');
        }
        $currencies = self::currencyIdsFromRows($rows);

        // Allow selling only items that are in this NPC catalogue AND pass sell rules.
        $cards = '';
        $any = false;
        $blockedAny = false;

        foreach ($rows as $r) {
            $itemId = (int)$r['item_id'];
            $own = self::itemCount($uid, $itemId);
            if ($own <= 0) continue;

            if (!self::isSellAllowed($r)) {
                $blockedAny = true;
                continue;
            }

            $any = true;
            $itemName = $r['item_name'] ? $r['item_name'] : ('Предмет #'.$itemId);
            $sellPer = self::sellPricePerRow($r);
            $curId = (int)$r['item_type'];

            $cards .= '<div class="npc-shop-card" data-item-id="'.$itemId.'" data-name="'.self::esc($itemName).'" data-sell-per="'.$sellPer.'" data-cur-id="'.$curId.'">'
                    .   '<div class="npc-shop-select"><input type="checkbox" data-sell-check></div>'
                    .   '<img class="npc-shop-img" src="/img/world/items/little/'.$itemId.'.png" alt="">'
                    .   '<div class="npc-shop-meta">'
                    .     '<div class="npc-shop-name">'.self::esc($itemName).'</div>'
                    .     '<div class="npc-shop-sub npc-shop-own">У тебя: <b data-own>'.number_format($own, 0, '.', '.').'</b></div>'
                    .     '<div class="npc-shop-row">'
                    .       '<div class="npc-shop-price"><img src="/img/world/items/little/'.$curId.'.png" alt="">'.number_format($sellPer, 0, '.', '.').' <span class="npc-shop-per">/шт</span></div>'
                    .       '<div class="npc-shop-qty"><input data-qty type="number" min="1" max="'.$own.'" step="1" value="1" inputmode="numeric"></div>'
                    .       '<button class="npc-shop-action secondary" data-shop-action="sell" data-item-id="'.$itemId.'">Продать</button>'
                    .     '</div>'
                    .   '</div>'
                    . '</div>';
        }

        $empty = '';
        if (!$any) {
            if ($blockedAny) {
                $empty = '<div class="npc-shop-note">У тебя есть предметы, но магазин не выкупает квестовые/ивентовые или не-торгуемые позиции.</div>';
            } else {
                $empty = '<div class="npc-shop-note">У тебя нет предметов, которые этот магазин готов выкупить.</div>';
            }
        }

        $cartbar = '<div class="npc-shop-cartbar" data-shop-cartbar>'
                 .   '<button class="npc-shop-action" data-shop-action="sell_bulk">Продать выбранное</button>'
                 .   '<div class="npc-shop-cartmeta">Выбрано: <b data-cart-count>0</b> · Сумма: <b data-cart-total>0</b></div>'
                 . '</div>';

        $html = '<div class="npc-shop" data-shop-mode="sell" data-npc-id="'.(int)$npcId.'">'
              .   '<div class="npc-shop-toolbar">'
              .     '<div class="npc-shop-tabs">'
              .       '<button class="npc-tab" onclick="NpcDialog('.(int)$npcId.',1,event);">Купить</button>'
              .       '<button class="npc-tab active" onclick="NpcDialog('.(int)$npcId.',2,event);">Продать</button>'
              .     '</div>'
              .     '<div class="npc-shop-search"><input data-shop-search type="text" placeholder="Поиск предмета"></div>'
              .     self::renderBalancesInline($uid, $currencies, $currencyNames)
              .   '</div>'
              .   self::noteHtml($note)
              .   $empty
              .   '<div class="npc-shop-grid">'.$cards.'</div>'
              .   ($any ? $cartbar : '')
              . '</div>';

        $response['question'] = $html;
        $response['answer'] = [
            0 => 'Назад',
            1 => 'Купить'
        ];
    }

    private static function getShopRow($npcId, $itemId)
    {
        $uid = self::uid();
        $locId = self::userLocationId($uid);
        $npcId = (int)$npcId;
        $itemId = (int)$itemId;

        $selectExtra = '';
        if (self::baseItemsHas('type'))    $selectExtra .= ", bi.`type` AS `item_kind`";
        if (self::baseItemsHas('trade'))   $selectExtra .= ", bi.`trade` AS `item_trade`";
        if (self::baseItemsHas('lombard')) $selectExtra .= ", bi.`lombard` AS `item_lombard`";
        if (self::baseItemsHas('rait_it')) $selectExtra .= ", bi.`rait_it` AS `item_rarity`";

        $q = self::db()->query("SELECT n.`id` AS `nid`, n.`item_id`, n.`item_price`, n.`item_count`, n.`item_type`,
                                       bi.`name` AS `item_name`, cur.`name` AS `cur_name`
                                       {$selectExtra}
                                FROM `items_npc` n
                                LEFT JOIN `base_items` bi  ON bi.`id`  = n.`item_id`
                                LEFT JOIN `base_items` cur ON cur.`id` = n.`item_type`
                                WHERE n.`npc_id` = {$npcId} AND n.`location_id` = {$locId} AND n.`item_id` = {$itemId}
                                LIMIT 1");
        $row = $q ? $q->fetch_assoc() : null;
        if (!$row) {
            $q = self::db()->query("SELECT n.`id` AS `nid`, n.`item_id`, n.`item_price`, n.`item_count`, n.`item_type`,
                                           bi.`name` AS `item_name`, cur.`name` AS `cur_name`
                                           {$selectExtra}
                                    FROM `items_npc` n
                                    LEFT JOIN `base_items` bi  ON bi.`id`  = n.`item_id`
                                    LEFT JOIN `base_items` cur ON cur.`id` = n.`item_type`
                                    WHERE n.`npc_id` = {$npcId} AND n.`item_id` = {$itemId}
                                    LIMIT 1");
            $row = $q ? $q->fetch_assoc() : null;
        }
        return $row;
    }

    private static function handleBuy($npcId, $itemId, $count)
    {
        $uid = self::uid();
        if ($uid <= 0) return ['ok'=>false, 'msg'=>'Ошибка: пользователь не найден.'];
        $itemId = (int)$itemId;
        $count = (int)$count;
        if ($itemId <= 0) return ['ok'=>false, 'msg'=>'Некорректный предмет.'];
        if ($count < 1) $count = 1;
        if ($count > 9999) $count = 9999;

        $row = self::getShopRow($npcId, $itemId);
        if (!$row) return ['ok'=>false, 'msg'=>'Этот предмет сейчас недоступен.'];

        $price = (int)$row['item_price'];
        $curId = (int)$row['item_type'];
        $stock = (int)$row['item_count'];

        if ($stock === 0) return ['ok'=>false, 'msg'=>'Товар закончился.'];
        if ($stock > 0 && $stock < $count) return ['ok'=>false, 'msg'=>'Недостаточно товара на складе.'];

        $total = $price * $count;
        if ($total < 0) $total = 0;

        if (!item_isset($curId, $total)) {
            $curName = $row['cur_name'] ? $row['cur_name'] : ('Предмет #'.$curId);
            return ['ok'=>false, 'msg'=>'Не хватает: '.$curName.' x'.number_format($total, 0, '.', '.')];
        }

        // Spend currency, give items
        minus_item($curId, $total);
        itemAdd($itemId, $count);

        // Update NPC stock (if limited)
        if ($stock > 0) {
            $nid = (int)$row['nid'];
            self::db()->query("UPDATE `items_npc` SET `item_count` = `item_count` - {$count} WHERE `id` = {$nid} AND `item_count` >= {$count}");
        }

        $itemName = $row['item_name'] ? $row['item_name'] : ('Предмет #'.$itemId);
        return [
            'ok'=>true,
            'msg'=>'Покупка успешна: '.$itemName.' x'.number_format($count, 0, '.', '.'),
            'cur_id'=>$curId,
            'item_id'=>$itemId
        ];
    }

    private static function handleSellSingle($npcId, $itemId, $count)
    {
        $uid = self::uid();
        if ($uid <= 0) return ['ok'=>false, 'msg'=>'Ошибка: пользователь не найден.'];
        $itemId = (int)$itemId;
        $count = (int)$count;
        if ($itemId <= 0) return ['ok'=>false, 'msg'=>'Некорректный предмет.'];
        if ($count < 1) $count = 1;
        if ($count > 9999) $count = 9999;

        $row = self::getShopRow($npcId, $itemId);
        if (!$row) return ['ok'=>false, 'msg'=>'Магазин не выкупает этот предмет.'];
        if (!self::isSellAllowed($row)) return ['ok'=>false, 'msg'=>'Этот предмет нельзя продать в Покемаркете.'];

        if (!item_isset($itemId, $count)) {
            return ['ok'=>false, 'msg'=>'У тебя недостаточно этого предмета.'];
        }

        $curId = (int)$row['item_type'];
        $stock = (int)$row['item_count'];

        $sellPer = self::sellPricePerRow($row);
        $total = $sellPer * $count;
        if ($total < 0) $total = 0;

        // Take items, give currency
        minus_item($itemId, $count);
        if ($total > 0) itemAdd($curId, $total);

        // Increase NPC stock (if limited)
        if ($stock >= 0) {
            $nid = (int)$row['nid'];
            self::db()->query("UPDATE `items_npc` SET `item_count` = `item_count` + {$count} WHERE `id` = {$nid}");
        }

        $itemName = $row['item_name'] ? $row['item_name'] : ('Предмет #'.$itemId);
        $curName = $row['cur_name'] ? $row['cur_name'] : ('#'.$curId);

        return [
            'ok'=>true,
            'msg'=>'Продано: '.$itemName.' x'.number_format($count, 0, '.', '.') . ' за ' . number_format($total, 0, '.', '.') . ' ('.$curName.')',
            'cur_id'=>$curId,
            'item_id'=>$itemId
        ];
    }

    private static function handleSellBulk($npcId, $itemsJson)
    {
        $uid = self::uid();
        if ($uid <= 0) return ['ok'=>false, 'msg'=>'Ошибка: пользователь не найден.'];

        $items = json_decode((string)$itemsJson, true);
        if (!is_array($items) || !$items) {
            return ['ok'=>false, 'msg'=>'Выбери предметы для продажи.'];
        }
        if (count($items) > 60) {
            return ['ok'=>false, 'msg'=>'Слишком много позиций за раз.'];
        }

        // Normalize and validate first (all-or-nothing)
        $norm = [];
        foreach ($items as $it) {
            if (!is_array($it)) continue;
            $itemId = isset($it['item_id']) ? (int)$it['item_id'] : (isset($it['id']) ? (int)$it['id'] : 0);
            $cnt = isset($it['count']) ? (int)$it['count'] : (isset($it['qty']) ? (int)$it['qty'] : 0);
            if ($itemId <= 0) continue;
            if ($cnt < 1) continue;
            if ($cnt > 9999) $cnt = 9999;
            if (!isset($norm[$itemId])) $norm[$itemId] = 0;
            $norm[$itemId] += $cnt;
            if ($norm[$itemId] > 9999) $norm[$itemId] = 9999;
        }
        if (!$norm) return ['ok'=>false, 'msg'=>'Выбери предметы для продажи.'];

        $totalByCur = [];
        $soldItems = [];

        // Pre-check ownership and eligibility
        foreach ($norm as $itemId => $cnt) {
            $row = self::getShopRow($npcId, $itemId);
            if (!$row) return ['ok'=>false, 'msg'=>'Магазин не выкупает один из выбранных предметов.'];
            if (!self::isSellAllowed($row)) return ['ok'=>false, 'msg'=>'Среди выбранных есть квестовые/ивентовые или не-торгуемые предметы.'];
            if (!item_isset($itemId, $cnt)) return ['ok'=>false, 'msg'=>'Не хватает предметов для продажи (ID '.$itemId.').'];

            $curId = (int)$row['item_type'];
            $sellPer = self::sellPricePerRow($row);
            $subTotal = $sellPer * $cnt;
            if (!isset($totalByCur[$curId])) $totalByCur[$curId] = 0;
            $totalByCur[$curId] += $subTotal;

            $soldItems[] = ['item_id'=>$itemId, 'count'=>$cnt, 'cur_id'=>$curId, 'subtotal'=>$subTotal, 'nid'=>(int)$row['nid'], 'stock'=>(int)$row['item_count']];
        }

        // Execute (best-effort atomicity by ordering operations)
        foreach ($soldItems as $si) {
            minus_item((int)$si['item_id'], (int)$si['count']);
            if ((int)$si['stock'] >= 0) {
                $nid = (int)$si['nid'];
                $cnt = (int)$si['count'];
                self::db()->query("UPDATE `items_npc` SET `item_count` = `item_count` + {$cnt} WHERE `id` = {$nid}");
            }
        }
        foreach ($totalByCur as $curId => $amount) {
            if ($amount > 0) itemAdd((int)$curId, (int)$amount);
        }

        $sumAll = 0;
        foreach ($totalByCur as $amount) $sumAll += (int)$amount;

        return [
            'ok'=>true,
            'msg'=>'Продажа завершена. Получено: '.number_format($sumAll, 0, '.', '.'),
            'bulk'=>true,
            'sold_items'=>$soldItems,
            'cur_totals'=>$totalByCur
        ];
    }

    private static function buildShopPatch($npcId, $mode, array $rows, array $affectedItemIds, array $currencyIds, $toast)
    {
        $uid = self::uid();
        $patch = [
            'type'  => 'shop',
            'npc_id'=> (int)$npcId,
            'mode'  => (string)$mode,
            'toast' => (string)$toast,
            'balances' => self::balancesMap($uid, $currencyIds),
            'cards' => [],
            'ts'    => time(),
        ];

        $aff = [];
        foreach ($affectedItemIds as $iid) $aff[(int)$iid] = true;

        foreach ($aff as $itemId => $_) {
            $row = self::getShopRow($npcId, $itemId);
            if (!$row) continue;
            $stock = (int)$row['item_count'];
            $stockLabel = ($stock < 0) ? '∞' : (string)$stock;
            $patch['cards'][] = [
                'item_id' => (int)$itemId,
                'stock'   => $stockLabel,
                'stock_raw'=> $stock,
                'own'     => self::itemCount($uid, (int)$itemId),
                'buy_disabled' => ($stock === 0)
            ];
        }

        return $patch;
    }

    /** Entry-point for numeric NPC scripts. */
    public static function run(array &$response, $npcId, $npcStep)
    {
        $npcId = (int)$npcId;
        $npcStep = (int)$npcStep;

        $note = null;
        $patch = null;

        // Shop actions (buy/sell/bulk) are posted from world.js.
        if (!empty($_POST['shop_action'])) {
            $action = (string)$_POST['shop_action'];

            $uid = self::uid();
            $locId = self::userLocationId($uid);
            $rows = self::shopRows($npcId, $locId);
            $currencyIds = self::currencyIdsFromRows($rows);

            if ($action === 'buy') {
                $itemId = isset($_POST['item_id']) ? (int)$_POST['item_id'] : 0;
                $count  = isset($_POST['count']) ? (int)$_POST['count'] : 1;
                $res = self::handleBuy($npcId, $itemId, $count);
                $note = $res['msg'];
                $npcStep = 1;
                $patch = self::buildShopPatch($npcId, 'buy', $rows, [$itemId, (int)($res['cur_id'] ?? 0)], $currencyIds, $note);
            } elseif ($action === 'sell') {
                $itemId = isset($_POST['item_id']) ? (int)$_POST['item_id'] : 0;
                $count  = isset($_POST['count']) ? (int)$_POST['count'] : 1;
                $res = self::handleSellSingle($npcId, $itemId, $count);
                $note = $res['msg'];
                $npcStep = 2;
                $curId = (int)($res['cur_id'] ?? 0);
                $patch = self::buildShopPatch($npcId, 'sell', $rows, [$itemId, $curId], $currencyIds, $note);
                // For sell view also send updated own count even if card should disappear.
            } elseif ($action === 'sell_bulk') {
                $itemsJson = isset($_POST['items']) ? (string)$_POST['items'] : '';
                $res = self::handleSellBulk($npcId, $itemsJson);
                $note = $res['msg'];
                $npcStep = 2;
                $affected = [];
                if (!empty($res['sold_items']) && is_array($res['sold_items'])) {
                    foreach ($res['sold_items'] as $si) {
                        $affected[] = (int)$si['item_id'];
                        $affected[] = (int)$si['cur_id'];
                    }
                }
                $patch = self::buildShopPatch($npcId, 'sell', $rows, $affected, $currencyIds, $note);
            }

            if ($patch) {
                $response['ui_patch'] = $patch;
            }
        }

        switch ($npcStep) {
            case 1:
                self::renderBuy($response, $npcId, $note);
                break;
            case 2:
                self::renderSell($response, $npcId, $note);
                break;
            default:
                self::renderWelcome($response, $npcId);
                break;
        }
    }
}
