const { test } = require('node:test');
const assert = require('node:assert/strict');
const fs = require('node:fs');
const vm = require('node:vm');

function session() {
    let elapsed = 0, interceptor, shown = 0, calls = 0;
    const modal = { classList: { contains: () => false } };
    const context = {
        window: { setInterval: () => 1, clearInterval() {}, addEventListener() {}, removeEventListener() {} },
        navigator: { onLine: true },
        document: { hidden: false, addEventListener() {}, removeEventListener() {} },
        performance: { now: () => elapsed },
        bootstrap: { Modal: { getOrCreateInstance: () => ({ show: () => shown++ }), getInstance: () => null } },
    };
    vm.runInNewContext(fs.readFileSync('public/assets/exam-session.js', 'utf8'), context);
    const wire = {
        finished: false,
        $interceptMessage: callback => { interceptor = callback; return () => {}; },
        saveAndFinish: async () => { calls++; wire.finished = true; return true; },
    };
    const view = context.window.examSession(wire);
    view.$root = { dataset: { started: 1000000, expires: 1120000, serverNow: 1030000, endedText: 'Ended' }, querySelector: () => modal };
    view.$nextTick = callback => callback();
    return { view, wire, timer: context.window.ExamTimer, advance: value => { elapsed = value; }, shown: () => shown, calls: () => calls, interceptor: () => interceptor };
}

test('reload preserves the original duration ratio instead of resetting it to 100 percent', () => {
    const { timer } = session();
    const initial = timer.state(1000000, 1120000, 1000000);
    const reopened = timer.state(1000000, 1120000, 1030000);
    assert.equal(initial.percent, 100);
    assert.equal(reopened.percent, 75);
    assert.equal(reopened.remaining, 90);
    assert.equal(reopened.text, '00:01:30');
});

test('clock advances from server time and monotonic elapsed time without device wall-clock parsing', () => {
    const state = session();
    state.view.init();
    state.advance(10000);
    state.view.updateTime();
    assert.equal(state.view.remaining, 80);
    assert.equal(state.view.timeText, '00:01:20');
    assert.equal(state.calls(), 0);
    assert.equal(state.shown(), 0);
});

test('expired, zero-length and future-start timer values stay bounded', () => {
    const { timer } = session();
    assert.equal(timer.state(1000, 2000, 2500).percent, 0);
    assert.equal(timer.state(1000, 2000, 2500).remaining, 0);
    assert.equal(timer.state(1000, 1000, 1000).percent, 0);
    assert.equal(timer.state(1000, 2000, 0).percent, 100);
    assert.equal(timer.state(1000, 2000, 1999).remaining, 1);
});

test('deadline sends one completion request and shows results only after server confirmation', async () => {
    const state = session();
    let complete;
    state.wire.saveAndFinish = () => new Promise(resolve => { complete = resolve; });
    state.view.init();
    state.advance(90000);
    state.view.updateTime();
    const pending = state.view.finishAtDeadline();
    assert.equal(state.view.finishPending, true);
    state.view.tick();
    assert.equal(state.shown(), 0);
    state.wire.finished = true;
    complete(true);
    await pending;
    assert.equal(state.view.finishPending, false);
    assert.equal(state.shown(), 1);
});

test('failed completion keeps results hidden and does not retry automatically', async () => {
    const state = session();
    state.view.init();
    state.advance(90000);
    state.view.updateTime();
    let attempts = 0;
    state.wire.saveAndFinish = async () => { attempts++; throw Error('network'); };
    await state.view.finishAtDeadline();
    state.view.tick();
    assert.equal(attempts, 1);
    assert.equal(state.view.finishFailed, true);
    assert.equal(state.shown(), 0);
    await state.view.finishAtDeadline();
    assert.equal(attempts, 2, 'explicit retry remains available');
});

test('answer save is confirmed after rendering; a network failure does not claim success', () => {
    const state = session();
    state.view.init();
    const handlers = {};
    state.interceptor()({ onSend: cb => handlers.send = cb, onSuccess: cb => handlers.success = cb,
        onFailure: cb => handlers.failure = cb, onError() {}, onCancel() {}, onFinish: cb => handlers.finish = cb });
    handlers.send({ payload: { updates: { 'results.0.2.3': '4' }, calls: [] } });
    assert.equal(state.view.saving, true);
    assert.equal(state.view.saved, false);
    handlers.success({ onRender: callback => callback() });
    handlers.finish();
    assert.equal(state.view.saved, true);
    handlers.send({ payload: { updates: { 'results.0.2.3': '5' }, calls: [] } });
    handlers.failure();
    handlers.finish();
    assert.equal(state.view.saveFailed, true);
    assert.equal(state.view.saved, false);
    assert.equal(state.view.busy, false);
});

test('manual completion failure can be explicitly retried before the deadline', async () => {
    const state = session();
    state.view.init();
    state.view.finishFailed = true;
    let attempts = 0;
    state.wire.save = async () => { attempts++; state.wire.finished = true; return true; };
    await state.view.finishAtDeadline(false);
    assert.equal(attempts, 1);
    assert.equal(state.calls(), 0, 'manual retry does not call the deadline-only action');
    assert.equal(state.view.finishFailed, false);
    assert.equal(state.shown(), 1);
});
