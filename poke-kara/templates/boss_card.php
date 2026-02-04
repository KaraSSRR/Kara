<?php
// Шаблон карточки босса
$hpPercent = $boss['hp_max'] > 0 ? ($boss['hp_current'] / $boss['hp_max']) * 100 : 0;
$timeLeft = max(0, strtotime($boss['expire_time']) - time());
$canJoin = WorldBossManager::canUserParticipate($userId, $boss['id']);

// Определяем цвет HP полоски
$hpColorClass = '';
if ($hpPercent > 60) $hpColorClass = 'hp-high';
elseif ($hpPercent > 30) $hpColorClass = 'hp-medium';
else $hpColorClass = 'hp-low';
?>

<div class="boss-card" data-instance-id="<?= $boss['id'] ?>">
    <div class="boss-header">
        <div class="boss-image">
            <img src="/img/pokemons/animation/<?= numbPok($boss['boss_id']) ?>.png" 
                 alt="<?= htmlspecialchars($boss['name']) ?>"
                 onerror="this.src='/img/default-boss.png'">
        </div>
        <div class="boss-info">
            <h3><?= htmlspecialchars($boss['name']) ?></h3>
            <p class="boss-description"><?= htmlspecialchars($boss['description']) ?></p>
            <div class="boss-meta">
                <span class="location">📍 <?= htmlspecialchars($boss['location_name'] ?? 'Неизвестно') ?></span>
                <span class="level-range">⭐ Уровень <?= $boss['level_min'] ?>-<?= $boss['level_max'] ?></span>
            </div>
        </div>
    </div>
    
    <div class="boss-stats">
        <div class="hp-section">
            <div class="hp-label">
                <span>❤️ HP</span>
                <span class="hp-numbers">
                    <?= number_format($boss['hp_current']) ?> / <?= number_format($boss['hp_max']) ?>
                </span>
            </div>
            <div class="hp-bar-container">
                <div class="hp-bar <?= $hpColorClass ?>" style="width: <?= $hpPercent ?>%"></div>
            </div>
            <div class="hp-percent"><?= round($hpPercent, 1) ?>%</div>
        </div>
        
        <div class="boss-additional-info">
            <div class="info-item">
                <span class="info-label">👥 Участников:</span>
                <span class="info-value"><?= $boss['total_participants'] ?>/<?= $boss['max_participants'] ?></span>
            </div>
            <div class="info-item">
                <span class="info-label">⏰ Осталось:</span>
                <span class="info-value time-countdown" data-time="<?= $timeLeft ?>">
                    <?= gmdate('H:i:s', $timeLeft) ?>
                </span>
            </div>
            <div class="info-item">
                <span class="info-label">💥 Урон нанесён:</span>
                <span class="info-value"><?= number_format($boss['total_damage_dealt']) ?></span>
            </div>
        </div>
    </div>
    
    <div class="boss-actions">
        <?php if ($boss['status'] === 'defeated'): ?>
            <button class="btn btn-success" disabled>✅ Побеждён</button>
        <?php elseif (!$canJoin): ?>
            <button class="btn btn-secondary" disabled title="Не можете участвовать">
                🚫 Недоступно
            </button>
        <?php else: ?>
            <button class="btn btn-primary" onclick="joinRaid(<?= $boss['id'] ?>)">
                ⚔️ Присоединиться к рейду
            </button>
        <?php endif; ?>
        
        <button class="btn btn-info" onclick="showBossDetails(<?= $boss['id'] ?>)">
            📊 Подробности
        </button>
    </div>
</div>

<style>
.boss-card {
    border: 1px solid #ddd;
    border-radius: 10px;
    margin-bottom: 20px;
    background: white;
    box-shadow: 0 2px 5px rgba(0,0,0,0.1);
    transition: transform 0.2s;
}

.boss-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 10px rgba(0,0,0,0.15);
}

.boss-header {
    display: flex;
    padding: 15px;
    border-bottom: 1px solid #eee;
}

.boss-image {
    width: 80px;
    height: 80px;
    margin-right: 15px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: #f8f9fa;
    border-radius: 10px;
}

.boss-image img {
    max-width: 70px;
    max-height: 70px;
}

.boss-info {
    flex: 1;
}

.boss-info h3 {
    margin: 0 0 5px 0;
    color: #333;
    font-size: 1.3em;
}

.boss-description {
    color: #666;
    margin: 5px 0;
    font-size: 0.9em;
}

.boss-meta {
    display: flex;
    gap: 15px;
    margin-top: 8px;
}

.boss-meta span {
    font-size: 0.85em;
    color: #777;
}

.boss-stats {
    padding: 15px;
}

.hp-section {
    margin-bottom: 15px;
}

.hp-label {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 5px;
    font-weight: bold;
}

.hp-bar-container {
    width: 100%;
    height: 20px;
    background: #e9ecef;
    border-radius: 10px;
    overflow: hidden;
    position: relative;
}

.hp-bar {
    height: 100%;
    border-radius: 10px;
    transition: width 0.3s ease;
}

.hp-bar.hp-high { background: linear-gradient(90deg, #28a745, #20c997); }
.hp-bar.hp-medium { background: linear-gradient(90deg, #ffc107, #fd7e14); }
.hp-bar.hp-low { background: linear-gradient(90deg, #dc3545, #e74c3c); }

.hp-percent {
    text-align: center;
    font-size: 0.8em;
    color: #666;
    margin-top: 2px;
}

.boss-additional-info {
    display: grid;
    grid-template-columns: 1fr 1fr 1fr;
    gap: 10px;
}

.info-item {
    text-align: center;
    padding: 8px;
    background: #f8f9fa;
    border-radius: 5px;
}

.info-label {
    display: block;
    font-size: 0.8em;
    color: #666;
    margin-bottom: 2px;
}

.info-value {
    display: block;
    font-weight: bold;
    color: #333;
}

.boss-actions {
    padding: 15px;
    border-top: 1px solid #eee;
    display: flex;
    gap: 10px;
    justify-content: center;
}

.btn {
    padding: 8px 16px;
    border: none;
    border-radius: 5px;
    cursor: pointer;
    font-weight: bold;
    transition: all 0.2s;
    text-decoration: none;
}

.btn:disabled {
    opacity: 0.6;
    cursor: not-allowed;
}

.btn-primary { background: #007bff; color: white; }
.btn-primary:hover:not(:disabled) { background: #0056b3; }

.btn-info { background: #17a2b8; color: white; }
.btn-info:hover:not(:disabled) { background: #117a8b; }

.btn-success { background: #28a745; color: white; }
.btn-secondary { background: #6c757d; color: white; }

@media (max-width: 768px) {
    .boss-header {
        flex-direction: column;
        text-align: center;
    }
    
    .boss-image {
        margin: 0 auto 10px auto;
    }
    
    .boss-additional-info {
        grid-template-columns: 1fr;
    }
    
    .boss-actions {
        flex-direction: column;
    }
}
</style>

<script>
// Обновление таймеров каждую секунду
setInterval(() => {
    document.querySelectorAll('.time-countdown').forEach(timer => {
        let timeLeft = parseInt(timer.dataset.time);
        if (timeLeft > 0) {
            timeLeft--;
            timer.dataset.time = timeLeft;
            timer.textContent = new Date(timeLeft * 1000).toISOString().substr(11, 8);
        } else {
            timer.textContent = '⏰ Истёк';
            timer.style.color = '#dc3545';
        }
    });
}, 1000);

function showBossDetails(instanceId) {
    // Можно открыть модальное окно с подробной информацией
    window.location.href = `/world_boss_details.php?id=${instanceId}`;
}
</script>