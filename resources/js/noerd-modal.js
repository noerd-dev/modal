document.addEventListener('alpine:init', () => {
    // Modal magic
    Alpine.magic('modal', () => {
        return (component, args = {}, source = null, position = null) => {
            const params = { modalComponent: component, arguments: args };
            if (source) params.source = source;
            if (position) params.position = position;
            showModalLoading();
            Livewire.dispatch('noerdModal', params);
        };
    });

    // Alpine Stores
    Alpine.store('app', {
        currentId: null,
        modalOpen: false,
        modalLoading: false,
        _modalLoadingTimeout: null,
        setId(id) {
            this.currentId = id;
        }
    });
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
    hideModalLoading();
});

document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape' && Alpine.store('app').modalOpen) {
        event.preventDefault();
        event.stopPropagation();
        Livewire.dispatch('closeTopModal');
    }
});

// Listen for clear-modal-url-params event from close button
document.addEventListener('clear-modal-url-params', (event) => {
    clearModalUrlParams(event.detail?.modal);
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
