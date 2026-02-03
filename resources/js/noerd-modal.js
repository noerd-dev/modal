document.addEventListener('alpine:init', () => {
    // Modal magic
    Alpine.magic('modal', () => {
        return (component, args = {}, source = null) => {
            const params = { modalComponent: component, arguments: args };
            if (source) params.source = source;
            Livewire.dispatch('noerdModal', params);
        };
    });

    // Alpine Stores
    Alpine.store('app', {
        currentId: null,
        modalOpen: false,
        setId(id) {
            this.currentId = id;
        }
    });
});

document.addEventListener('set-app-id', (event) => {
    Alpine.store('app').setId(event.detail.id);
});

document.addEventListener('modal-closed-global', () => {
    Alpine.store('app').modalOpen = false;
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
// Now receives the actual parameter name directly (e.g., 'customerId')
function clearModalUrlParams(paramName) {
    if (!paramName) return;

    const url = new URL(window.location.href);

    if (url.searchParams.has(paramName)) {
        url.searchParams.delete(paramName);
        window.history.replaceState({}, '', url.toString());
    }
}
