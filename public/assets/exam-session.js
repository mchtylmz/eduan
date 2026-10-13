(() => {
    'use strict';

    window.ExamTimer = {
        state(startedAt, expiresAt, serverNow) {
            const duration = Math.max(0, expiresAt - startedAt);
            const milliseconds = Math.max(0, expiresAt - serverNow);
            const remaining = Math.ceil(milliseconds / 1000);
            const percent = duration > 0 ? Math.min(100, milliseconds / duration * 100) : 0;
            const hours = Math.floor(remaining / 3600);
            const minutes = Math.floor(remaining % 3600 / 60);
            const seconds = remaining % 60;

            return { remaining, percent, text: [hours, minutes, seconds].map(value => String(value).padStart(2, '0')).join(':') };
        },
    };

    window.examSession = wire => ({
        menuOpen: false,
        online: navigator.onLine,
        saving: false,
        saved: false,
        busy: false,
        saveFailed: false,
        finishPending: false,
        finishFailed: false,
        completionRequested: false,
        remaining: 0,
        percent: 0,
        timeText: '--:--:--',
        interval: null,
        clock: null,
        removeInterceptor: null,
        connectionListener: null,
        visibilityListener: null,

        init() {
            this.syncClock();
            this.removeInterceptor = wire.$interceptMessage(({ onSend, onSuccess, onFailure, onError, onCancel, onFinish }) => {
                let answerRequest = false;
                let finishRequest = false;
                onSend(({ payload }) => {
                    answerRequest = Object.keys(payload.updates).some(key => key.startsWith('results.'))
                        || payload.calls.some(call => call.method === 'uncheckAnswer');
                    finishRequest = payload.calls.some(call => ['save', 'saveAndFinish'].includes(call.method));
                    this.busy = true;
                    if (answerRequest) this.saving = true;
                    if (finishRequest) this.finishPending = true;
                });
                onSuccess(({ onRender }) => onRender(() => {
                    this.syncClock();
                    if (answerRequest) {
                        this.saving = false;
                        this.saveFailed = false;
                        this.saved = !wire.finished;
                    }
                    if (wire.finished) {
                        this.finishPending = false;
                        this.finishFailed = false;
                        this.showFinished();
                    }
                }));
                const failed = () => {
                    if (answerRequest) {
                        this.saveFailed = true;
                        this.saved = false;
                    }
                    if (finishRequest) {
                        this.finishFailed = true;
                        this.finishPending = false;
                    }
                    this.saving = false;
                };
                onFailure(failed);
                onError(failed);
                onCancel(failed);
                onFinish(() => { this.busy = false; });
            });
            this.connectionListener = () => { this.online = navigator.onLine; };
            window.addEventListener('online', this.connectionListener);
            window.addEventListener('offline', this.connectionListener);
            this.visibilityListener = () => { if (!document.hidden) this.tick(); };
            document.addEventListener('visibilitychange', this.visibilityListener);
            this.interval = window.setInterval(() => this.tick(), 1000);
            this.tick();
            if (wire.finished) this.$nextTick(() => this.showFinished());
        },

        syncClock() {
            this.clock = {
                startedAt: Number(this.$root.dataset.started),
                expiresAt: Number(this.$root.dataset.expires),
                serverNow: Number(this.$root.dataset.serverNow),
                measuredAt: performance.now(),
            };
            this.updateTime();
        },

        updateTime() {
            const state = window.ExamTimer.state(this.clock.startedAt, this.clock.expiresAt,
                this.clock.serverNow + performance.now() - this.clock.measuredAt);
            this.remaining = state.remaining;
            this.percent = state.percent;
            this.timeText = this.remaining > 0 ? state.text : this.$root.dataset.endedText;
        },

        tick() {
            if (wire.finished) {
                window.clearInterval(this.interval);
                return;
            }
            this.updateTime();
            if (this.remaining <= 0 && !this.completionRequested && this.online && !this.busy) this.finishAtDeadline();
        },

        async finishAtDeadline(atDeadline = true) {
            if (this.finishPending || wire.finished || (atDeadline && this.remaining > 0) || !this.online) return;
            this.completionRequested = true;
            this.finishPending = true;
            this.finishFailed = false;
            try {
                const finished = await (atDeadline ? wire.saveAndFinish() : wire.save());
                this.finishPending = false;
                if (finished && wire.finished) {
                    this.showFinished();
                } else {
                    this.syncClock();
                    this.completionRequested = false;
                }
            } catch (_) {
                this.finishPending = false;
                this.finishFailed = true;
            }
        },

        showFinished() {
            if (!wire.finished) return;
            window.clearInterval(this.interval);
            const element = this.$root.querySelector('#finishedModal');
            if (element && !element.classList.contains('show')) {
                bootstrap.Modal.getOrCreateInstance(element, { backdrop: 'static', keyboard: false }).show();
            }
        },

        destroy() {
            window.clearInterval(this.interval);
            this.removeInterceptor?.();
            window.removeEventListener('online', this.connectionListener);
            window.removeEventListener('offline', this.connectionListener);
            document.removeEventListener('visibilitychange', this.visibilityListener);
            const modal = this.$root.querySelector('#finishedModal');
            if (modal) bootstrap.Modal.getInstance(modal)?.dispose();
        },
    });
})();
