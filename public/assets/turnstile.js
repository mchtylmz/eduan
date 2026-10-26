(function (root) {
    'use strict';

    let sdk;
    function load() {
        if (root.turnstile) return Promise.resolve(root.turnstile);
        if (sdk) return sdk;
        sdk = new Promise((resolve, reject) => {
            const script = document.createElement('script');
            script.src = 'https://challenges.cloudflare.com/turnstile/v0/api.js?render=explicit';
            script.async = true;
            script.defer = true;
            script.onload = () => root.turnstile ? resolve(root.turnstile) : reject(new Error('unavailable'));
            script.onerror = () => { script.remove(); reject(new Error('unavailable')); };
            document.head.appendChild(script);
        }).catch((error) => { sdk = null; throw error; });
        return sdk;
    }

    root.eduanTurnstile = function (wire, action, sitekey, language) {
        return {
            failed: false,
            widget: null,
            alive: false,
            async init() {
                this.alive = true;
                try {
                    const api = await load();
                    if (!this.alive || this.widget !== null) return;
                    this.widget = api.render(this.$refs.widget, {
                        sitekey, action, language, theme: 'light',
                        size: this.$refs.widget.clientWidth < 300 ? 'compact' : 'flexible',
                        callback: (token) => { this.failed = false; wire.$set('turnstileToken', token, false); },
                        'expired-callback': () => this.clear(),
                        'timeout-callback': () => { this.clear(); this.failed = true; },
                        'error-callback': () => { this.clear(); this.failed = true; return true; }
                    });
                } catch (_) {
                    if (this.alive) { this.clear(); this.failed = true; }
                }
            },
            clear() { wire.$set('turnstileToken', '', false); },
            reset() {
                this.clear();
                if (this.widget !== null && root.turnstile) root.turnstile.reset(this.widget);
            },
            retry() {
                this.failed = false;
                if (this.widget !== null) this.reset();
                else this.init();
            },
            destroy() {
                this.alive = false;
                if (this.widget !== null && root.turnstile) root.turnstile.remove(this.widget);
                this.widget = null;
            }
        };
    };
})(window);
