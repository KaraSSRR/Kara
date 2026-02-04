<?php
require_once 'config.php';
require_once 'inc/world_boss_manager.php';
require_once 'inc/boss_reward_distributor.php';

if (!isset($_SESSION['id'])) {
    header('Location: /login');
    exit;
}

$instanceId = (int)($_GET['id'] ?? 0);
if ($instanceId <= 0) {
    header('Location: /world_bosses.php');
    exit;
}

$instance = WorldBossManager::getBossInstance($instanceId);
if (!$instance) {
    header('Location: /world_bosses.php');
    exit;
}

$participants = WorldBossManager::getBossParticipants($instanceId, 100);
$userId = (int)$_SESSION['id'];
$userParticipant = null;

// Находим данные текущего пользователя
foreach ($participants as $p) {
    if ((int)$p['user_id'] === $userId) {
        $userParticipant = $p;
        break;
    }
}

$hpPercent = $instance['hp_max'] > 0 ? ($instance['hp_current'] / $instance['hp_max']) * 100 : 0;
$timeLeft = max(0, strtotime($instance['expire_time']) - time());

include 'header.php';
?>

<div class="boss-details-page">
    <h1>🔥 <?= htmlspecialchars($instance['name']) ?></h1>
    
    <!-- Основная информация о боссе -->
    <div class="boss-main-info">
        <div class="boss-image">
            <img src="/img/pokemons/animation/<?= numbPok($instance['boss_id']) ?>.png" 
                 alt="<?= htmlspecialchars($instance['name']) ?>"
                 onerror="this.src='/img/default-boss.png'">
        </div>
        
        <div class="boss-stats">
            <div class="hp-section">
                <h3>❤️ Здоровье</h3>
                <div class="hp-bar-container">
                    <?php 
                    $hpColorClass = $hpPercent > 60 ? 'hp-high' : ($hpPercent > 30 ? 'hp-medium' : 'hp-low');
                    ?>
                    <div class="hp-bar <?= $hpColorClass ?>" style="width: <?= $hpPercent ?>%"></div>
                </div>
                <div class="hp-text">
                    <?= number_format($instance['hp_current']) ?> / <?= number_format($instance['hp_max']) ?>
                    (<?= round($hpPercent, 1) ?>%)
                </div>
            </div>
            
            <div class="boss-info-grid">
                <div class="info-item">
                    <span class="label">Статус:</span>
                    <span class="value status-<?= $instance['status'] ?>">
                        <?php
                        switch($instance['status']) {
                            case 'active': echo '⚔️ Активен'; break;
                            case 'defeated': echo '✅ Побеждён'; break;
                            case 'expired': echo '⏰ Истёк'; break;
                            default: echo '❓ ' . $instance['status'];
                        }
                        ?>
                    </span>
                </div>
                
                <div class="info-item">
                    <span class="label">Участников:</span>
                    <span class="value"><?= $instance['total_participants'] ?>/<?= $instance['max_participants'] ?></span>
                </div>
                
                <div class="info-item">
                    <span class="label">Урон нанесён:</span>
                    <span class="value"><?= number_format($instance['total_damage_dealt']) ?></span>
                </div>
                
                <div class="info-item">
                    <span class="label">Уровень:</span>
                    <span class="value"><?= $instance['level_min'] ?>-<?= $instance['level_max'] ?></span>
                </div>
                
                <?php if ($instance['status'] === 'active'): ?>
                <div class="info-item">
                    <span class="label">Осталось времени:</span>
                    <span class="value time-countdown" data-time="<?= $timeLeft ?>">
                        <?= gmdate('H:i:s', $timeLeft) ?>
                    </span>
                </div>
                <?php elseif ($instance['defeat_time']): ?>
                <div class="info-item">
                    <span class="label">Побеждён:</span>
                    <span class="value"><?= date('d.m.Y H:i', strtotime($instance['defeat_time'])) ?></span>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- Описание босса -->
    <?php if (!empty($instance['description'])): ?>
    <div class="boss-description">
        <h3>📋 Описание</h3>
        <p><?= nl2br(htmlspecialchars($instance['description'])) ?></p>
    </div>
    <?php endif; ?>
    
    <!-- Участие текущего пользователя -->
    <?php if ($userParticipant): ?>
    <div class="user-participation">
        <h3>📊 Ваше участие</h3>
        <div class="participation-stats">
            <div class="stat-item">
                <span class="stat-label">Место в рейтинге:</span>
                <span class="stat-value rank-<?= min(3, (int)$userParticipant['rank_position']) ?>">
                    #<?= $userParticipant['rank_position'] ?: '—' ?>
                </span>
            </div>
            <div class="stat-item">
                <span class="stat-label">Урон нанесён:</span>
                <span class="stat-value"><?= number_format($userParticipant['damage_dealt']) ?></span>
            </div>
            <div class="stat-item">
                <span class="stat-label">Количество атак:</span>
                <span class="stat-value"><?= $userParticipant['attacks_count'] ?></span>
            </div>
            <div class="stat-item">
                <span class="stat-label">Время участия:</span>
                <span class="stat-value"><?= gmdate('H:i:s', $userParticipant['participation_duration']) ?></span>
            </div>
            <div class="stat-item">
                <span class="stat-label">Очки участия:</span>
                <span class="stat-value"><?= round($userParticipant['participation_score'], 1) ?></span>
            </div>
            <div class="stat-item">
                <span class="stat-label">Награды получены:</span>
                <span class="stat-value">
                    <?= $userParticipant['rewards_given'] ? '✅ Да' : '⏳ Ожидают' ?>
                </span>
            </div>
        </div>
    </div>
    <?php endif; ?>
    
    <!-- Таблица лидеров -->
    <div class="leaderboard">
        <h3>🏆 Таблица лидеров</h3>
        
        <?php if (empty($participants)): ?>
            <p class="empty-message">Пока нет участников</p>
        <?php else: ?>
            <table class="participants-table">
                <thead>
                    <tr>
                        <th>Место</th>
                        <th>Игрок</th>
                        <th>Урон</th>
                        <th>Атаки</th>
                        <th>Время</th>
                        <th>Очки</th>
                        <th>Награды</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($participants as $rank => $participant): ?>
                        <?php 
                        $position = $rank + 1;
                        $isCurrentUser = ((int)$participant['user_id'] === $userId);
                        $rowClass = $isCurrentUser ? 'current-user' : '';
                        if ($position <= 3) $rowClass .= ' rank-' . $position;
                        ?>
                        <tr class="<?= $rowClass ?>">
                            <td class="rank-cell">
                                <?php if ($position === 1): ?>
                                    🥇 <?= $position ?>
                                <?php elseif ($position === 2): ?>
                                    🥈 <?= $position ?>
                                <?php elseif ($position === 3): ?>
                                    🥉 <?= $position ?>
                                <?php else: ?>
                                    <?= $position ?>
                                <?php endif; ?>
                            </td>
                            <td class="user-cell">
                                <div class="user-info">
                                    <span class="user-login u-<?= $participant['user_group'] ?>">
                                        <?= htmlspecialchars($participant['login']) ?>
                                    </span>
                                    <?php if ($isCurrentUser): ?>
                                        <span class="you-badge">Вы</span>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td><?= number_format($participant['damage_dealt']) ?></td>
                            <td><?= $participant['attacks_count'] ?></td>
                            <td><?= gmdate('H:i:s', $participant['participation_duration']) ?></td>
                            <td><?= round($participant['participation_score'], 1) ?></td>
                            <td>
                                <?= $participant['rewards_given'] ? '✅' : '⏳' ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
    
    <!-- Действия -->
    <div class="boss-actions">
        <?php if ($instance['status'] === 'active'): ?>
            <?php if ($userParticipant): ?>
                <a href="/battle/world_boss/<?= $instanceId ?>" class="btn btn-primary">
                    ⚔️ Продолжить бой
                </a>
                <button class="btn btn-secondary" onclick="leaveBossRaid(<?= $instanceId ?>)">
                    🚪 Покинуть рейд
                </button>
            <?php elseif (WorldBossManager::canUserParticipate($userId, $instanceId)): ?>
                <button class="btn btn-primary" onclick="joinRaid(<?= $instanceId ?>)">
                    ⚔️ Присоединиться к рейду
                </button>
            <?php else: ?>
                <button class="btn btn-secondary" disabled>
                    🚫 Недоступно для участия
                </button>
            <?php endif; ?>
        <?php endif; ?>
        
        <a href="/world_bosses.php" class="btn btn-info">
            📋 Вернуться к списку боссов
        </a>
    </div>
</div>

<style>
.boss-details-page {
    max-width: 1200px;
    margin: 0 auto;
    padding: 20px;
}

.boss-main-info {
    display: flex;
    gap: 30px;
    margin-bottom: 30px;
    background: white;
    padding: 20px;
    border-radius: 10px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
}

.boss-image {
    flex: 0 0 150px;
    text-align: center;
}

.boss-image img {
    max-width: 120px;
    max-height: 120px;
}

.boss-stats {
    flex: 1;
}

.hp-section {
    margin-bottom: 20px;
}

.hp-bar-container {
    width: 100%;
    height: 25px;
    background: #e9ecef;
    border-radius: 12px;
    overflow: hidden;
    margin: 10px 0;
}

.hp-bar {
    height: 100%;
    border-radius: 12px;
    transition: width 0.3s ease;
}

.hp-bar.hp-high { background: linear-gradient(90deg, #28a745, #20c997); }
.hp-bar.hp-medium { background: linear-gradient(90deg, #ffc107, #fd7e14); }
.hp-bar.hp-low { background: linear-gradient(90deg, #dc3545, #e74c3c); }

.hp-text {
    text-align: center;
    font-weight: bold;
    color: #333;
}

.boss-info-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 15px;
}

.info-item {
    display: flex;
    justify-content: space-between;
    padding: 8px 12px;
    background: #f8f9fa;
    border-radius: 5px;
}

.label {
    font-weight: bold;
    color: #666;
}

.value {
    color: #333;
}

.status-active { color: #28a745; font-weight: bold; }
.status-defeated { color: #6c757d; font-weight: bold; }
.status-expired { color: #dc3545; font-weight: bold; }

.boss-description,
.user-participation,
.leaderboard {
    background: white;
    padding: 20px;
    border-radius: 10px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    margin-bottom: 20px;
}

.participation-stats {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 15px;
}

.stat-item {
    display: flex;
    justify-content: space-between;
    padding: 10px 15px;
    background: #f8f9fa;
    border-radius: 5px;
}

.stat-label {
    font-weight: bold;
    color: #666;
}

.stat-value {
    color: #333;
    font-weight: bold;
}

.rank-1 { color: #ffd700; }
.rank-2 { color: #c0c0c0; }
.rank-3 { color: #cd7f32; }

.participants-table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 15px;
}

.participants-table th,
.participants-table td {
    padding: 12px 8px;
    text-align: left;
    border-bottom: 1px solid #ddd;
}

.participants-table th {
    background: #f8f9fa;
    font-weight: bold;
    color: #333;
}

.participants-table tr.current-user {
    background: #e7f3ff;
    font-weight: bold;
}

.participants-table tr.rank-1 { background: #fff9e6; }
.participants-table tr.rank-2 { background: #f5f5f5; }
.participants-table tr.rank-3 { background: #fff2e6; }

.user-info {
    display: flex;
    align-items: center;
    gap: 8px;
}

.you-badge {
    background: #007bff;
    color: white;
    padding: 2px 6px;
    border-radius: 3px;
    font-size: 0.8em;
}

.boss-actions {
    text-align: center;
    margin-top: 30px;
}

.btn {
    padding: 12px 24px;
    border: none;
    border-radius: 5px;
    cursor: pointer;
    font-weight: bold;
    text-decoration: none;
    display: inline-block;
    margin: 0 10px;
    transition: all 0.2s;
}

.btn-primary { background: #007bff; color: white; }
.btn-primary:hover { background: #0056b3; }

.btn-secondary { background: #6c757d; color: white; }
.btn-secondary:hover { background: #545b62; }

.btn-info { background: #17a2b8; color: white; }
.btn-info:hover { background: #117a8b; }

.btn:disabled {
    opacity: 0.6;
    cursor: not-allowed;
}

.empty-message {
    text-align: center;
    color: #666;
    font-style: italic;
    padding: 20px;
}

@media (max-width: 768px) {
    .boss-main-info {
        flex-direction: column;
        text-align: center;
    }
    
    .boss-info-grid {
        grid-template-columns: 1fr;
    }
    
    .participation-stats {
        grid-template-columns: 1fr;
    }
    
    .participants-table {
        font-size: 0.9em;
    }
    
    .participants-table th,
    .participants-table td {
        padding: 8px 4px;
    }
}
</style>

<script>
// Обновление таймера
setInterval(() => {
    const timer = document.querySelector('.time-countdown');
    if (timer) {
        let timeLeft = parseInt(timer.dataset.time);
        if (timeLeft > 0) {
            timeLeft--;
            timer.dataset.time = timeLeft;
            timer.textContent = new Date(timeLeft * 1000).toISOString().substr(11, 8);
        } else {
            timer.textContent = '⏰ Истёк';
            timer.style.color = '#dc3545';
        }
    }
}, 1000);

// Функции управления рейдом
function joinRaid(instanceId) {
    if (!confirm('Присоединиться к рейду против этого босса?')) return;
    
    fetch('/do/WorldBoss', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `action=join&instance_id=${instanceId}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            alert(data.error || 'Произошла ошибка');
        }
    });
}

function leaveBossRaid(instanceId) {
    if (!confirm('Покинуть рейд? Вы можете потерять прогресс.')) return;
    
    fetch('/do/WorldBoss', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `action=leave&instance_id=${instanceId}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            window.location.href = '/world_bosses.php';
        } else {
            alert(data.error || 'Произошла ошибка');
        }
    });
}

// Автообновление каждые 30 секунд для активных рейдов
<?php if ($instance['status'] === 'active'): ?>
setInterval(() => {
    location.reload();
}, 30000);
<?php endif; ?>
</script>

<?php include 'footer.php'; ?>