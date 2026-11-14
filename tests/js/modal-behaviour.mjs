// Behavioural harness for the keyboard, loading and history handling of
// resources/js/noerd-modal.js. Run by ModalBehaviourTest.php; exits non-zero on
// failure.
import assert from 'node:assert/strict';

const documentListeners = {};
const dispatched = [];
const hooks = {};
const panels = [];
const replaced = [];
const timers = [];

let appStore = { currentId: 'kept-from-the-core', setId() {} };

globalThis.document = {
    addEventListener(name, handler) {
        (documentListeners[name] ??= []).push(handler);
    },
    body: { dataset: {}, style: {} },
    documentElement: { clientWidth: 1000, style: {} },
    querySelectorAll: (selector) => (selector === '[modal]' ? panels : []),
};

const livewire = {
    dispatch: (event, params) => dispatched.push({ event, params }),
    find: () => null,
    on: () => {},
    hook: (name, callback) => {
        (hooks[name] ??= []).push(callback);
    },
};

globalThis.window = {
    addEventListener: () => {},
    innerWidth: 1000,
    scrollTo: () => {},
    Livewire: livewire,
    location: { href: 'https://example.test/list?x=1' },
    history: {
        state: { alpine: { search: 'kept' } },
        replaceState: (state, title, url) => replaced.push({ state, url }),
    },
};

globalThis.Livewire = livewire;
globalThis.setTimeout = (callback) => {
    timers.push(callback);

    return timers.length;
};
globalThis.clearTimeout = () => {};

globalThis.Alpine = {
    magic: () => {},
    store: (name, value) => {
        if (value !== undefined) {
            appStore = value;
        }

        return appStore;
    },
    effect: () => {},
};

await import('../../resources/js/noerd-modal.js');

const fire = (name, event = {}) => (documentListeners[name] ?? []).forEach((handler) => handler(event));
const runTimers = () => timers.splice(0).forEach((callback) => callback());
const closes = () => dispatched.filter((entry) => entry.event === 'closeTopModal').length;

fire('alpine:init');
fire('livewire:init');

// The store of the core is extended, never replaced.
assert.equal(appStore.currentId, 'kept-from-the-core', 'an existing store key must survive');
assert.equal(typeof appStore.setId, 'function', 'an existing store method must survive');
assert.equal(appStore.modalOpen, false, 'the modal keys are added');

/** A panel inside the Alpine scope of <x-noerd::modal> ({ open }). */
function makePanel() {
    const scope = { open: true };
    const wrapper = { _x_dataStack: [scope], parentElement: null };
    const panel = { dataset: {}, parentElement: wrapper };
    panels.push(panel);

    return { panel, scope };
}

function escape(defaultPrevented = false) {
    const event = {
        key: 'Escape',
        defaultPrevented,
        prevented: false,
        stopped: false,
        preventDefault() {
            this.prevented = true;
        },
        stopPropagation() {
            this.stopped = true;
        },
    };
    fire('keydown', event);

    return event;
}

const bottom = makePanel();
const top = makePanel();
appStore.modalOpen = true;

// An ESC a dropdown already handled does not close the modal.
escape(true);
runTimers();
assert.equal(closes(), 0, 'a handled ESC must not close the modal');

// ESC closes the top modal and does not stop the event (window listeners run).
const first = escape();
assert.equal(first.prevented, true);
assert.equal(first.stopped, false, 'ESC must reach .window listeners');
assert.equal(top.scope.open, false);
assert.equal(top.panel.dataset.closing, 'true');

// A second ESC (or the X button) during the transition closes nothing else.
escape();
window.noerdCloseTopModal();
runTimers();
assert.equal(closes(), 1, 'one modal closes per transition');
assert.equal(bottom.scope.open, true, 'the modal below stays open');

// The loading overlay ends when the stack opens nothing or a request fails.
appStore.modalLoading = true;
fire('noerd-modal-loading-end');
assert.equal(appStore.modalLoading, false);

appStore.modalLoading = true;
let failCallback = null;
hooks.request.forEach((callback) => callback({ fail: (cb) => { failCallback = cb; } }));
failCallback();
assert.equal(appStore.modalLoading, false, 'a failed request ends the overlay');

// The URL rewrite keeps the history state (Alpine's #[Url] keys).
fire('set-modal-url', { detail: { url: 'https://example.test/crm/account/5?modal=true' } });
runTimers();
fire('restore-modal-url');
assert.deepEqual(replaced.map((entry) => entry.state), [window.history.state, window.history.state]);

// wire:navigate away from an open modal releases the store.
appStore.modalOpen = true;
fire('livewire:navigated');
assert.equal(appStore.modalOpen, false, 'a navigation releases the modal flags');
