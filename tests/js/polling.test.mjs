import { test } from 'node:test';
import assert from 'node:assert/strict';
import { readFile } from 'node:fs/promises';

const source = await readFile(new URL('../../resources/js/modules/polling.js', import.meta.url), 'utf8');
const { visiblePoll } = await import(`data:text/javascript;base64,${Buffer.from(source).toString('base64')}`);

function environment(t, visibility = 'visible') {
    const document = new EventTarget();
    document.visibilityState = visibility;
    const window = new EventTarget();
    // Each test has a fresh lifecycle target and a deterministic timer clock.
    globalThis.document = document;
    globalThis.window = window;
    t.mock.timers.enable({ apis: ['setTimeout'] });
    t.after(() => { delete globalThis.document; delete globalThis.window; });
    return {
        hide() { document.visibilityState = 'hidden'; document.dispatchEvent(new Event('visibilitychange')); },
        show() { document.visibilityState = 'visible'; document.dispatchEvent(new Event('visibilitychange')); },
        leave() { window.dispatchEvent(new Event('pagehide')); },
    };
}
const settled = async () => { await Promise.resolve(); await Promise.resolve(); await Promise.resolve(); };

test('polling starts immediately for an active page and pauses while hidden', async t => {
    const page = environment(t);
    let requests = 0;
    const poller = visiblePoll(async () => { requests++; }, 1000);
    poller.start();
    await settled();
    assert.equal(requests, 1);
    t.mock.timers.tick(1000);
    await settled();
    assert.equal(requests, 2);
    page.hide();
    t.mock.timers.tick(10000);
    await settled();
    assert.equal(requests, 2);
    page.show();
    await settled();
    assert.equal(requests, 3);
    poller.stop();
});

test('stopping after completion prevents timers and visibility events from restarting polling', async t => {
    const page = environment(t, 'hidden');
    let requests = 0;
    const poller = visiblePoll(async () => { requests++; poller.stop(); }, 1000);
    poller.start();
    assert.equal(requests, 0);
    page.show();
    await settled();
    assert.equal(requests, 1);
    page.hide();
    page.show();
    t.mock.timers.tick(10000);
    await settled();
    assert.equal(requests, 1);
});

test('in-flight work never overlaps and is cancelled on hiding or leaving the page', async t => {
    const page = environment(t);
    let requests = 0;
    let currentSignal;
    const poller = visiblePoll(signal => {
        requests++;
        currentSignal = signal;
        return new Promise(resolve => signal.addEventListener('abort', resolve, { once: true }));
    }, 1000);
    poller.start();
    t.mock.timers.tick(10000);
    assert.equal(requests, 1);
    page.hide();
    assert.equal(currentSignal.aborted, true);
    await settled();
    page.show();
    assert.equal(requests, 2);
    page.leave();
    assert.equal(currentSignal.aborted, true);
    await settled();
    page.hide();
    page.show();
    assert.equal(requests, 2);
});
