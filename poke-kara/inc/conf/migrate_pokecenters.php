<?php
/**
 * migrate_pokecenters.php
 *
 * 1) Adds map_x/map_y columns to base_location (if absent).
 * 2) Creates city_pokecenter mapping table (if absent).
 * 3) For each city creates a Pokecenter as separate location (base_location.tipe='pokecenter'),
 *    adds a road from city -> pokecenter in loc_to, and creates loc_to row for pokecenter -> city.
 *
 * How to run:
 *   - Put this file in project root (or anywhere with access to /inc/conf/global.php)
 *   - Open in browser once (admin only) or run via CLI: php migrate_pokecenters.php
 *
 * IMPORTANT:
 *   - City IDs below are based on your provided base_location dump.
 *     If IDs differ in your DB — edit the $cities array.
 */

$patch_project = $_SERVER['DOCUMENT_ROOT'] ?: __DIR__;
$patch_global  = $patch_project . '/inc/conf/global.php';

if (!file_exists($patch_global)) {
    die("global.php not found at: {$patch_global}\n");
}
require_once($patch_global);

/** @var mysqli $mysqli */

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

function col_exists(mysqli $db, string $table, string $col): bool {
    $stmt = $db->prepare("SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ? LIMIT 1");
    $stmt->bind_param("ss", $table, $col);
    $stmt->execute();
    $res = $stmt->get_result()->fetch_row();
    return !empty($res);
}

function ensure_loc_to_row(mysqli $db, int $locId): array {
    $row = $db->query("SELECT * FROM `loc_to` WHERE `loc_id` = {$locId} LIMIT 1")->fetch_assoc();
    if (!$row) {
        $db->query("INSERT INTO `loc_to` (`loc_id`, `roads`) VALUES ({$locId}, '[]')");
        $row = $db->query("SELECT * FROM `loc_to` WHERE `loc_id` = {$locId} LIMIT 1")->fetch_assoc();
    }
    return $row ?: ['loc_id'=>$locId, 'roads'=>'[]'];
}

function json_array_int($raw): array {
    $arr = json_decode((string)$raw, true);
    if (!is_array($arr)) return [];
    $out = [];
    foreach ($arr as $v) $out[] = (int)$v;
    return array_values(array_unique($out));
}

/* --- 1) Ensure columns map_x/map_y --- */
if (!col_exists($mysqli, 'base_location', 'map_x')) {
    $mysqli->query("ALTER TABLE `base_location` ADD COLUMN `map_x` SMALLINT NULL");
    echo "Added base_location.map_x\n";
}
if (!col_exists($mysqli, 'base_location', 'map_y')) {
    $mysqli->query("ALTER TABLE `base_location` ADD COLUMN `map_y` SMALLINT NULL");
    echo "Added base_location.map_y\n";
}

/* --- 2) Mapping table --- */
$mysqli->query("
    CREATE TABLE IF NOT EXISTS `city_pokecenter` (
        `city_id` INT NOT NULL PRIMARY KEY,
        `pokecenter_id` INT NOT NULL UNIQUE,
        `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB
");
echo "Ensured city_pokecenter table\n";

/* --- 3) Cities list (EDIT IF NEEDED) --- */
$cities = [
    // city_id => city_name
    20 => 'Церулин',
    6  => 'Веридиан',
    16 => 'Пьютер',
    39 => 'Лавандия',
    38 => 'Фуксия',
    31 => 'Вермилион',
];

foreach ($cities as $cityId => $cityName) {
    $cityId = (int)$cityId;

    // If mapping exists, skip creation, but still ensure loc_to edges.
    $mapRow = $mysqli->query("SELECT * FROM `city_pokecenter` WHERE `city_id` = {$cityId} LIMIT 1")->fetch_assoc();
    $pcId = $mapRow ? (int)$mapRow['pokecenter_id'] : 0;

    // Load city data
    $city = $mysqli->query("SELECT * FROM `base_location` WHERE `id` = {$cityId} LIMIT 1")->fetch_assoc();
    if (!$city) {
        echo "City {$cityId} ({$cityName}) not found in base_location - SKIP\n";
        continue;
    }

    if ($pcId <= 0) {
        // Try find existing pokecenter by name+tipe
        $escaped = $mysqli->real_escape_string($cityName);
        $found = $mysqli->query("SELECT * FROM `base_location` WHERE `tipe`='pokecenter' AND (`name` LIKE '%{$escaped}%' OR `description` LIKE '%{$escaped}%') LIMIT 1")->fetch_assoc();
        if ($found) {
            $pcId = (int)$found['id'];
        } else {
            // Create new pokecenter row
            $region = (int)($city['region'] ?? 0);
            $weather = (int)($city['weather'] ?? 0);
            $search_tipe = $mysqli->real_escape_string($city['search_tipe'] ?? 'city');

            $name = $mysqli->real_escape_string("Покецентр — {$cityName}");
            $desc = $mysqli->real_escape_string("Покецентр города {$cityName}.");

            $mx = isset($city['map_x']) ? (int)$city['map_x'] + 18 : null;
            $my = isset($city['map_y']) ? (int)$city['map_y'] + 18 : null;

            // Insert (explicit columns to avoid несовпадение схемы)
            // If your base_location has more NOT NULL columns, add them here.
            $stmt = $mysqli->prepare("
                INSERT INTO `base_location`
                    (`name`,`description`,`region`,`pve`,`weather`,`tipe`,`search_tipe`,`map_x`,`map_y`)
                VALUES
                    (?,?,?,?,?,?,?,?,?)
            ");
            $pve = 0;
            $tipe = 'pokecenter';
            $stmt->bind_param(
                "ssiiissii",
                $name,
                $desc,
                $region,
                $pve,
                $weather,
                $tipe,
                $search_tipe,
                $mx,
                $my
            );
            $stmt->execute();
            $pcId = (int)$mysqli->insert_id;
            echo "Created Pokecenter for {$cityName}: id={$pcId}\n";
        }

        $mysqli->query("INSERT INTO `city_pokecenter` (`city_id`,`pokecenter_id`) VALUES ({$cityId}, {$pcId})
                        ON DUPLICATE KEY UPDATE `pokecenter_id`=VALUES(`pokecenter_id`)");
    }

    // Ensure loc_to rows
    $cityLocTo = ensure_loc_to_row($mysqli, $cityId);
    $pcLocTo   = ensure_loc_to_row($mysqli, $pcId);

    // City -> Pokecenter
    $roads = json_array_int($cityLocTo['roads']);
    if (!in_array($pcId, $roads, true)) {
        $roads[] = $pcId;
    }
    $mysqli->query("UPDATE `loc_to` SET `roads` = '".$mysqli->real_escape_string(json_encode(array_values($roads), JSON_UNESCAPED_UNICODE))."' WHERE `loc_id` = {$cityId}");

    // Pokecenter -> City (ONLY city)
    $mysqli->query("UPDATE `loc_to` SET `roads` = '".$mysqli->real_escape_string(json_encode([$cityId], JSON_UNESCAPED_UNICODE))."' WHERE `loc_id` = {$pcId}");

    echo "Linked {$cityName} ({$cityId}) <-> Pokecenter ({$pcId})\n";
}

echo "Done.\n";
