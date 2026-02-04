<?php
require_once 'config.php';
require_once 'inc/world_boss_manager.php';

// Проверка авторизации
if (!isset($_SESSION['id'])) {
    header('Location: /login');
    exit;
}

$userId = (int)$_SESSION['id'];
$userLocation = (int)($_SESSION['location'] ?? 1);

// Получаем активных боссов
$activeBosses = WorldBossManager::getActiveBosses($userLocation);
$allBosses = WorldBossManager::getActiveBosses(); // Все боссы для просмотра

include 'header.php';
?>

<div class="world-bosses-page">
    <h1>🔥 Мировые боссы</h1>
    
    <div class="boss-filters">
        <button class="filter-btn active" data-filter="local">В моей локации</button>
        <button class="filter-btn" data-filter="all">Все активные</button>
    </div>
    
    <?php if (empty($activeBosses) && empty($allBosses)): ?>
        <div class="no-bosses">
            <h3>😴 Сейчас нет активных боссов</h3>
            <p>Боссы появляются по расписанию. Следите за уведомлениями!</p>
        </div>
    <?php endif; ?>
    
    <!-- Боссы в текущей локации -->
    <div class="boss-list" data-category="local">
        <h2>В вашей локации</h2>
        <?php if (empty($activeBosses)): ?>
            <p class="empty-message">В вашей локации нет активных боссов</p>
        <?php else: ?>
            <?php foreach ($activeBosses as $boss): ?>
                <?php include 'templates/boss_card.php'; ?>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
    
    <!-- Все боссы -->
    <div class="boss-list" data-category="all" style="display: none;">
        <h2>Все активные боссы</h2>
        <?php foreach ($allBosses as $boss): ?>
            <?php include 'templates/boss_card.php'; ?>
        <?php endforeach; ?>
    </div>
</div>

<style>
.world-bosses-page {
    max-width: 1200px;
    margin: 0 auto;
    padding: 20px;
}

.boss-filters {
    margin-bottom: 20px;
    text-align: center;
}

.filter-btn {
    background: #f0f0f0;
    border: 1px solid #ddd;
    padding: 10px 20px;
    margin: 0 5px;
    border-radius: 5px;
    cursor: pointer;
    transition: all 0.3s;
}

.filter-btn.active,
.filter-btn:hover {
    background: #007bff;
    color: white;
    border-color: #007bff;
}

.no-bosses {
    text-align: center;
    padding: 40px;
    background: #f8f9fa;
    border-radius: 10px;
    margin: 20px 0;
}

.boss-list h2 {
    border-bottom: 2px solid #007bff;
    padding-bottom: 10px;
    margin-bottom: 20px;
}

.empty-message {
    text-align: center;
    color: #666;
    font-style: italic;
    padding: 20px;
}
</style>

<script>
// Переключение фильтров
document.querySelectorAll('.filter-btn').forEach(btn => {
    btn.addEventListener('click', function() {
        const filter = this.dataset.filter;
        
        // Обновляем активную кнопку
        document.querySelectorAll('.filter-btn').forEach(b => b.classList.remove('active'));
        this.classList.add('active');
        
        // Показываем/скрываем списки
        document.querySelectorAll('.boss-list').forEach(list => {
            list.style.display = list.dataset.category === filter ? 'block' : 'none';
        });
    });
});

// Функция присоединения к рейду
function joinRaid(instanceId) {
    if (!confirm('Присоединиться к рейду против этого босса?')) return;
    
    fetch('/do/WorldBoss', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `action=join&instance_id=${instanceId}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert(data.message || 'Вы присоединились к рейду!');
            if (data.redirect) {
                window.location.href = data.redirect;
            } else {
                location.reload();
            }
        } else {
            alert(data.error || 'Произошла ошибка');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Произошла ошибка соединения');
    });
}

// Автообновление каждые 30 секунд
setInterval(() => {
    location.reload();
}, 30000);
</script>

<?php include 'footer.php'; ?>