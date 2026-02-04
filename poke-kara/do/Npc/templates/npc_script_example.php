<?php
// Example NEW NPC script using lib-kits.
// Copy this file, rename to <npc_id>.php or your preferred file, and wire it from index.php route.

NpcKit::strict();

$npcId = isset($npc_id) ? (int)$npc_id : 0;
$stepKey = isset($npcStep) ? $npcStep : (isset($_POST['step']) ? $_POST['step'] : null);
$npcRow = NpcKit::loadNpc($npcId);
if (!$npcRow) {
    NpcKit::error($response, 1, 'NPC not found');
    return;
}

$response = NpcKit::responseBase($npcRow);

// Linear storyline example
$storyCode = 'main';
$story = StoryKit::ensure($storyCode, 1);
$step = StoryKit::step($storyCode);

switch ($step) {
    case 1:
        NpcKit::say($response, 'Привет! Это пример диалога.', 'Выберите действие:');
        NpcKit::answers($response, [
            [2, 'Продолжить сюжет'],
            ['fight', 'Начать бой (пример)'],
            ['close', 'Закрыть']
        ]);
        break;

    default:
        // Interpret custom keys
        if (!empty($stepKey) && $stepKey === 'fight') {
            // Example enemy pack
            $enemies = [
                ['basenum' => 10, 'lvl' => 3, 'numb' => 0, 'boss' => 0, 'catch' => 0],
                ['basenum' => 13, 'lvl' => 3, 'numb' => 0, 'boss' => 0, 'catch' => 0],
            ];
            $battleId = NpcBattle::start($enemies, ['battle_type' => 'pve']);
            NpcKit::openBattle($response, $battleId);
            return;
        }
        if (!empty($stepKey) && $stepKey === 'close') {
            NpcKit::close($response);
            return;
        }

        StoryKit::setStep($storyCode, $step + 1);
        NpcKit::say($response, 'Шаг сюжета обновлён.', '');
        NpcKit::close($response, 2);
        break;
}
