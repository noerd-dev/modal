@verbatim
## Noerd Modal Module

The Noerd Modal module is the stacked modal system of noerd (Composer package `noerd/modal`,
namespace `NoerdModal`): any Livewire component is opened in a modal without traits or changes
to the component. It is a SUPPORT package — no tenant app, no `app-configs/`, no migrations, no
`navigation.yml`. It depends only on Laravel and Livewire 4; `noerd/noerd` REQUIRES `noerd/modal`,
never the other way round (guarded by `tests/Feature/ModuleBoundaryTest.php`). The framework
rules for WHEN to open a route modal vs. a component modal and the mandatory modal chrome come
from the `noerd/noerd` guideline — this block only adds what is specific to this package.

### Public API
- Layout wiring: `<x-noerd::noerd-modal-assets/>` in `<head>` (loads the bundle through the asset
  route, or from the Vite dev server while the hot file exists) and `<livewire:noerd-modal/>`
  inside an `x-data` ancestor (`<body x-data>`).
- The stack is ONE Livewire component, `noerd-modal` (`resources/views/components/noerd-modal.blade.php`,
  `#[Isolate]`, also reachable as `noerd-modal::noerd-modal`). It opens on the Livewire event
  `noerdModal` (`bootModal(modalComponent, arguments, source, position, size, route, rewriteUrl)`):
  at least one of `modalComponent` (component name) or `route` (name of a `Route::livewire()`
  route) is required. A route is resolved to the component behind it (`livewire_component` route
  action) and the browser URL is rewritten to the route `+ ?modal=true`; given both, the route
  wins and `modalComponent` is the fallback for an unregistered route. `rewriteUrl: false` keeps
  the browser URL. A non-array `arguments` value is treated as `['modelId' => value]`. The event
  takes NO `url`: the browser URL is derived from the route on the server only. An event that opens
  nothing dispatches `noerd-modal-loading-end` (the loading overlay never takes pointer events and
  also ends on a failed Livewire request).
- Alpine magics (`resources/js/noerd-modal.js`): `$modal(component, args, source, position, size)`
  and `$modalRoute(route, args, source, position, size, {fallbackComponent, rewriteUrl})`. Both
  resolve `source` from the closest `[wire:id]` when not given, so the opener receives
  `refreshList-{source}` on close.
- The PHP side (`Noerd::modal()`, `Noerd::modalRoute()`, `Noerd::modalFor()`) lives in
  `noerd/noerd` (`Noerd\Services\NoerdManager`) and only dispatches the same `noerdModal` event —
  this package owns the event contract and the rendering, never the PHP facade.
- Closing: dispatch `closeTopModal` (top of the stack, LIFO; also bound to ESC and the X button)
  or `closeAllModals` — the stack has no other public close method (removing one entry is private).
  ESC closes one modal per press (the closing panel is marked `data-closing`), ignores an Escape an
  element already handled (`event.defaultPrevented`) and never stops the event. Closing clears the `#[Url]` / `queryString*()` parameters of the closed
  component from the URL (`clear-modal-url-params`), restores the previous URL of a routed modal
  (`restore-modal-url`, LIFO stack in JS) and dispatches `refreshList-{source}` for the opener.
  `modal-closed-global` fires when the stack is empty.
- `resizeTopModal(size)` (Livewire event) changes the top panel width in place (e.g. `narrow` →
  `default` after a quick-create); the embedded component sits behind `wire:ignore` and survives.
- `size: narrow` is applied automatically when the target component opts into `quickCreate`
  through its noerd detail/page YAML — a soft, try/catch-guarded reference to
  `Noerd\Helpers\StaticConfigHelper`, so the package still works in a host without `noerd/noerd`.
  A component with `public $forceModalFullscreen = true` always opens fullscreen.
- The URL rewrite is only performed when it is TRUTHFUL: every non-empty argument must be a
  parameter of the route (or one of the chrome arguments `quickCreate`, `embedded`,
  `disableModal`, `source`, `context`); a non-empty `relations` (the prefill of a create form)
  blocks it; a missing `{modelId}` becomes the `new` sentinel. A shared `?modal=true` link is
  reopened from the session flash `noerd-modal.open` (`component`, `arguments`, `url`) in
  `mount()`; the flashed component is mounted once on trial and SKIPPED when it no longer resolves
  or is refused (403 from an access hook), so the page behind it still renders.
- The Alpine store `app` is SHARED: this package adds `modalOpen`, `modalFullscreen`,
  `modalLoading`; `noerd/noerd` adds `currentId`/`setId()` (its list keyboard navigation). Each
  side only adds missing keys — never `Alpine.store('app', {...})` over the other.

### Structure
- `src/Providers/NoerdModalServiceProvider.php` — `mergeConfigFrom()`, views under the `noerd`
  namespace (`<x-noerd::modal>`, `<x-noerd::modal.panel>`, `<x-noerd::noerd-modal-assets>`),
  JSON translations, Livewire namespace `noerd-modal::` plus `Livewire::addComponent('noerd-modal')`
  for the bare name of the stack (one component, never a whole location), the asset route, and —
  console only — the config publish tag and the two publish commands.
- `src/Http/Controllers/AssetController.php` + `src/Support/AssetManifest.php` — serve
  `dist/build` (Vite manifest, entry `resources/js/noerd-modal.js`) by route `noerd-modal.asset`
  (`GET /noerd-modal/{file}`, no middleware, immutable cache headers, 304 on `If-Modified-Since`);
  only files listed in the manifest are servable.
- `resources/views/components/modal/panel.blade.php` — the panel chrome (position `center`/`right`,
  sizes `default`/`narrow`, fullscreen toggle via the Alpine store `app`, stacked depth styling,
  `role="dialog"` + `aria-modal` + `aria-labelledby`, ids unique per modal key);
  `modal/index.blade.php` — the `open` wrapper. Publish the panel for customisation with
  `noerd-modal:publish-panel` (target `resources/views/vendor/noerd/components/modal/panel.blade.php`).
  CONTRACT of a published panel: the x-data of the panel body keeps `isModal`, `isRight`,
  `modalFullscreen`, `modalControlsClass` and `modalTitleId` — `noerd/noerd`'s page, modal title
  and list header read them — and the panel element keeps the `modal` attribute (the top panel is
  the last `[modal]` element; ESC and the core's keyboard shortcuts rely on it).
- `resources/views/examples/` — demo components, deliberately OUTSIDE `components/`: neither
  mountable by bare name nor as `noerd-modal::…`. `noerd-modal:publish-example` copies them to the
  host's `resources/views/components/example/` (Livewire names `example.noerd-example-*`).
- `config/noerd-modal.php` — single key `position` (`center` | `right`).
- `dist/build/` is COMMITTED (one bundle + manifest), `package-lock.json` too (`vite` and
  `laravel-vite-plugin` are devDependencies): `npm run build` inside the package regenerates it
  after every change to `resources/js/noerd-modal.js`; `npm run dev` writes the hot file to the host's
  `public/vendor/noerd-modal/hot` (the vite config assumes the package lives in `app-modules/noerd-modal`).

### No install/update command
This package has NO `noerd:install-modal` / `noerd:update-modal` and must never get one:
- `config/noerd-modal.php` is merged via `mergeConfigFrom()` and published only on request
  (`php artisan vendor:publish --tag=noerd-modal-config`).
- The bundle is served straight from the package by the `noerd-modal.asset` route — nothing is
  ever copied into `public/`, so the command would be a no-op, and `--force` (as forwarded by
  `noerd:update-all --force`) would silently reset the user's `position` setting.
- The provider never writes into the host on boot (`tests/Feature/ProviderBootTest.php`).
- It does not depend on `noerd/noerd`, so a command built on `HasModuleInstallation` /
  `RequiresNoerdInstallation` would fatal in a standalone Livewire host
  (`tests/Feature/NoerdModalCommandsTest.php` asserts that only the two publish commands exist).
- Consequently a host registers `noerd/modal` in the `packages` array of `boost.json` BY HAND —
  no install command adds it.

### Rules that are easy to get wrong
- Every stacked modal is rendered inside `@teleport('body')`, and the whole stack re-renders on
  every stack update (open, close, resize, mark top). A Livewire child living in a modal is
  therefore RE-MOUNTED on every stack update — `mount()` of any component that can be opened in a
  modal must be side-effect free (no writes, no dispatches with side effects, no counters).
- Never add middleware to the asset route: a static asset needs neither a session nor CSRF.
- Never hand-roll an overlay in a component; open everything through `$modal` / `$modalRoute` or
  the `noerdModal` event and close through `closeTopModal`.
- Keep the URL-rewrite contract: an argument that is not a route parameter (e.g. `accountId` on a
  contacts list) blocks the rewrite by design — pass `rewriteUrl: false` instead of forcing a URL.
- `resolveUrlParameters()` discovers `#[Url]` properties AND public `queryString*()` methods (trait
  aliases such as `detailPrimary`); `filter` and `currentTab` are blacklisted and never cleared.

### Commands
- `php artisan noerd-modal:publish-example {--force}` — copies the three example components to
  `resources/views/components/example/` and appends the `noerd-modal-example` route (pointing at the
  PUBLISHED `example.noerd-example-page`) to `routes/web.php`
- `php artisan noerd-modal:publish-panel {--force}` — publishes the panel view for customisation
- `php artisan vendor:publish --tag=noerd-modal-config` — the only config publish path

### Tests
- Pest tests in `tests/Feature/`, bound per file to the package's own `NoerdModal\Tests\TestCase`
  (Orchestra Testbench with Livewire and this provider ONLY — no `noerd/noerd`). Standalone:
  `composer install && vendor/bin/pest` in the package; from a host root:
  `php artisan test --compact app-modules/noerd-modal/tests` — no database is touched.
  `ModuleBoundaryTest` forbids every `Noerd\` reference except the two guarded quickCreate probes.
- The tests open `zz-modal-target`, `zz-modal-fullscreen-target` and `zz-modal-refused-target`
  (`tests/Fixtures`, registered by the TestCase) — never the examples.
- `tests/js/modal-source.mjs` (source resolution) and `tests/js/modal-behaviour.mjs` (ESC, loading
  overlay, history state, shared store) run under Node (skipped when no `node` binary is found).
- Modal route tests: mirror `NoerdModalTest.php`, `describe('Modal Route URL')` — register a
  throwaway component with `Livewire::component('zz-…')` (and a route with `Route::livewire()`),
  dispatch `noerdModal` on `Livewire::test('noerd-modal::noerd-modal')` and assert the `modals`
  array (`url`, `urlParameters`, `componentName`) — never assert a shipped YAML or route.

### Reference implementations
- `resources/views/components/noerd-modal.blade.php` — the stack: `bootModal()`, `routeUrlIsTruthful()`,
  `closeTopModal()`, `closeAllModals()`, `resizeTopModal()`
- `tests/Feature/NoerdModalTest.php` (`describe('Modal Route URL')`) — route-vs-component tests
- `tests/Feature/ProviderBootTest.php`, `tests/Feature/NoerdModalCommandsTest.php`,
  `tests/Feature/AssetRouteTest.php` — the "nothing is written into the host" guards
@endverbatim
