// Track the scrollbar width of the document before any modal is opened.
// We capture this outside of modal lifecycle events so the value reflects
// the page state BEFORE the modal DOM is teleported into <body>, which
// would otherwise distort the measurement.
let preModalScrollbarWidth = 0;

function measurePreModalScrollbarWidth() {
    if (Alpine.store('app')?.modalOpen) {
        return;
    }
    preModalScrollbarWidth = Math.max(
        0,
        window.innerWidth - document.documentElement.clientWidth
    );
}

// The Livewire component that owns the clicked element. The modal stack
// dispatches `refreshList-{source}` when the modal closes, so the opener (a list,
// a detail) reloads once the record was created or edited. PHP openers get this
// for free — NoerdManager passes the current component — so the Alpine magics
// resolve the equivalent from the DOM instead of leaving the modal source-less.
function resolveComponentSource(el) {
    const root = el?.closest?.('[wire\\:id]');

    if (!root) return null;

    try {
        return window.Livewire?.find(root.getAttribute('wire:id'))?.__instance?.name ?? null;
    } catch (e) {
        return null;
    }
}

function dispatchNoerdModal(params, args, source, position, size) {
    params.arguments = args;
    if (source) params.source = source;
    if (position) params.position = position;
    if (size) params.size = size;
    measurePreModalScrollbarWidth();
    showModalLoading();
    Livewire.dispatch('noerdModal', params);
}

document.addEventListener('alpine:init', () => {
    // Open a Livewire component in a modal: $modal('crm::task-create-modal', {...})
    Alpine.magic('modal', (el) => {
        return (component, args = {}, source = null, position = null, size = null) => {
            dispatchNoerdModal(
                { modalComponent: component },
                args,
                source ?? resolveComponentSource(el),
                position,
                size
            );
        };
    });

    // Open the component behind a named Route::livewire() route in a modal and
    // rewrite the browser URL to it: $modalRoute('crm.account.detail', {modelId: 5})
    //
    // options.fallbackComponent — opened when the route name is not registered
    //   (e.g. the owning module is not installed)
    // options.rewriteUrl: false — resolve the route but keep the browser URL,
    //   for targets that are not addressable (e.g. a filtered list)
    Alpine.magic('modalRoute', (el) => {
        return (route, args = {}, source = null, position = null, size = null, options = {}) => {
            const params = { route };
            if (options.fallbackComponent) params.modalComponent = options.fallbackComponent;
            if (options.rewriteUrl === false) params.rewriteUrl = false;
            dispatchNoerdModal(params, args, source ?? resolveComponentSource(el), position, size);
        };
    });

    // Alpine Stores
    Alpine.store('app', {
        currentId: null,
        modalOpen: false,
        modalFullscreen: false,
        modalLoading: false,
        _modalLoadingTimeout: null,
        setId(id) {
            this.currentId = id;
        }
    });

    measurePreModalScrollbarWidth();

    // Lock background scroll when a modal opens.
    //
    // The scrollbar presence is captured BEFORE the modal DOM is teleported
    // into <body> (see `measurePreModalScrollbarWidth`), so we can decide
    // whether to preserve the scrollbar without being fooled by the injected
    // modal markup.
    //
    // When a scrollbar was present, we keep it visible on <html> via
    // `overflow-y: scroll`. This preserves the initial containing block (ICB)
    // width — fixed-positioned elements such as banners, sidebars, top bars
    // and the modal itself are sized against the ICB, so keeping the
    // scrollbar reserved prevents them from shifting horizontally when the
    // modal opens.
    //
    // When no scrollbar existed, we leave <html> alone so no scrollbar is
    // forced into view.
    Alpine.effect(() => {
        if (!Alpine.store('app').modalOpen) {
            return;
        }

        const scrollY = window.scrollY;
        document.body.dataset.scrollY = scrollY;

        if (preModalScrollbarWidth > 0) {
            document.documentElement.style.overflowY = 'scroll';
        }

        document.body.style.position = 'fixed';
        document.body.style.top = `-${scrollY}px`;
        document.body.style.width = '100%';
    });
});

// Keep the measurement fresh as the viewport/content changes between modal opens.
window.addEventListener('resize', measurePreModalScrollbarWidth);
window.addEventListener('scroll', measurePreModalScrollbarWidth, { passive: true });
document.addEventListener('DOMContentLoaded', measurePreModalScrollbarWidth);

// Capture scrollbar state right before a Livewire request, which is the last
// moment we can reliably read it before a server-rendered modal is injected.
document.addEventListener('livewire:init', () => {
    if (typeof Livewire?.hook === 'function') {
        Livewire.hook('request', () => {
            measurePreModalScrollbarWidth();
        });
    }
});

function showModalLoading() {
    const store = Alpine.store('app');
    store.modalLoading = true;
    clearTimeout(store._modalLoadingTimeout);
    store._modalLoadingTimeout = setTimeout(() => {
        store.modalLoading = false;
    }, 10000);
}

function hideModalLoading() {
    const store = Alpine.store('app');
    store.modalLoading = false;
    clearTimeout(store._modalLoadingTimeout);
}

// Catch noerdModal events dispatched from PHP ($this->dispatch)
document.addEventListener('livewire:init', () => {
    Livewire.on('noerdModal', () => {
        showModalLoading();
    });
});

document.addEventListener('set-app-id', (event) => {
    Alpine.store('app').setId(event.detail.id);
});

document.addEventListener('modal-closed-global', () => {
    Alpine.store('app').modalOpen = false;
    Alpine.store('app').modalFullscreen = false;
    const scrollY = document.body.dataset.scrollY || '0';
    document.body.style.position = '';
    document.body.style.top = '';
    document.body.style.width = '';
    document.body.style.paddingRight = '';
    document.body.style.overflowY = '';
    document.documentElement.style.overflowY = '';
    document.documentElement.style.paddingRight = '';
    window.scrollTo(0, parseInt(scrollY));
    hideModalLoading();
    // No modal is left, so no URL is owed back — drop anything the stack still
    // holds (e.g. after a wire:navigate away from an open routed modal).
    modalUrlStack.length = 0;
});

document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape' && Alpine.store('app').modalOpen) {
        event.preventDefault();
        event.stopPropagation();
        closeTopModalWithTransition();
    }
});

// Mirror the X-button close behaviour: trigger the leave transition on the
// topmost modal panel first, then dispatch closeTopModal after the animation
// has had time to play.
function closeTopModalWithTransition() {
    const panels = document.querySelectorAll('[modal]');
    const topPanel = panels[panels.length - 1];

    if (topPanel) {
        let el = topPanel;
        while (el && el !== document.body) {
            if (el._x_dataStack) {
                for (const scope of el._x_dataStack) {
                    if ('open' in scope) {
                        scope.open = false;
                        setTimeout(() => Livewire.dispatch('closeTopModal'), 200);
                        return;
                    }
                }
            }
            el = el.parentElement;
        }
    }

    Livewire.dispatch('closeTopModal');
}

// Listen for clear-modal-url-params event from close button
document.addEventListener('clear-modal-url-params', (event) => {
    clearModalUrlParams(event.detail?.modal);
});

// URL stack for modals that carry a detail-route URL (detailRoute opt-in).
// Opening such a modal rewrites the browser URL to the route via replaceState;
// closing it restores the previous list URL (LIFO for stacked modals).
const modalUrlStack = [];

document.addEventListener('set-modal-url', (event) => {
    const url = event.detail?.url;
    if (!url) return;

    // Push synchronously so a restore dispatched in the SAME tick (a Livewire
    // request that closes one modal and opens another) still finds its entry.
    const prev = new URL(window.location.href);
    (event.detail?.clearParams || []).forEach((param) => prev.searchParams.delete(param));
    modalUrlStack.push(prev.toString());

    // Only the write is deferred, past Livewire's microtask-batched #[Url]
    // replaceState of the freshly mounted modal child, so our write lands last.
    setTimeout(() => {
        window.history.replaceState({}, '', url);
    }, 0);
});

document.addEventListener('restore-modal-url', () => {
    const prev = modalUrlStack.pop();
    if (prev) {
        window.history.replaceState({}, '', prev);
    }
});

// Clear URL parameter for the specific modal component
function clearModalUrlParams(paramName) {
    if (!paramName) return;

    const url = new URL(window.location.href);

    if (url.searchParams.has(paramName)) {
        url.searchParams.delete(paramName);
        window.history.replaceState({}, '', url.toString());
    }
}
