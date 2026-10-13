const { test } = require('node:test');
const assert = require('node:assert/strict');
const fs = require('node:fs');
const vm = require('node:vm');

function worker(version = '1.1.19') {
    const events = {};
    const writes = [];
    const deleted = [];
    const cachedOffline = { offline: true };
    const opened = [];
    let skippedWaiting = false;
    const cache = {
        add: async request => writes.push(request),
        match: async () => undefined,
        put: async (request, response) => writes.push({ request, response }),
    };
    const context = {
        URL, Request, console,
        self: {
            location: { origin: 'https://example.test' },
            PWA_CACHE_VERSION: version,
            addEventListener: (name, handler) => events[name] = handler,
            skipWaiting: async () => { skippedWaiting = true; },
            clients: { claim: async () => {} },
        },
        caches: {
            open: async name => { opened.push(name); return cache; },
            keys: async () => ['hypotenuse-pwa-1.0', 'hypotenuse-pwa-2', 'hypotenuse-pwa-1.1.19', 'other-app'],
            delete: async key => deleted.push(key),
            match: async path => path === '/offline.html' ? cachedOffline : undefined,
        },
        fetch: async () => ({ ok: true, redirected: false, headers: new Headers(), clone() { return this; } }),
    };
    vm.runInNewContext(fs.readFileSync('public/assets/pwa-sw.js', 'utf8'), context);
    return { events, context, writes, deleted, cachedOffline, cache, opened, skippedWaiting: () => skippedWaiting };
}

async function request(state, path, mode = 'navigate', method = 'GET') {
    let response;
    state.events.fetch({ request: { url: 'https://example.test' + path, mode, method }, respondWith: value => response = value });
    return response === undefined ? undefined : await response;
}

test('install caches only anonymous offline page', async () => {
    const state = worker();
    let done;
    state.events.install({ waitUntil: value => done = value });
    await done;
    assert.equal(state.writes.length, 1);
    assert.equal(state.writes[0].url, 'https://example.test/offline.html');
    assert.equal(state.writes[0].credentials, 'omit');
    assert.equal(state.skippedWaiting(), false, 'updates must wait for active clients to close');
});

test('private and public HTML are never cached', async () => {
    const state = worker();
    for (const path of ['/', '/auth/login', '/account/profile', '/account/results/exams/1', '/exam/1/start', '/test/1/start', '/admin/users']) {
        assert.equal((await request(state, path)).ok, true);
    }
    assert.equal(state.writes.length, 0);
});

test('offline navigation returns neutral offline screen', async () => {
    const state = worker();
    state.context.fetch = async () => { throw new Error('offline'); };
    assert.equal(await request(state, '/account/profile'), state.cachedOffline);
    assert.equal(state.writes.length, 0);
});

test('only static assets are cached; uploads and POST are excluded', async () => {
    const state = worker();
    assert.equal(await request(state, '/uploads/questions/1.png', 'cors'), undefined);
    assert.equal(await request(state, '/livewire/update', 'cors', 'POST'), undefined);
    await request(state, '/assets/app.css', 'cors');
    assert.equal(state.writes.length, 1);
});

test('activation deletes only this app old cache', async () => {
    const state = worker();
    let done;
    state.events.activate({ waitUntil: value => done = value });
    await done;
    assert.deepEqual(state.deleted, ['hypotenuse-pwa-1.0', 'hypotenuse-pwa-2']);
});

test('new releases use separate asset caches; current cached assets still load offline', async () => {
    const old = worker('1.1.19');
    const saved = { oldAsset: true };
    old.cache.match = async () => saved;
    old.context.fetch = async () => { throw new Error('offline'); };
    assert.equal(await request(old, '/assets/app.css', 'cors'), saved);
    assert.deepEqual(old.opened, ['hypotenuse-pwa-1.1.19']);
    const fresh = worker('1.1.20');
    assert.equal((await request(fresh, '/assets/app.css', 'cors')).ok, true);
    assert.deepEqual(fresh.opened, ['hypotenuse-pwa-1.1.20']);
    assert.equal(fresh.writes.length, 1);
});

test('cache read or write failures do not block online assets', async () => {
    const blocked = worker();
    blocked.context.caches.open = async () => { throw new Error('storage disabled'); };
    assert.equal((await request(blocked, '/assets/app.css', 'cors')).ok, true);
    const full = worker();
    full.cache.put = async () => { throw new Error('quota exceeded'); };
    assert.equal((await request(full, '/assets/app.css', 'cors')).ok, true);
});

test('HTML, private, no-store, redirects and failed responses are never stored as assets', async () => {
    for (const options of [
        { headers: new Headers({ 'content-type': 'text/html; charset=utf-8' }) },
        { headers: new Headers({ 'cache-control': 'private, max-age=30' }) },
        { headers: new Headers({ 'cache-control': 'no-store' }) },
        { redirected: true }, { ok: false },
    ]) {
        const state = worker();
        state.context.fetch = async () => ({ ok: true, redirected: false, headers: new Headers(), ...options });
        await request(state, '/assets/app.js', 'cors');
        assert.equal(state.writes.length, 0);
    }
});

test('root worker release matches APP_VERSION without changing app identity', () => {
    const root = fs.readFileSync('public/pwa-sw.js', 'utf8');
    const version = process.env.APP_VERSION || (fs.existsSync('.env')
        ? fs.readFileSync('.env', 'utf8').match(/^APP_VERSION="?([^"\r\n]+)"?$/m)?.[1] : null);
    assert.ok(/self\.PWA_CACHE_VERSION = '[\w.-]+'/.test(root));
    if (version) assert.ok(root.includes(`self.PWA_CACHE_VERSION = '${version}'`));
    const manifest = JSON.parse(fs.readFileSync('public/pwa-manifest.json', 'utf8'));
    assert.equal(manifest.id, '0123456789');
});

test('offline name is rendered as text, not HTML', () => {
    const element = { hidden: true, textContent: '' };
    const html = fs.readFileSync('public/offline.html', 'utf8');
    const script = html.match(/<script>([\s\S]*?)<\/script>/)[1];
    vm.runInNewContext(script, {
        localStorage: { getItem: () => '<img src=x onerror=alert(1)> Name' },
        document: { getElementById: () => element },
    });
    assert.equal(element.hidden, false);
    assert.equal(element.textContent, '<img src=x onerror=alert(1)> Name');
});

test('online layout stores only display name and guest view clears it', () => {
    const values = new Map();
    function page(name) {
        const events = {};
        const chain = { addClass: () => chain, removeClass: () => chain };
        vm.runInNewContext(fs.readFileSync('public/assets/app.js', 'utf8'), {
            console, navigator: {}, location: { protocol: 'https:' },
            window: { addEventListener() {}, matchMedia: () => ({ matches: false }), navigator: {} },
            document: {
                addEventListener: (event, fn) => events[event] = fn,
                querySelector: () => ({ content: name }), querySelectorAll: () => [], getElementById: () => null,
            },
            localStorage: { setItem: (key, value) => values.set(key, value), removeItem: key => values.delete(key) },
            $: () => chain,
        });
        events.DOMContentLoaded();
    }
    page('Ada Student');
    assert.deepEqual([...values], [['pwa-user-name', 'Ada Student']]);
    page('');
    assert.equal(values.size, 0);
});

test('registration uses a stable root URL and handles update errors without reload', async () => {
    const events = {}, warnings = [], calls = [];
    const chain = { addClass: () => chain, removeClass: () => chain };
    const workerApi = {
        register: async (url, options) => {
            calls.push({ url, options });
            return { update: async () => { throw new Error('update unavailable'); } };
        },
        getRegistrations: async () => [
            { scope: 'https://example.test/assets/', unregister: async () => calls.push('old-scope-removed') },
            { scope: 'https://example.test/', unregister: async () => { throw new Error('root must stay'); } },
        ],
    };
    vm.runInNewContext(fs.readFileSync('public/assets/app.js', 'utf8'), {
        URL, navigator: { serviceWorker: workerApi, onLine: false },
        location: { href: 'https://example.test/auth/login' },
        console: { warn: (...args) => warnings.push(args) },
        window: {
            addEventListener: (event, fn) => (events[event] ||= []).push(fn),
            matchMedia: () => ({ matches: false }), navigator: {},
        },
        document: {
            addEventListener: (event, fn) => (events[event] ||= []).push(fn),
            querySelector: selector => selector.includes('offline-message') ? { content: 'Geen internetverbinding' } : null,
            querySelectorAll: () => [], getElementById: () => null,
        },
        localStorage: { removeItem() {} },
        alert: message => calls.push(message), $: () => chain,
    });
    events.DOMContentLoaded[0]();
    events.load.forEach(fn => fn());
    await new Promise(resolve => setImmediate(resolve));
    assert.equal(calls[0].url, '/pwa-sw.js');
    assert.equal(calls[0].options.updateViaCache, 'none');
    assert.equal(calls[0].options.scope, '/');
    assert.ok(calls.includes('old-scope-removed'));
    assert.equal(warnings.length, 1);
    events.offline[0]();
    assert.ok(calls.includes('Geen internetverbinding'));
});
