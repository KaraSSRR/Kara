<?php
/**
 * Возвращает относительный путь к аватарке пользователя (с учетом скина, цвета, шапки).
 * @param array $cloth — массив с полями 'model', 'skin', 'color', 'hat'
 * @param string $sex — пол ('m' или 'f')
 * @return string — путь к аватарке (например: img/avatars/model/ava/2/2a1/2a1a.png)
 */
function getAvatarPath($cloth, $sex = 'm') {
    // Значения по умолчанию
    $model = (isset($cloth['model']) && is_numeric($cloth['model']) && intval($cloth['model']) > 0) ? intval($cloth['model']) : null;
    $skin  = (isset($cloth['skin']) && is_numeric($cloth['skin']) && intval($cloth['skin']) > 0) ? intval($cloth['skin']) : null;
    $color = (isset($cloth['color']) && $cloth['color'] !== '' && $cloth['color'] !== '0') ? $cloth['color'] : null;
    $hat   = (isset($cloth['hat']) && $cloth['hat'] !== '' && $cloth['hat'] !== '0') ? $cloth['hat'] : null;

    // Логика дефолтов по полу
    if ($model === null) {
        $model = ($sex === 'f') ? 1 : 5;
    }
    if ($skin === null) {
        $skin = $model;
    }
    if ($color === null) {
        $color = 'a';
    }

    $folder   = ($model . 'a' . $skin);
    $filename = $model . 'a' . $skin . $color;

    // Дефолтные папки (без "a") для стандартных аватаров
    if ($hat) {
        $path = "/img/avatars/model/ava/{$model}/hat/{$filename}{$hat}.png";
    } else {
        // Если дефолт (skin=model, color=a) — путь img/avatars/model/ava/{model}/{model}/{model}a.png
        if ($skin == $model && $color == 'a') {
            $path = "/img/avatars/model/ava/{$model}/{$model}/{$model}a.png";
        } else {
            $path = "/img/avatars/model/ava/{$model}/{$folder}/{$filename}.png";
        }
    }

    if (file_exists($_SERVER['DOCUMENT_ROOT'] . $path)) {
        // Возвращаем путь без первого слэша
        return ltrim($path, '/');
    } else {
        // Фолбэк на дефолтный файл
        return ($sex == 'f')
            ? 'img/avatars/model/ava/1/1/1a.png'
            : 'img/avatars/model/ava/5/5/5a.png';
    }
}

/**
 * Одевает скин пользователю (или обновляет запись в cloth)
 * Если был активный скин — он снимается и возвращается в инвентарь.
 * В поле cloth.skinName вносится название из базы base_items по skin и color.
 * @param mysqli $mysqli
 * @param int $userId
 * @param int $model
 * @param int $skin
 * @param string $color
 * @return bool
 */
function wearSkin($mysqli, $userId, $model, $skin, $color = 'a') {
    // 1. Снять старый скин (если был)
    removeSkinAndReturn($mysqli, $userId);

    // 2. Получаем skinName из базы base_items по skin и color
    $skinName = null;
    $stmt = $mysqli->prepare("SELECT `skinName` FROM `base_items` WHERE `skin_id`=? AND `skin_color`=? LIMIT 1");
    $stmt->bind_param("is", $skin, $color);
    $stmt->execute();
    $stmt->bind_result($skinName);
    $stmt->fetch();
    $stmt->close();

    // 3. Обновляем или создаём запись cloth
    $ex = $mysqli->query("SELECT COUNT(*) FROM `cloth` WHERE `user` = $userId")->fetch_row();
    if ($ex[0] > 0) {
        $stmt = $mysqli->prepare("UPDATE `cloth` SET `model`=?, `skin`=?, `color`=?, `skinName`=?, `hat`=NULL WHERE `user`=?");
        $stmt->bind_param("iissi", $model, $skin, $color, $skinName, $userId);
    } else {
        $stmt = $mysqli->prepare("INSERT INTO `cloth` (`user`, `model`, `skin`, `color`, `skinName`) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("iiiss", $userId, $model, $skin, $color, $skinName);
    }
    return $stmt->execute();
}

/**
 * Снимает скин (skin, color, skinName, hat становятся NULL)
 * Возвращает скин в инвентарь
 * @param mysqli $mysqli
 * @param int $userId
 * @return bool
 */
function removeSkinAndReturn($mysqli, $userId) {
    // Получаем текущий skin и color
    $stmt = $mysqli->prepare("SELECT `skin`, `color` FROM `cloth` WHERE `user`=?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $stmt->bind_result($skinId, $color);
    $stmt->fetch();
    $stmt->close();

    // Если скин надет — вернуть его в инвентарь
    if ($skinId) {
        // Найти item_id скина в базе base_items
        $querySkin = $mysqli->prepare("SELECT `id` FROM `base_items` WHERE `skin_id`=? AND `skin_color`=? LIMIT 1");
        $querySkin->bind_param("is", $skinId, $color);
        $querySkin->execute();
        $querySkin->bind_result($itemId);
        $querySkin->fetch();
        $querySkin->close();

        if ($itemId) {
            // Проверяем, есть ли уже такой предмет у игрока, если да — увеличиваем count
            $check = $mysqli->prepare("SELECT `id`, `count` FROM `items_users` WHERE `item_id`=? AND `user`=? LIMIT 1");
            $check->bind_param("ii", $itemId, $userId);
            $check->execute();
            $check->bind_result($rowId, $count);
            if ($check->fetch()) {
                $check->close();
                $upd = $mysqli->prepare("UPDATE `items_users` SET `count`=`count`+1 WHERE `id`=?");
                $upd->bind_param("i", $rowId);
                $upd->execute();
                $upd->close();
            } else {
                $check->close();
                $ins = $mysqli->prepare("INSERT INTO `items_users` (`item_id`,`count`,`user`) VALUES (?,1,?)");
                $ins->bind_param("ii", $itemId, $userId);
                $ins->execute();
                $ins->close();
            }
        }
    }

    // Снимаем скин (очищаем все поля, связанные со скином)
    $stmt = $mysqli->prepare("UPDATE `cloth` SET `skin`=NULL, `color`=NULL, `skinName`=NULL, `hat`=NULL WHERE `user`=?");
    $stmt->bind_param("i", $userId);
    return $stmt->execute();
}

/**
 * Снимает только шапку (hat)
 * @param mysqli $mysqli
 * @param int $userId
 * @return bool
 */
function removeHat($mysqli, $userId) {
    $stmt = $mysqli->prepare("UPDATE `cloth` SET `hat`=NULL WHERE `user`=?");
    $stmt->bind_param("i", $userId);
    return $stmt->execute();
}
?>