# noerd/noerd-modal

A modal system for the Noerd framework, providing stackable, responsive modals with deep Livewire/Volt integration
and YAML-driven validation.

## Key Features

- **Blade Components** -- `<x-noerd::modal>`, `<x-noerd::modal.panel>`, `<x-noerd::modal.button>`, `<x-noerd::modal.close-button>`
- **NoerdModalTrait** -- Lifecycle helpers for Livewire detail/list components (mount, close, select, store)
- **Modal Stacking** -- Multiple modals can be open simultaneously with focus management
- **YAML-Driven Validation** -- `validateFromLayout()` extracts required fields from page layout configuration
- **Responsive Design** -- Full-height on mobile, windowed or fullscreen on desktop
- **Vite-Bundled JS** -- Alpine.js integration with `$modal()` magic, auto-published to `public/vendor/noerd-modal`

## Installation

```bash
composer require noerd/noerd-modal
```
