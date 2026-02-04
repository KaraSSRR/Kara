window.AntiBot = {
    // Данные активности
    mouseMoves: [],
    clickTimes: [],
    keypressTimes: [],
    focusChanges: [],
    visibilityChanges: [],
    hiddenElementsClicks: 0,
    navigatorInfo: {},
    
    // Состояние системы
    _lastSend: Date.now(),
    _sendInProgress: false,
    _suspiciousScore: 0,
    _adaptiveInterval: 120000, // Начинаем с 2 минут
    _minInterval: 30000,
    _maxInterval: 300000,
    _significantChangeThreshold: 15,
    _sessionStartTime: Date.now(),
    _isActive: true,
    _heartbeatInterval: null,
    _performanceMetrics: {
        pageLoadTime: 0,
        firstInteractionTime: 0,
        totalFocusTime: 0,
        lastFocusTime: Date.now()
    },
    
    // Конфигурация
    config: {
        maxArraySize: 50,
        mouseMoveThrottle: 100,
        batchSendSize: 5,
        heartbeatInterval: 60000, // 1 минута
        riskThresholds: {
            low: 20,
            medium: 50,
            high: 80,
            critical: 95
        },
        endpoints: {
            primary: '/do/antibot.php',
            fallback: '/antibot.php'
        }
    },

    start: function() {
        if (!this._isActive) return;
        
        this._initEventListeners();
        this._collectNavigatorInfo();
        this._createHoneyPot();
        this._startAdaptiveSending();
        this._initBeforeUnloadHandler();
        this._startHeartbeat();
        this._initPerformanceTracking();
        this._detectAutomation();
        
        console.log('🤖 AntiBot система запущена (расширенная версия v2.0)');
    },

    _initEventListeners: function() {
        let lastMouseMove = 0;
        let mouseMovementPattern = [];
        
        // Троттлинг движений мыши с анализом паттернов
        document.addEventListener('mousemove', (e) => {
            const now = Date.now();
            if (now - lastMouseMove < this.config.mouseMoveThrottle) return;
            lastMouseMove = now;
            
            const moveData = {
                x: Math.round(e.clientX),
                y: Math.round(e.clientY),
                t: now,
                buttons: e.buttons,
                movementX: e.movementX || 0,
                movementY: e.movementY || 0
            };
            
            this.mouseMoves.push(moveData);
            this._trimArray(this.mouseMoves);
            
            // Анализ паттернов движения мыши
            this._analyzeMousePattern(moveData);
            this._updateSuspiciousScore('mouse_move', -0.5); // Хорошая активность
            
            // Запись первого взаимодействия
            if (this._performanceMetrics.firstInteractionTime === 0) {
                this._performanceMetrics.firstInteractionTime = now - this._sessionStartTime;
            }
        });

        document.addEventListener('click', (e) => {
            const now = Date.now();
            const clickData = {
                t: now,
                x: e.clientX,
                y: e.clientY,
                button: e.button,
                ctrlKey: e.ctrlKey,
                shiftKey: e.shiftKey,
                altKey: e.altKey,
                target: e.target.tagName || 'UNKNOWN'
            };
            
            this.clickTimes.push(clickData);
            this._trimArray(this.clickTimes);
            
            // Проверка на скрытые элементы (ловушки)
            if (e.target.classList && e.target.classList.contains('antibot-trap')) {
                this.hiddenElementsClicks++;
                this._updateSuspiciousScore('trap_click', 50);
                this._triggerImmediateSend('trap_detected');
                console.warn('🚨 Ловушка активирована!');
            }
            
            // Анализ паттернов кликов
            this._analyzeClickPattern();
            this._analyzeClickCoordinates();
        });

        document.addEventListener('keydown', (e) => {
            const now = Date.now();
            const keyData = {
                t: now,
                key: e.key,
                code: e.code,
                ctrlKey: e.ctrlKey,
                shiftKey: e.shiftKey,
                altKey: e.altKey,
                repeat: e.repeat
            };
            
            this.keypressTimes.push(keyData);
            this._trimArray(this.keypressTimes);
            this._updateSuspiciousScore('keypress', -0.5);
            
            // Детекция автоматического ввода
            this._detectAutomaticTyping();
        });

        // Улучшенное отслеживание фокуса
        window.addEventListener('focus', () => {
            const now = Date.now();
            this.focusChanges.push({t: now, focus: true});
            this._trimArray(this.focusChanges);
            this._performanceMetrics.lastFocusTime = now;
        });

        window.addEventListener('blur', () => {
            const now = Date.now();
            this.focusChanges.push({t: now, focus: false});
            this._trimArray(this.focusChanges);
            
            // Добавляем время фокуса к общему
            this._performanceMetrics.totalFocusTime += now - this._performanceMetrics.lastFocusTime;
        });

        document.addEventListener('visibilitychange', () => {
            const now = Date.now();
            this.visibilityChanges.push({
                t: now, 
                visible: !document.hidden,
                visibilityState: document.visibilityState
            });
            this._trimArray(this.visibilityChanges);
        });

        // Дополнительные события для детекции автоматизации
        document.addEventListener('contextmenu', (e) => {
            this._updateSuspiciousScore('context_menu', -1);
        });

        // Отслеживание прокрутки
        let lastScrollTime = 0;
        window.addEventListener('scroll', () => {
            const now = Date.now();
            if (now - lastScrollTime > 100) { // Троттлинг
                this._updateSuspiciousScore('scroll', -0.5);
                lastScrollTime = now;
            }
        });

        // Детекция изменения размера окна
        window.addEventListener('resize', () => {
            this._updateSuspiciousScore('window_resize', -1);
        });
    },

    _collectNavigatorInfo: function() {
        const now = Date.now();
        
        this.navigatorInfo = {
            webdriver: !!navigator.webdriver,
            plugins: navigator.plugins ? navigator.plugins.length : 0,
            languages: navigator.languages || [],
            userAgent: navigator.userAgent || '',
            platform: navigator.platform || '',
            cookieEnabled: navigator.cookieEnabled,
            onLine: navigator.onLine,
            hardwareConcurrency: navigator.hardwareConcurrency || 0,
            deviceMemory: navigator.deviceMemory || 0,
            connection: navigator.connection ? {
                effectiveType: navigator.connection.effectiveType,
                downlink: navigator.connection.downlink,
                rtt: navigator.connection.rtt
            } : null,
            screen: {
                width: screen.width,
                height: screen.height,
                colorDepth: screen.colorDepth,
                pixelDepth: screen.pixelDepth,
                availWidth: screen.availWidth,
                availHeight: screen.availHeight
            },
            window: {
                innerWidth: window.innerWidth,
                innerHeight: window.innerHeight,
                outerWidth: window.outerWidth,
                outerHeight: window.outerHeight,
                devicePixelRatio: window.devicePixelRatio || 1
            },
            timezone: Intl.DateTimeFormat().resolvedOptions().timeZone,
            timestamp: now,
            performance: {
                timing: performance.timing ? {
                    navigationStart: performance.timing.navigationStart,
                    loadEventEnd: performance.timing.loadEventEnd,
                    domContentLoadedEventEnd: performance.timing.domContentLoadedEventEnd
                } : null,
                memory: performance.memory ? {
                    usedJSHeapSize: performance.memory.usedJSHeapSize,
                    totalJSHeapSize: performance.memory.totalJSHeapSize
                } : null
            },
            // Дополнительные проверки
            phantom: !!(window.callPhantom || window._phantom),
            selenium: !!(window.document.$cdc_asdjflasutopfhvcZLmcfl_ || window.document.documentElement.getAttribute('selenium') || window.document.documentElement.getAttribute('webdriver') || window.document.documentElement.getAttribute('driver')),
            headless: !!(window.outerHeight === 0 || window.outerWidth === 0),
            webgl: this._getWebGLInfo(),
            canvas: this._getCanvasFingerprint(),
            audioContext: this._getAudioFingerprint()
        };
        
        // Вычисляем время загрузки страницы
        if (performance.timing && performance.timing.loadEventEnd > 0) {
            this._performanceMetrics.pageLoadTime = performance.timing.loadEventEnd - performance.timing.navigationStart;
        }
        
        // Проверки на автоматизацию
        this._detectAutomationSignatures();
    },

    _getWebGLInfo: function() {
        try {
            const canvas = document.createElement('canvas');
            const gl = canvas.getContext('webgl') || canvas.getContext('experimental-webgl');
            if (!gl) return null;
            
            return {
                vendor: gl.getParameter(gl.VENDOR),
                renderer: gl.getParameter(gl.RENDERER),
                version: gl.getParameter(gl.VERSION)
            };
        } catch (e) {
            return null;
        }
    },

    _getCanvasFingerprint: function() {
        try {
            const canvas = document.createElement('canvas');
            const ctx = canvas.getContext('2d');
            ctx.textBaseline = 'top';
            ctx.font = '14px Arial';
            ctx.fillText('AntiBot fingerprint', 2, 2);
            return canvas.toDataURL().slice(-50); // Последние 50 символов
        } catch (e) {
            return null;
        }
    },

    _getAudioFingerprint: function() {
        try {
            const audioContext = new (window.AudioContext || window.webkitAudioContext)();
            const oscillator = audioContext.createOscillator();
            const analyser = audioContext.createAnalyser();
            oscillator.connect(analyser);
            oscillator.frequency.value = 1000;
            oscillator.start();
            setTimeout(() => oscillator.stop(), 100);
            return analyser.frequencyBinCount;
        } catch (e) {
            return null;
        }
    },

    _detectAutomationSignatures: function() {
        let suspiciousScore = 0;
        
        // WebDriver детекция
        if (this.navigatorInfo.webdriver) {
            suspiciousScore += 100;
            this._triggerImmediateSend('webdriver');
        }
        
        // Phantom.js детекция
        if (this.navigatorInfo.phantom) {
            suspiciousScore += 100;
            this._triggerImmediateSend('phantomjs');
        }
        
        // Selenium детекция
        if (this.navigatorInfo.selenium) {
            suspiciousScore += 100;
            this._triggerImmediateSend('selenium');
        }
        
        // Headless браузер
        if (this.navigatorInfo.headless) {
            suspiciousScore += 80;
        }
        
        // Проверка на необычные характеристики экрана
        if (screen.width === 0 || screen.height === 0) {
            suspiciousScore += 50;
        }
        
        // Проверка на отсутствие плагинов (подозрительно для обычного браузера)
        if (this.navigatorInfo.plugins === 0) {
            suspiciousScore += 10;
        }
        
        // Проверка соотношения размеров окна и экрана
        if (window.outerWidth > screen.width || window.outerHeight > screen.height) {
            suspiciousScore += 20;
        }
        
        this._updateSuspiciousScore('automation_detection', suspiciousScore);
    },

    _createHoneyPot: function() {
        // Создаем несколько типов ловушек
        
        // 1. Невидимая кнопка
        const trap1 = document.createElement('button');
        trap1.className = 'antibot-trap';
        trap1.style.cssText = `
            position: absolute !important;
            left: -9999px !important;
            top: -9999px !important;
            width: 1px !important;
            height: 1px !important;
            opacity: 0 !important;
            pointer-events: none !important;
            z-index: -1 !important;
        `;
        trap1.tabIndex = -1;
        trap1.setAttribute('aria-hidden', 'true');
        
        // 2. Скрытый контейнер с приманками
        const trapContainer = document.createElement('div');
        trapContainer.style.cssText = 'display: none !important; visibility: hidden !important;';
        trapContainer.innerHTML = `
            <input type="text" class="antibot-trap" style="position: absolute; left: -9999px;" />
            <a href="#" class="antibot-trap" style="position: absolute; left: -9999px;">Click here</a>
            <div class="antibot-trap" style="position: absolute; left: -9999px;">Hidden content</div>
            <button class="antibot-trap" style="position: absolute; left: -9999px;">Submit</button>
        `;
        
        // 3. CSS-ловушка (видимая но скрытая через CSS)
        const cssTrap = document.createElement('div');
        cssTrap.className = 'antibot-trap css-trap';
        cssTrap.innerHTML = '<span>Verify you are human</span>';
        cssTrap.style.cssText = `
            position: fixed;
            top: -100px;
            left: 50%;
            transform: translateX(-50%);
            background: #fff;
            border: 1px solid #ccc;
            padding: 10px;
            cursor: pointer;
            font-size: 12px;
            z-index: 9999;
        `;
        
        // 4. Ловушка с прозрачным оверлеем
        const overlayTrap = document.createElement('div');
        overlayTrap.className = 'antibot-trap overlay-trap';
        overlayTrap.style.cssText = `
            position: fixed;
            top: 0;
            left: 0;
            width: 1px;
            height: 1px;
            background: transparent;
            z-index: -1;
            pointer-events: auto;
        `;
        
        // Добавляем все ловушки
        if (document.body) {
            document.body.appendChild(trap1);
            document.body.appendChild(trapContainer);
            document.body.appendChild(cssTrap);
            document.body.appendChild(overlayTrap);
        } else {
            // Если body еще не готов, добавляем при загрузке DOM
            document.addEventListener('DOMContentLoaded', () => {
                document.body.appendChild(trap1);
                document.body.appendChild(trapContainer);
                document.body.appendChild(cssTrap);
                document.body.appendChild(overlayTrap);
            });
        }
    },

    _analyzeMousePattern: function(moveData) {
        if (this.mouseMoves.length < 10) return;
        
        const recent = this.mouseMoves.slice(-10);
        let straightLines = 0;
        let perfectCurves = 0;
        
        // Анализ на идеально прямые линии (подозрительно)
        for (let i = 2; i < recent.length; i++) {
            const dx1 = recent[i-1].x - recent[i-2].x;
            const dy1 = recent[i-1].y - recent[i-2].y;
            const dx2 = recent[i].x - recent[i-1].x;
            const dy2 = recent[i].y - recent[i-1].y;
            
            // Проверка на одинаковое направление (прямая линия)
            if (dx1 !== 0 && dx2 !== 0 && Math.abs(dy1/dx1 - dy2/dx2) < 0.01) {
                straightLines++;
            }
        }
        
        if (straightLines > 5) {
            this._updateSuspiciousScore('perfect_mouse_lines', 15);
        }
        
        // Проверка на отсутствие естественного дрожания мыши
        const movements = recent.map(m => Math.sqrt(m.movementX*m.movementX + m.movementY*m.movementY));
        const avgMovement = movements.reduce((a, b) => a + b, 0) / movements.length;
        
        if (avgMovement < 0.5 && recent.length > 5) {
            this._updateSuspiciousScore('too_smooth_mouse', 10);
        }
    },

    _analyzeClickCoordinates: function() {
        if (this.clickTimes.length < 5) return;
        
        const recent = this.clickTimes.slice(-5);
        let sameCoordinates = 0;
        
        // Проверка на клики в одних и тех же координатах
        for (let i = 1; i < recent.length; i++) {
            if (recent[i].x === recent[i-1].x && recent[i].y === recent[i-1].y) {
                sameCoordinates++;
            }
        }
        
        if (sameCoordinates > 2) {
            this._updateSuspiciousScore('identical_click_coordinates', 20);
        }
        
        // Проверка на клики только по центру элементов (подозрительно)
        let centerClicks = 0;
        recent.forEach(click => {
            const element = document.elementFromPoint(click.x, click.y);
            if (element) {
                const rect = element.getBoundingClientRect();
                const centerX = rect.left + rect.width / 2;
                const centerY = rect.top + rect.height / 2;
                
                if (Math.abs(click.x - centerX) < 2 && Math.abs(click.y - centerY) < 2) {
                    centerClicks++;
                }
            }
        });
        
        if (centerClicks >= 4) {
            this._updateSuspiciousScore('center_only_clicks', 15);
        }
    },

    _detectAutomaticTyping: function() {
        if (this.keypressTimes.length < 10) return;
        
        const recent = this.keypressTimes.slice(-10);
        const intervals = [];
        
        for (let i = 1; i < recent.length; i++) {
            intervals.push(recent[i].t - recent[i-1].t);
        }
        
        // Проверка на слишком равномерную скорость печати
        const avgInterval = intervals.reduce((a, b) => a + b, 0) / intervals.length;
        const variance = intervals.reduce((sum, interval) => {
            return sum + Math.pow(interval - avgInterval, 2);
        }, 0) / intervals.length;
        
        if (avgInterval < 50) {
            this._updateSuspiciousScore('superhuman_typing', 25);
        }
        
        if (variance < 10 && avgInterval < 200) {
            this._updateSuspiciousScore('robotic_typing', 20);
        }
    },

    _detectAutomation: function() {
        // Проверка различных признаков автоматизации
        const checks = [
            () => window.navigator.webdriver,
            () => window.document.$cdc_asdjflasutopfhvcZLmcfl_,
            () => window.document.documentElement.getAttribute('selenium'),
            () => window.document.documentElement.getAttribute('webdriver'),
            () => window.document.documentElement.getAttribute('driver'),
            () => window.callPhantom,
            () => window._phantom,
            () => window.phantom,
            () => window.Buffer,
            () => window.emit,
            () => window.spawn,
            () => typeof InstallTrigger !== 'undefined' && !window.sidebar, // Firefox без sidebar
        ];
        
        let automationScore = 0;
        checks.forEach(check => {
            if (check()) automationScore += 10;
        });
        
        if (automationScore > 0) {
            this._updateSuspiciousScore('automation_detected', automationScore);
        }
    },

    _startHeartbeat: function() {
        this._heartbeatInterval = setInterval(() => {
            if (this._hasSignificantChanges() || this._suspiciousScore > this.config.riskThresholds.medium) {
                this.sendStats();
            }
        }, this.config.heartbeatInterval);
    },

    _initPerformanceTracking: function() {
        // Отслеживание времени загрузки страницы
        window.addEventListener('load', () => {
            this._performanceMetrics.pageLoadTime = performance.now();
        });
    },

    sendStats: function() {
        const now = Date.now();
        
        // Защита от слишком частых отправок
        if (this._sendInProgress || (now - this._lastSend) < 10000) {
            return;
        }
        
        this._sendInProgress = true;
        this._lastSend = now;

        const data = this._buildStatsPacket();
        
        // Попытка отправки на основной endpoint
        this._sendToEndpoint(this.config.endpoints.primary, data)
            .catch(() => {
                // Fallback на запасной endpoint
                console.warn('Primary endpoint failed, trying fallback...');
                return this._sendToEndpoint(this.config.endpoints.fallback, data);
            })
            .catch(error => {
                console.warn('All endpoints failed:', error);
                // Сохраняем данные локально для повторной отправки
                this._saveForRetry(data);
            })
            .finally(() => {
                this._sendInProgress = false;
                this._clearSentData();
            });
    },

    _sendToEndpoint: function(endpoint, data) {
        return fetch(endpoint, {
            method: 'POST',
            headers: { 
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-AntiBot-Version': '2.0'
            },
            body: JSON.stringify(data),
            credentials: 'include'
        })
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP ${response.status}`);
            }
            return response.json();
        })
        .then(result => {
            this._handleResponse(result);
            return result;
        });
    },

    _saveForRetry: function(data) {
        try {
            const retryData = {
                data: data,
                timestamp: Date.now(),
                attempts: 0
            };
            localStorage.setItem('antibot_retry', JSON.stringify(retryData));
        } catch (e) {
            // Игнорируем ошибки localStorage
        }
    },

    _buildStatsPacket: function() {
        // Отправляем агрегат вместо сырых массивов: меньше трафика и нагрузки на сервер.
        const packet = {
            mouseN: this.mouseMoves.length,
            clickN: this.clickTimes.length,
            keyN: this.keypressTimes.length,
            focusN: this.focusChanges.length,
            visibleN: this.visibilityChanges.length,
            trap: this.hiddenElementsClicks,
            navigator: this.navigatorInfo,
            suspiciousScore: this._suspiciousScore,
            time: Date.now(),
            url: window.location.href,
            referrer: document.referrer,
            sessionDuration: Date.now() - this._sessionStartTime,
            version: '2.1'
        };

        // Для высокорисковых — добавляем небольшой сэмпл для диагностики (по желанию).
        if (this._suspiciousScore >= 80) {
            packet.sample = {
                mouse: this.mouseMoves.slice(-20),
                click: this.clickTimes.slice(-15),
                key: this.keypressTimes.slice(-10)
            };
        }

        return packet;
    },

    _handleResponse: function(result) {
        if (result.error) {
            if (result.error === 'suspicious') {
                console.error(`🚨 Bot detected: ${result.reason}`);
                this._onBotDetected(result.reason);
            } else if (result.error === 'rate_limit_exceeded') {
                console.warn('🕐 Rate limit exceeded, slowing down...');
                this._adaptiveInterval = Math.min(this._maxInterval, this._adaptiveInterval * 2);
            } else {
                console.warn('AntiBot error:', result.error);
            }
        } else {
            // Успешная отправка - снижаем подозрительность и нормализуем интервал
            this._updateSuspiciousScore('successful_send', -2);
            this._adaptiveInterval = Math.max(this._minInterval, this._adaptiveInterval * 0.9);
        }
    },

    _onBotDetected: function(reason) {
        console.warn(`🤖 Bot detection reason: ${reason}`);
        
        // Увеличиваем интервал отправки при детекции
        this._adaptiveInterval = this._maxInterval;
        
        // Вызываем коллбэк если определен
        if (typeof window.onBotDetected === 'function') {
            window.onBotDetected(reason);
        }
        
        // Можно добавить дополнительные действия:
        // - Показ CAPTCHA
        // - Блокировка интерфейса
        // - Перенаправление
    },

    // Остальные методы остаются без изменений...
    _trimArray: function(arr) {
        while (arr.length > this.config.maxArraySize) {
            arr.shift();
        }
    },

    _updateSuspiciousScore: function(reason, delta) {
        this._suspiciousScore = Math.max(0, Math.min(100, this._suspiciousScore + delta));
        
        // Адаптируем интервал отправки
        if (this._suspiciousScore > this.config.riskThresholds.high) {
            this._adaptiveInterval = this._minInterval;
        } else if (this._suspiciousScore > this.config.riskThresholds.medium) {
            this._adaptiveInterval = this._minInterval * 2;
        } else if (this._suspiciousScore < this.config.riskThresholds.low) {
            this._adaptiveInterval = this._maxInterval;
        }
        
        // Логирование для отладки
        if (delta > 5) {
            console.warn(`🚨 Suspicious activity: ${reason} (+${delta}), score: ${this._suspiciousScore}`);
        }
    },

    _analyzeClickPattern: function() {
        if (this.clickTimes.length < 3) return;
        
        const recent = this.clickTimes.slice(-5);
        const intervals = [];
        
        for (let i = 1; i < recent.length; i++) {
            intervals.push(recent[i].t - recent[i-1].t);
        }
        
        const avgInterval = intervals.reduce((a, b) => a + b, 0) / intervals.length;
        const variance = intervals.reduce((sum, interval) => {
            return sum + Math.pow(interval - avgInterval, 2);
        }, 0) / intervals.length;
        
        // Подозрительно равномерные клики
        if (avgInterval < 200 && variance < 100) {
            this._updateSuspiciousScore('uniform_clicks', 15);
        }
        
        // Слишком быстрые клики
        if (avgInterval < 50) {
            this._updateSuspiciousScore('fast_clicks', 25);
        }
    },

    _hasSignificantChanges: function() {
        const totalActivity = this.mouseMoves.length + 
                            this.clickTimes.length + 
                            this.keypressTimes.length + 
                            this.hiddenElementsClicks * 10;
        
        return totalActivity >= this._significantChangeThreshold || 
               this._suspiciousScore > this.config.riskThresholds.low;
    },

    _startAdaptiveSending: function() {
        const sendLoop = () => {
            if (!this._sendInProgress && this._hasSignificantChanges()) {
                this.sendStats();
            }
            
            setTimeout(sendLoop, this._adaptiveInterval);
        };
        
        setTimeout(sendLoop, this._adaptiveInterval);
    },

    _triggerImmediateSend: function(reason) {
        if (!this._sendInProgress) {
            console.warn(`🚨 Immediate send triggered: ${reason}`);
            setTimeout(() => this.sendStats(), 100);
        }
    },

    _initBeforeUnloadHandler: function() {
        window.addEventListener('beforeunload', () => {
            if (this._hasSignificantChanges()) {
                this._sendStatsSync();
            }
        });
    },

    _sendStatsSync: function() {
        const data = this._buildStatsPacket();
        
        try {
            if (navigator.sendBeacon) {
                const blob = new Blob([JSON.stringify(data)], {
                    type: 'application/json'
                });
                navigator.sendBeacon(this.config.endpoints.primary, blob);
            }
        } catch (e) {
            console.warn('Beacon send failed:', e);
        }
    },

    _clearSentData: function() {
        // Очищаем отправленные данные, оставляя только последние
        this.mouseMoves = this.mouseMoves.slice(-10);
        this.clickTimes = this.clickTimes.slice(-8);
        this.keypressTimes = this.keypressTimes.slice(-8);
        this.focusChanges = this.focusChanges.slice(-3);
        this.visibilityChanges = this.visibilityChanges.slice(-3);
        
        // Сбрасываем счетчик ловушек после отправки
        this.hiddenElementsClicks = 0;
    },

    // Публичные методы
    getCurrentScore: function() {
        return this._suspiciousScore;
    },

    getSessionInfo: function() {
        return {
            score: this._suspiciousScore,
            sessionDuration: Date.now() - this._sessionStartTime,
            totalInteractions: this.mouseMoves.length + this.clickTimes.length + this.keypressTimes.length,
            performance: this._performanceMetrics
        };
    },

    isActive: function() {
        return this._isActive && !this._sendInProgress;
    },

    forceCheck: function() {
        this._triggerImmediateSend('manual_check');
    },

    stop: function() {
        this._isActive = false;
        if (this._heartbeatInterval) {
            clearInterval(this._heartbeatInterval);
        }
        console.log('🤖 AntiBot система остановлена');
    },

    restart: function() {
        this.stop();
        setTimeout(() => {
            this._isActive = true;
            this.start();
        }, 1000);
    }
};

// Автозапуск при загрузке DOM
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
        AntiBot.start();
    });
} else {
    AntiBot.start();
}

// Глобальный доступ для отладки
window.AB = AntiBot;

// Обработка повторных отправок из localStorage
window.addEventListener('load', () => {
    try {
        const retryData = localStorage.getItem('antibot_retry');
        if (retryData) {
            const parsed = JSON.parse(retryData);
            if (Date.now() - parsed.timestamp < 300000 && parsed.attempts < 3) { // 5 минут и не более 3 попыток
                parsed.attempts++;
                localStorage.setItem('antibot_retry', JSON.stringify(parsed));
                
                setTimeout(() => {
                    AntiBot._sendToEndpoint(AntiBot.config.endpoints.primary, parsed.data)
                        .then(() => {
                            localStorage.removeItem('antibot_retry');
                        })
                        .catch(() => {
                            if (parsed.attempts >= 3) {
                                localStorage.removeItem('antibot_retry');
                            }
                        });
                }, 5000);
            } else {
                localStorage.removeItem('antibot_retry');
            }
        }
    } catch (e) {
        // Игнорируем ошибки
    }
});