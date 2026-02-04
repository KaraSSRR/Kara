<?php
/**
 * Маппинг вашей БД/структур на формат pokemon-showdown (PokemonSet / choices).
 * ВАЖНО: стараемся использовать англ. названия из таблиц base_*.
 */
class ShowdownMapper {

    private static $moveNameCache = [];
    private static $abilityNameCache = [];
    private static $itemNameCache = [];

    public static function speciesName(array $poke): string {
        $base = (string)($poke['name'] ?? $poke['base_name'] ?? '');
        $base = trim($base);
        if ($base === '') $base = 'MissingNo';

        $form = (string)($poke['form'] ?? '0');
        $form = trim($form);

        if ($form === '' || $form === '0') return $base;

        // Популярные коды форм, которые совпадают по смыслу с PS
        $map = [
            'alola' => 'Alola',
            'galar' => 'Galar',
            'hisui' => 'Hisui',
            'paldea' => 'Paldea',
            'mega' => 'Mega',
            'megax' => 'Mega-X',
            'megay' => 'Mega-Y',
            'gmax' => 'Gmax',
            'female' => 'F', // PS обычно не использует как отдельную форму, но пусть будет
        ];

        if (isset($map[$form])) {
            // Mega/Gmax/регионалки — через дефис
            if (in_array($form, ['mega','megax','megay','gmax','alola','galar','hisui','paldea'], true)) {
                return $base . '-' . $map[$form];
            }
        }

        // Если в form хранится уже PS-совместимое имя (например "Therian"), просто добавим через дефис
        // (без гарантии — это best-effort).
        $clean = preg_replace('/[^A-Za-z0-9\-]/', '', $form);
        if ($clean) return $base . '-' . ucfirst($clean);

        return $base;
    }

    public static function parse6(string $csv): array {
        $parts = array_map('trim', explode(',', (string)$csv));
        $parts = array_pad($parts, 6, '0');
        return [
            (int)$parts[0],
            (int)$parts[1],
            (int)$parts[2],
            (int)$parts[3],
            (int)$parts[4],
            (int)$parts[5],
        ];
    }

    public static function ivsFromGenes(string $genCsv): array {
        [$hp,$atk,$def,$spe,$spa,$spd] = self::parse6($genCsv);
        return ['hp'=>$hp,'atk'=>$atk,'def'=>$def,'spa'=>$spa,'spd'=>$spd,'spe'=>$spe];
    }

    public static function evsFromCsv(string $evCsv): array {
        [$hp,$atk,$def,$spe,$spa,$spd] = self::parse6($evCsv);
        return ['hp'=>$hp,'atk'=>$atk,'def'=>$def,'spa'=>$spa,'spd'=>$spd,'spe'=>$spe];
    }

    public static function movesFromAttacks(string $attacksCsv): array {
        $ids = array_filter(array_map('trim', explode(',', (string)$attacksCsv)), fn($x) => $x !== '');
        $moves = [];
        foreach ($ids as $id) {
            $id = (int)$id;
            $name = self::moveName($id);
            if ($name) $moves[] = $name;
        }
        // PS ожидает 1-4; если меньше, оставим как есть (PS сам подставит Struggle при отсутствии PP)
        return $moves;
    }

    public static function moveName(int $id): string {
        if (isset(self::$moveNameCache[$id])) return self::$moveNameCache[$id];
        if (!Work::$sql) return self::$moveNameCache[$id] = '';
        $stmt = Work::$sql->prepare("SELECT name FROM base_atk WHERE id = ? LIMIT 1");
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $res = $stmt->get_result();
        $row = $res ? $res->fetch_assoc() : null;
        $stmt->close();
        return self::$moveNameCache[$id] = (string)($row['name'] ?? '');
    }

    public static function abilityName(int $id): string {
        if (isset(self::$abilityNameCache[$id])) return self::$abilityNameCache[$id];
        if (!Work::$sql) return self::$abilityNameCache[$id] = '';
        $stmt = Work::$sql->prepare("SELECT name FROM base_ability WHERE id = ? LIMIT 1");
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $res = $stmt->get_result();
        $row = $res ? $res->fetch_assoc() : null;
        $stmt->close();
        return self::$abilityNameCache[$id] = (string)($row['name'] ?? '');
    }

    public static function itemName(int $id): string {
        if ($id <= 0) return '';
        if (isset(self::$itemNameCache[$id])) return self::$itemNameCache[$id];
        if (!Work::$sql) return self::$itemNameCache[$id] = '';
        $stmt = Work::$sql->prepare("SELECT name FROM base_items WHERE id = ? LIMIT 1");
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $res = $stmt->get_result();
        $row = $res ? $res->fetch_assoc() : null;
        $stmt->close();
        return self::$itemNameCache[$id] = (string)($row['name'] ?? '');
    }

    /**
     * Сборка PokemonSet (как в sim/TEAMS.md).
     */
    public static function pokemonSet(array $poke): array {
        $set = [
            'name' => (string)($poke['name_new'] ?? $poke['name'] ?? ''),
            'species' => self::speciesName($poke),
            'item' => self::itemName((int)($poke['item_id'] ?? 0)),
            'ability' => self::abilityName((int)($poke['ability'] ?? 0)),
            'moves' => self::movesFromAttacks((string)($poke['attacks'] ?? '')),
            'level' => (int)($poke['lvl'] ?? 1),
            'shiny' => ((string)($poke['type'] ?? '') === 'shine'),
            'evs' => self::evsFromCsv((string)($poke['evcounts'] ?? '0,0,0,0,0,0')),
            'ivs' => self::ivsFromGenes((string)($poke['gen'] ?? '31,31,31,31,31,31')),
        ];

        // Гендер — если у вас хранится как "Мальчик/Девочка" или "m/f"
        $gender = (string)($poke['gender'] ?? '');
        $gender = mb_strtolower(trim($gender));
        if ($gender === 'мальчик' || $gender === 'm' || $gender === 'male') $set['gender'] = 'M';
        if ($gender === 'девочка' || $gender === 'f' || $gender === 'female') $set['gender'] = 'F';

        return $set;
    }

    /**
     * Преобразовать id атаки в слот (1..4) по строке attacks активного покемона.
     */
    public static function moveSlotFromAttackId(array $activePoke, int $attackId): int {
        $list = array_values(array_filter(array_map('trim', explode(',', (string)($activePoke['attacks'] ?? ''))), fn($x)=>$x!==''));
        foreach ($list as $i => $idStr) {
            if ((int)$idStr === $attackId) return $i + 1;
        }
        return 1;
    }

    /**
     * Найти слот (1..N) покемона в команде по его user_pokemons.id.
     * slotMap: [id1,id2,...] в порядке формирования команды.
     */
    public static function switchSlotFromPokemonId(array $slotMap, int $pokemonId): int {
        foreach ($slotMap as $i => $id) {
            if ((int)$id === $pokemonId) return $i + 1;
        }
        return 1;
    }

    public static function parseCondition(string $cond): array {
        // Примеры: "156/200", "0 fnt", "120/200 par"
        $cond = trim($cond);
        $hpNow = 0; $hpMax = 0; $status = '';
        if (preg_match('/^(\d+)\/(\d+)(?:\s+(\w+))?$/', $cond, $m)) {
            $hpNow = (int)$m[1];
            $hpMax = (int)$m[2];
            $status = (string)($m[3] ?? '');
        } elseif (preg_match('/^(\d+)\s+(\w+)$/', $cond, $m)) {
            $hpNow = (int)$m[1];
            $hpMax = 0;
            $status = (string)($m[2] ?? '');
        }
        return [$hpNow, $hpMax, $status];
    }
}
