const {test} = require('node:test');
const assert = require('node:assert/strict');
const vm = require('node:vm');
const fs = require('node:fs');
const source = fs.readFileSync(require('node:path').join(__dirname, '../../public/assets/turnstile.js'), 'utf8');

function setup(withSdk = true) {
    const rendered = [], reset = [], removed = [], scripts = [], values = [];
    const api = {render: (el, options) => {rendered.push({el, options}); return `widget-${rendered.length}`;}, reset: id => reset.push(id), remove: id => removed.push(id)};
    const window = withSdk ? {turnstile: api} : {};
    const document = {createElement: () => ({remove() {this.removed = true;}}), head: {appendChild: script => scripts.push(script)}};
    vm.runInNewContext(source, {window, document});
    function form(width = 330) {
        const value = window.eduanTurnstile({$set: (...v) => values.push(v)}, 'login', 'public-key', 'nl');
        value.$refs = {widget: {clientWidth: width}};
        return value;
    }
    return {window, api, rendered, reset, removed, scripts, values, form};
}

test('explicit widget binds token without an extra request and keeps action/locale', async () => {
    const s = setup(); const form = s.form(); await form.init();
    const opts = s.rendered[0].options;
    assert.equal(opts.action, 'login'); assert.equal(opts.language, 'nl'); assert.equal(opts.size, 'flexible');
    opts.callback('token'); assert.deepEqual(s.values.at(-1), ['turnstileToken', 'token', false]);
});
test('small containers use compact widget', async () => {
    const s = setup(); await s.form(280).init(); assert.equal(s.rendered[0].options.size, 'compact');
});
test('expiry clears token; error and timeout clear token and offer retry', async () => {
    const s = setup(); const f = s.form(); await f.init(); const o = s.rendered[0].options;
    o.callback('old'); o['expired-callback'](); assert.equal(s.values.at(-1)[1], '');
    assert.equal(o['error-callback'](), true); assert.equal(f.failed, true);
    f.retry(); assert.equal(f.failed, false); assert.deepEqual(s.reset, ['widget-1']);
    o['timeout-callback'](); assert.equal(f.failed, true); o.callback('new'); assert.equal(f.failed, false);
});
test('reset and destroy affect only own widget; repeated init does not duplicate', async () => {
    const s = setup(); const a = s.form(), b = s.form(); await a.init(); await b.init(); await a.init();
    assert.equal(s.rendered.length, 2); a.reset(); assert.deepEqual(s.reset, ['widget-1']);
    a.destroy(); assert.deepEqual(s.removed, ['widget-1']); assert.equal(b.widget, 'widget-2');
});
test('multiple pending forms share one SDK request and destroyed form never renders', async () => {
    const s = setup(false); const a = s.form(), b = s.form(); const pa = a.init(), pb = b.init();
    assert.equal(s.scripts.length, 1); a.destroy(); s.window.turnstile = s.api; s.scripts[0].onload();
    await Promise.all([pa, pb]); assert.equal(s.rendered.length, 1); assert.equal(a.widget, null);
});
test('SDK failure is visible and retry creates a fresh request', async () => {
    const s = setup(false); const f = s.form(); const p = f.init(); s.scripts[0].onerror(); await p;
    assert.equal(f.failed, true); assert.equal(s.values.at(-1)[1], '');
    const retry = f.init(); assert.equal(s.scripts.length, 2); s.window.turnstile = s.api; s.scripts[1].onload(); await retry;
    assert.equal(s.rendered.length, 1);
});
