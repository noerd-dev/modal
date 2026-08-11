// Behavioural harness for the Alpine $modal / $modalRoute magics: every modal
// they open must be tagged with the Livewire component that owns the clicked
// element, so the stack can dispatch `refreshList-{source}` when it closes.
//
// Run by ModalSourceTest.php (node <this file>); exits non-zero on failure.
import assert from 'node:assert/strict';

const documentListeners = {};
const dispatched = [];

let appStore;

globalThis.document = {
    addEventListener(name, handler) {
        (documentListeners[name] ??= []).push(handler);
    },
    body: { dataset: {}, style: {} },
    documentElement: { clientWidth: 1000, style: {} },
    querySelectorAll: () => [],
};

const livewire = {
    dispatch: (event, params) => dispatched.push({ event, params }),
    find: (id) => (id === 'lw-list' ? { __instance: { name: 'crm::accounts-list' } } : null),
    on: () => {},
};

globalThis.window = {
    addEventListener: () => {},
    innerWidth: 1000,
    Livewire: livewire,
};

globalThis.Livewire = livewire;

const magics = {};

globalThis.Alpine = {
    magic: (name, factory) => {
        magics[name] = factory;
    },
    store: (name, value) => {
        if (value !== undefined) {
            appStore = value;
        }

        return appStore;
    },
    effect: () => {},
};

await import('../../resources/js/noerd-modal.js');

documentListeners['alpine:init'].forEach((handler) => handler());

/** An element inside a Livewire component root (<div wire:id="lw-list">). */
const elementInsideList = {
    closest: (selector) => (selector === '[wire\\:id]' ? { getAttribute: () => 'lw-list' } : null),
};

/** An element outside any Livewire component. */
const detachedElement = { closest: () => null };

const lastParams = () => dispatched[dispatched.length - 1].params;

magics.modal(elementInsideList)('crm::task-create-modal', { customerId: 5 });
assert.equal(lastParams().source, 'crm::accounts-list', '$modal must tag the opening component');

magics.modalRoute(elementInsideList)('crm.account.detail', { modelId: 5 });
assert.equal(lastParams().source, 'crm::accounts-list', '$modalRoute must tag the opening component');

magics.modal(elementInsideList)('crm::task-create-modal', {}, 'explicit-source');
assert.equal(lastParams().source, 'explicit-source', 'an explicit source must win');

magics.modalRoute(elementInsideList)('crm.account.detail', {}, 'explicit-source');
assert.equal(lastParams().source, 'explicit-source', 'an explicit source must win');

magics.modal(detachedElement)('crm::task-create-modal', {});
assert.equal(lastParams().source, undefined, 'no owning component means no source');

magics.modalRoute(detachedElement)('crm.account.detail', {}, null, null, null, {
    fallbackComponent: 'crm::account-detail',
    rewriteUrl: false,
});
assert.equal(lastParams().source, undefined, 'no owning component means no source');
assert.equal(lastParams().modalComponent, 'crm::account-detail', 'options must survive');
assert.equal(lastParams().rewriteUrl, false, 'options must survive');

assert.ok(
    dispatched.every((entry) => entry.event === 'noerdModal'),
    'the magics dispatch the noerdModal event',
);
