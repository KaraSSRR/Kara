// Карта русских клавиш к английским для ALT+Key
const RU_TO_EN = {
    'Р': 'P', 'И': 'I', 'С': 'C', 'Е': 'T', 'Й': 'Q', 'Н': 'H', 'Ы': 'S', 'М': 'V', 'Ь': 'M', 'В': 'D', 'Б': 'B', 'Х': 'X', 'А': 'F'
};

// Универсальный обработчик хоткеев
const HotkeyManager = {
    handlers: [],
    // Регистрировать хоткей: combo = 'alt+p', 'esc', 'alt+1', 'alt+x' и т.п.
    bind(combo, callback) {
        this.handlers.push({ combo: combo.toLowerCase(), callback });
    },
    // Проверка: активен ли инпут, textarea или contenteditable
    isBlockedContext() {
        const a = document.activeElement;
        return a && (
            a.tagName === 'INPUT' ||
            a.tagName === 'TEXTAREA' ||
            a.isContentEditable
        );
    },
    // Привести e к виду {alt, shift, ctrl, key} с поддержкой ру/ен
    getEventCombo(e) {
        let key = (e.key || '').toUpperCase();
        if (RU_TO_EN[key]) key = RU_TO_EN[key];
        let combo = [];
        if (e.ctrlKey) combo.push('ctrl');
        if (e.altKey) combo.push('alt');
        if (e.shiftKey) combo.push('shift');
        combo.push(key);
        return combo.join('+').toLowerCase();
    },
    // Главный обработчик
    handle(e) {
        if (this.isBlockedContext()) return;

        // Спецкейс для ESC всегда
        if ((e.key === "Escape" || e.keyCode === 27)) {
            // Можно вынести в биндинг, но для совместимости оставим тут
            if ($('.DivNotification').html() !== '') {
                $('.DivNotification .noty').remove();
            } else if (typeof closeModal === "function") {
                closeModal();
            }
            e.preventDefault();
            return false;
        }

        const combo = this.getEventCombo(e);

        // Перебор обработчиков
        for (const h of this.handlers) {
            if (combo === h.combo) {
                h.callback(e);
                e.preventDefault();
                return false;
            }
        }

        // --- Быстрые клавиши для боя ---
        if (e.altKey && $('.Battle').length > 0) {
            // 1-4: выбор атаки
            if (combo === 'alt+1') { $('.MoveBox .Move:eq(0)').trigger('click'); e.preventDefault(); return false; }
            if (combo === 'alt+2') { $('.MoveBox .Move:eq(1)').trigger('click'); e.preventDefault(); return false; }
            if (combo === 'alt+3') { $('.MoveBox .Move:eq(2)').trigger('click'); e.preventDefault(); return false; }
            if (combo === 'alt+4') { $('.MoveBox .Move:eq(3)').trigger('click'); e.preventDefault(); return false; }
            if (combo === 'alt+x') { $('.buttonFight.Button.LeaveButton').trigger('click'); e.preventDefault(); return false; }
            if (combo === 'alt+f') { $('.BattleItems .Item:eq(0)').trigger('click'); e.preventDefault(); return false; }
        }
    },
    init() {
        if (this._inited) return;
        window.addEventListener('keydown', e => this.handle(e));
        this._inited = true;
    }
};

// --- Пример биндингов для твоей игры ---
HotkeyManager.bind('alt+p', () => Game.modals.pokemons && Game.modals.pokemons());
HotkeyManager.bind('alt+i', () => Game.modals.inventory && Game.modals.inventory('all'));
HotkeyManager.bind('alt+c', () => typeof openModal === "function" && openModal('craft'));
HotkeyManager.bind('alt+t', () => typeof openModal === "function" && openModal('trainers'));
HotkeyManager.bind('alt+q', () => Game.modals.diary && Game.modals.diary('quests'));
HotkeyManager.bind('alt+h', () => typeof recover === "function" && recover());
HotkeyManager.bind('alt+s', () => typeof settings === "function" && settings());
HotkeyManager.bind('alt+v', () => typeof goLocationTelep === "function" && goLocationTelep(3));
HotkeyManager.bind('alt+m', () => typeof openModal === "function" && openModal('map'));
HotkeyManager.bind('alt+d', () => typeof openModal === "function" && openModal('pokedex'));
HotkeyManager.bind('alt+b', () => Game.modals.inventory && Game.modals.inventory('bag'));

// Инициализация один раз
HotkeyManager.init();