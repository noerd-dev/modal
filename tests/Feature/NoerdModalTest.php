<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Livewire\Livewire;
use Tests\TestCase;

uses(TestCase::class);

describe('Modal Manager', function (): void {
    it('opens a modal when noerdModal event is dispatched', function (): void {
        $component = Livewire::test('noerd-modal::noerd-modal')
            ->dispatch(
                'noerdModal',
                modalComponent: 'noerd-modal::example.noerd-example-component',
                arguments: ['name' => 'John Doe'],
            );

        $modals = $component->get('modals');
        expect($modals)->toHaveCount(1);

        $modal = array_values($modals)[0];
        expect($modal['componentName'])->toBe('noerd-modal::example.noerd-example-component');
        expect($modal['arguments'])->toBe(['name' => 'John Doe']);
        expect($modal['show'])->toBeTrue();
    });

    it('creates unique modal keys for each modal', function (): void {
        $component = Livewire::test('noerd-modal::noerd-modal')
            ->dispatch('noerdModal', modalComponent: 'noerd-modal::example.noerd-example-component', arguments: [])
            ->dispatch('noerdModal', modalComponent: 'noerd-modal::example.noerd-example-component', arguments: ['count' => 2]);

        $modals = $component->get('modals');
        $keys = array_keys($modals);

        expect($keys)->toHaveCount(2);
        expect($keys[0])->not->toBe($keys[1]);
    });

    it('tracks modal iteration for stacking', function (): void {
        $component = Livewire::test('noerd-modal::noerd-modal')
            ->dispatch('noerdModal', modalComponent: 'noerd-modal::example.noerd-example-component', arguments: [])
            ->dispatch('noerdModal', modalComponent: 'noerd-modal::example.noerd-example-component', arguments: ['count' => 2]);

        $modals = $component->get('modals');
        $iterations = array_column($modals, 'iteration');

        expect($iterations)->toContain(1);
        expect($iterations)->toContain(2);
    });

    it('marks only the top modal as topModal', function (): void {
        $component = Livewire::test('noerd-modal::noerd-modal')
            ->dispatch('noerdModal', modalComponent: 'noerd-modal::example.noerd-example-component', arguments: [])
            ->dispatch('noerdModal', modalComponent: 'noerd-modal::example.noerd-example-component', arguments: ['count' => 2])
            ->dispatch('noerdModal', modalComponent: 'noerd-modal::example.noerd-example-component', arguments: ['count' => 3]);

        $modals = $component->get('modals');
        $topModals = array_filter($modals, fn($modal) => $modal['topModal'] === true);

        expect($topModals)->toHaveCount(1);

        // The last modal should be the top modal
        $lastModal = end($modals);
        expect($lastModal['topModal'])->toBeTrue();
    });

    it('resizes the top modal in place without closing or reopening it', function (): void {
        $component = Livewire::test('noerd-modal::noerd-modal')
            ->dispatch('noerdModal', modalComponent: 'noerd-modal::example.noerd-example-component', arguments: ['id' => 1], size: 'narrow');

        $originalKey = array_keys($component->get('modals'))[0];
        expect($component->get('modals')[$originalKey]['size'])->toBe('narrow');

        $component->dispatch('resizeTopModal', size: 'default')
            ->assertNotDispatched('modal-closed-global');

        $modals = $component->get('modals');

        // Same modal, same key — only the size changed.
        expect($modals)->toHaveCount(1);
        expect(array_keys($modals)[0])->toBe($originalKey);
        expect($modals[$originalKey]['size'])->toBe('default');
        expect($modals[$originalKey]['topModal'])->toBeTrue();
    });

    it('only resizes the top modal of a stack', function (): void {
        $component = Livewire::test('noerd-modal::noerd-modal')
            ->dispatch('noerdModal', modalComponent: 'noerd-modal::example.noerd-example-component', arguments: ['id' => 1], size: 'narrow')
            ->dispatch('noerdModal', modalComponent: 'noerd-modal::example.noerd-example-component', arguments: ['id' => 2], size: 'narrow');

        $keys = array_keys($component->get('modals'));
        [$bottomKey, $topKey] = $keys;

        $component->dispatch('resizeTopModal', size: 'default');

        $modals = $component->get('modals');

        expect($modals[$topKey]['size'])->toBe('default');
        expect($modals[$bottomKey]['size'])->toBe('narrow');
    });

    it('does nothing when resizeTopModal is dispatched with no modals open', function (): void {
        Livewire::test('noerd-modal::noerd-modal')
            ->dispatch('resizeTopModal', size: 'default')
            ->assertNotDispatched('modal-closed-global');
    });

    it('closes a modal when closeModal event is dispatched', function (): void {
        $component = Livewire::test('noerd-modal::noerd-modal')
            ->dispatch('noerdModal', modalComponent: 'noerd-modal::example.noerd-example-component', arguments: []);

        $modals = $component->get('modals');
        $modalKey = array_keys($modals)[0];

        $component->call('closeModal', 'noerd-modal::example.noerd-example-component', null, $modalKey);

        expect($component->get('modals'))->toBeEmpty();
    });

    it('dispatches modal-closed-global when all modals are closed', function (): void {
        $component = Livewire::test('noerd-modal::noerd-modal')
            ->dispatch('noerdModal', modalComponent: 'noerd-modal::example.noerd-example-component', arguments: []);

        $modals = $component->get('modals');
        $modalKey = array_keys($modals)[0];

        $component->call('closeModal', 'noerd-modal::example.noerd-example-component', null, $modalKey)
            ->assertDispatched('modal-closed-global');
    });

    it('does not dispatch modal-closed-global when modals remain open', function (): void {
        $component = Livewire::test('noerd-modal::noerd-modal')
            ->dispatch('noerdModal', modalComponent: 'noerd-modal::example.noerd-example-component', arguments: [])
            ->dispatch('noerdModal', modalComponent: 'noerd-modal::example.noerd-example-component', arguments: ['count' => 2]);

        $modals = $component->get('modals');
        $firstModalKey = array_keys($modals)[0];

        $component->call('closeModal', 'noerd-modal::example.noerd-example-component', null, $firstModalKey)
            ->assertNotDispatched('modal-closed-global');

        // One modal should remain
        expect($component->get('modals'))->toHaveCount(1);
    });

    it('closes only the top modal when closeTopModal is dispatched', function (): void {
        $component = Livewire::test('noerd-modal::noerd-modal')
            ->dispatch('noerdModal', modalComponent: 'noerd-modal::example.noerd-example-component', arguments: ['id' => 1])
            ->dispatch('noerdModal', modalComponent: 'noerd-modal::example.noerd-example-component', arguments: ['id' => 2])
            ->dispatch('noerdModal', modalComponent: 'noerd-modal::example.noerd-example-component', arguments: ['id' => 3]);

        expect($component->get('modals'))->toHaveCount(3);

        // Dispatch closeTopModal (simulates ESC key press)
        $component->dispatch('closeTopModal');

        // Should directly close the top modal
        expect($component->get('modals'))->toHaveCount(2);
    });

    it('closes nested modals one by one with multiple closeTopModal dispatches', function (): void {
        $component = Livewire::test('noerd-modal::noerd-modal')
            ->dispatch('noerdModal', modalComponent: 'noerd-modal::example.noerd-example-component', arguments: ['id' => 1])
            ->dispatch('noerdModal', modalComponent: 'noerd-modal::example.noerd-example-component', arguments: ['id' => 2]);

        expect($component->get('modals'))->toHaveCount(2);

        // First closeTopModal should close the second modal (top)
        $component->dispatch('closeTopModal');

        expect($component->get('modals'))->toHaveCount(1);

        // The remaining modal should now be the top modal
        $remainingModals = $component->get('modals');
        $remainingModal = array_values($remainingModals)[0];
        expect($remainingModal['topModal'])->toBeTrue();

        // Second closeTopModal should close the first modal
        $component->dispatch('closeTopModal');

        expect($component->get('modals'))->toBeEmpty();
    });

    it('dispatches refreshList when closeTopModal closes a modal with source', function (): void {
        $component = Livewire::test('noerd-modal::noerd-modal')
            ->dispatch(
                'noerdModal',
                modalComponent: 'noerd-modal::example.noerd-example-component',
                source: 'customers-list',
                arguments: [],
            );

        expect($component->get('modals'))->toHaveCount(1);

        $component->dispatch('closeTopModal')
            ->assertDispatched('refreshList-customers-list');

        expect($component->get('modals'))->toBeEmpty();
    });

    it('does nothing when closeTopModal is dispatched with no modals open', function (): void {
        $component = Livewire::test('noerd-modal::noerd-modal')
            ->dispatch('closeTopModal');

        // Should not dispatch any refresh event since there's nothing to close
        $component->assertNotDispatched('refreshList-*');
        $component->assertNotDispatched('modal-closed-global');
        expect($component->get('modals'))->toBeEmpty();
    });

    it('stores source parameter in modal', function (): void {
        $component = Livewire::test('noerd-modal::noerd-modal')
            ->dispatch(
                'noerdModal',
                modalComponent: 'noerd-modal::example.noerd-example-component',
                source: 'test-source',
                arguments: [],
            );

        $modals = $component->get('modals');
        $modal = array_values($modals)[0];

        expect($modal['source'])->toBe('test-source');
    });

    it('closes every open modal when closeAllModals is dispatched', function (): void {
        $component = Livewire::test('noerd-modal::noerd-modal')
            ->dispatch('noerdModal', modalComponent: 'noerd-modal::example.noerd-example-component', arguments: ['id' => 1])
            ->dispatch('noerdModal', modalComponent: 'noerd-modal::example.noerd-example-component', arguments: ['id' => 2])
            ->dispatch('noerdModal', modalComponent: 'noerd-modal::example.noerd-example-component', arguments: ['id' => 3]);

        expect($component->get('modals'))->toHaveCount(3);

        $component->dispatch('closeAllModals');

        expect($component->get('modals'))->toBeEmpty();
    });

    it('dispatches modal-closed-global once when closeAllModals clears the stack', function (): void {
        $component = Livewire::test('noerd-modal::noerd-modal')
            ->dispatch('noerdModal', modalComponent: 'noerd-modal::example.noerd-example-component', arguments: ['id' => 1])
            ->dispatch('noerdModal', modalComponent: 'noerd-modal::example.noerd-example-component', arguments: ['id' => 2]);

        $component->dispatch('closeAllModals')
            ->assertDispatched('modal-closed-global');
    });

    it('dispatches refreshList for each sourced modal on closeAllModals', function (): void {
        $component = Livewire::test('noerd-modal::noerd-modal')
            ->dispatch(
                'noerdModal',
                modalComponent: 'noerd-modal::example.noerd-example-component',
                source: 'customers-list',
                arguments: ['id' => 1],
            )
            ->dispatch(
                'noerdModal',
                modalComponent: 'noerd-modal::example.noerd-example-component',
                source: 'products-list',
                arguments: ['id' => 2],
            );

        $component->dispatch('closeAllModals')
            ->assertDispatched('refreshList-customers-list')
            ->assertDispatched('refreshList-products-list');

        expect($component->get('modals'))->toBeEmpty();
    });

    it('does nothing when closeAllModals is dispatched with no modals open', function (): void {
        $component = Livewire::test('noerd-modal::noerd-modal')
            ->dispatch('closeAllModals');

        $component->assertNotDispatched('modal-closed-global');
        $component->assertNotDispatched('refreshList-*');
        expect($component->get('modals'))->toBeEmpty();
    });

    it('toggles fullscreen session state', function (): void {
        $component = Livewire::test('noerd-modal::noerd-modal');

        expect(session('modal_fullscreen'))->toBeNull();

        $component->call('toggleFullscreen');
        expect(session('modal_fullscreen'))->toBeTrue();

        $component->call('toggleFullscreen');
        expect(session('modal_fullscreen'))->toBeNull();
    });

    it('applies the fullscreen state client-side instead of re-rendering the panel', function (): void {
        $html = renderModalPanel();

        expect($html)->toContain('$store.app.modalFullscreen = false');
        expect($html)->toContain("\$store.app.modalFullscreen ? 'sm:max-w-full");
        expect($html)->not->toContain('wire:click.prevent="toggleFullscreen"');
    });

    it('renders identical panel geometry regardless of the fullscreen session state', function (): void {
        $default = panelGeometryBindings(renderModalPanel());

        session(['modal_fullscreen' => true]);
        $fullscreen = renderModalPanel();

        // Only the store seed differs — the geometry itself is no longer baked
        // into the server-rendered markup, so nothing can jump on toggle.
        expect($fullscreen)->toContain('$store.app.modalFullscreen = true');
        expect(panelGeometryBindings($fullscreen))->toBe($default)->not->toBeEmpty();
    });

    it('centers the panel permanently and covers the full viewport in fullscreen', function (): void {
        $html = renderModalPanel();

        expect($html)->toContain('sm:top-1/2 sm:-translate-y-1/2');
        expect($html)->toContain('sm:min-h-[100dvh] sm:max-h-[100dvh]');
    });

    it('never reads the shared preference for a forced fullscreen modal', function (): void {
        $html = renderModalPanel('noerd-modal::example.noerd-example-fullscreen-component');

        expect($html)->toContain("true ? 'sm:max-w-full");
        expect($html)->not->toContain("\$store.app.modalFullscreen ? 'sm:max-w-full");
    });
});

describe('Modal Route URL', function (): void {
    it('collects trait-style queryString aliases into urlParameters', function (): void {
        // Mirrors NoerdPage::queryStringNoerdPage(): resolveUrlParameters() must
        // discover public queryString{Trait}() methods so the alias is cleared
        // from the URL when the modal closes.
        Livewire::component('zz-modal-query-string-component', new class extends \Livewire\Component {
            public $modelId = null;

            public function queryStringZzTest(): array
            {
                return [
                    'modelId' => ['as' => 'zzId', 'keep' => false, 'except' => ''],
                ];
            }

            public function render(): string
            {
                return '<div>zz-query-string</div>';
            }
        });

        $component = Livewire::test('noerd-modal::noerd-modal')
            ->dispatch(
                'noerdModal',
                modalComponent: 'zz-modal-query-string-component',
                arguments: ['modelId' => 5],
                url: 'https://example.test/zz/5',
            );

        $modal = array_values($component->get('modals'))[0];

        expect($modal['urlParameters'])->toContain('zzId');

        $component->assertDispatched(
            'set-modal-url',
            fn(string $event, array $params): bool => in_array('zzId', $params['clearParams'], true),
        );
    });

    it('stores the url on the modal and dispatches set-modal-url', function (): void {
        $component = Livewire::test('noerd-modal::noerd-modal')
            ->dispatch(
                'noerdModal',
                modalComponent: 'noerd-modal::example.noerd-example-component',
                arguments: ['modelId' => 5],
                url: 'https://example.test/crm/account/5',
            );

        $modal = array_values($component->get('modals'))[0];

        expect($modal['url'])->toBe('https://example.test/crm/account/5');

        $component->assertDispatched(
            'set-modal-url',
            fn(string $event, array $params): bool => $params['url'] === 'https://example.test/crm/account/5'
                && $params['clearParams'] === $modal['urlParameters'],
        );
    });

    it('does not dispatch set-modal-url for modals without a url', function (): void {
        Livewire::test('noerd-modal::noerd-modal')
            ->dispatch('noerdModal', modalComponent: 'noerd-modal::example.noerd-example-component', arguments: [])
            ->assertNotDispatched('set-modal-url');
    });

    it('dispatches restore-modal-url when closeTopModal closes a url modal', function (): void {
        Livewire::test('noerd-modal::noerd-modal')
            ->dispatch(
                'noerdModal',
                modalComponent: 'noerd-modal::example.noerd-example-component',
                arguments: ['modelId' => 5],
                url: 'https://example.test/crm/account/5',
            )
            ->dispatch('closeTopModal')
            ->assertDispatched('restore-modal-url');
    });

    it('does not dispatch restore-modal-url when closing a modal without a url', function (): void {
        Livewire::test('noerd-modal::noerd-modal')
            ->dispatch('noerdModal', modalComponent: 'noerd-modal::example.noerd-example-component', arguments: [])
            ->dispatch('closeTopModal')
            ->assertNotDispatched('restore-modal-url');
    });

    it('only restores the url once the url modal itself is closed in a stack', function (): void {
        $component = Livewire::test('noerd-modal::noerd-modal')
            ->dispatch(
                'noerdModal',
                modalComponent: 'noerd-modal::example.noerd-example-component',
                arguments: ['modelId' => 5],
                url: 'https://example.test/crm/account/5',
            )
            ->dispatch('noerdModal', modalComponent: 'noerd-modal::example.noerd-example-component', arguments: ['sub' => true]);

        $component->dispatch('closeTopModal')
            ->assertNotDispatched('restore-modal-url');

        $component->dispatch('closeTopModal')
            ->assertDispatched('restore-modal-url');
    });

    it('resolves a livewire route name to its component and route url', function (): void {
        Route::livewire('test-example/{modelId}', 'noerd-modal::example.noerd-example-component')
            ->name('test.example.detail');

        $component = Livewire::test('noerd-modal::noerd-modal')
            ->dispatch('noerdModal', route: 'test.example.detail', arguments: ['modelId' => 5]);

        $modal = array_values($component->get('modals'))[0];

        expect($modal['componentName'])->toBe('noerd-modal::example.noerd-example-component');
        expect($modal['url'])->toBe(route('test.example.detail', ['modelId' => 5, 'modal' => 'true']));

        $component->assertDispatched('set-modal-url');
    });

    it('resolves a parameterless livewire route with a plain modal url', function (): void {
        Route::livewire('test-examples', 'noerd-modal::example.noerd-example-component')
            ->name('test.examples');

        $component = Livewire::test('noerd-modal::noerd-modal')
            ->dispatch('noerdModal', route: 'test.examples', arguments: []);

        $modal = array_values($component->get('modals'))[0];

        expect($modal['componentName'])->toBe('noerd-modal::example.noerd-example-component');
        expect($modal['url'])->toBe(route('test.examples', ['modal' => 'true']));
    });

    it('uses the new sentinel in the url when the modelId param is missing', function (): void {
        Route::livewire('test-example/{modelId}', 'noerd-modal::example.noerd-example-component')
            ->name('test.example.detail');

        $component = Livewire::test('noerd-modal::noerd-modal')
            ->dispatch('noerdModal', route: 'test.example.detail', arguments: ['modelId' => null]);

        $modal = array_values($component->get('modals'))[0];

        expect($modal['componentName'])->toBe('noerd-modal::example.noerd-example-component');
        expect($modal['url'])->toBe(route('test.example.detail', ['modelId' => 'new', 'modal' => 'true']));

        $component->assertDispatched('set-modal-url');
    });

    it('resolves the route component without a url when a required non-modelId param is missing', function (): void {
        Route::livewire('test-example-other/{otherId}', 'noerd-modal::example.noerd-example-component')
            ->name('test.example.other');

        $component = Livewire::test('noerd-modal::noerd-modal')
            ->dispatch('noerdModal', route: 'test.example.other', arguments: []);

        $modal = array_values($component->get('modals'))[0];

        expect($modal['componentName'])->toBe('noerd-modal::example.noerd-example-component');
        expect($modal['url'])->toBeNull();

        $component->assertNotDispatched('set-modal-url');
    });

    it('opens nothing for an unknown route name', function (): void {
        expect(
            Livewire::test('noerd-modal::noerd-modal')
                ->dispatch('noerdModal', route: 'route.that.does.not.exist', arguments: [])
                ->get('modals'),
        )->toBeEmpty();
    });

    it('falls back to the modalComponent when the route name is not registered', function (): void {
        $component = Livewire::test('noerd-modal::noerd-modal')
            ->dispatch(
                'noerdModal',
                modalComponent: 'noerd-modal::example.noerd-example-component',
                arguments: ['modelId' => 5],
                route: 'route.that.does.not.exist',
            );

        $modal = array_values($component->get('modals'))[0];

        expect($modal['componentName'])->toBe('noerd-modal::example.noerd-example-component');
        expect($modal['url'])->toBeNull();

        $component->assertNotDispatched('set-modal-url');
    });

    it('prefers the route component over the fallback modalComponent', function (): void {
        Route::livewire('test-route-wins/{modelId}', 'noerd-modal::example.noerd-example-fullscreen-component')
            ->name('test.route.wins');

        $component = Livewire::test('noerd-modal::noerd-modal')
            ->dispatch(
                'noerdModal',
                modalComponent: 'noerd-modal::example.noerd-example-component',
                arguments: ['modelId' => 5],
                route: 'test.route.wins',
            );

        $modal = array_values($component->get('modals'))[0];

        expect($modal['componentName'])->toBe('noerd-modal::example.noerd-example-fullscreen-component');
        expect($modal['url'])->toBe(route('test.route.wins', ['modelId' => 5, 'modal' => 'true']));
    });

    it('skips the url rewrite when rewriteUrl is false', function (): void {
        Route::livewire('test-no-rewrite/{modelId}', 'noerd-modal::example.noerd-example-component')
            ->name('test.no.rewrite');

        $component = Livewire::test('noerd-modal::noerd-modal')
            ->dispatch('noerdModal', arguments: ['modelId' => 5], route: 'test.no.rewrite', rewriteUrl: false);

        $modal = array_values($component->get('modals'))[0];

        expect($modal['componentName'])->toBe('noerd-modal::example.noerd-example-component');
        expect($modal['url'])->toBeNull();

        $component->assertNotDispatched('set-modal-url');
    });

    it('skips the url rewrite when an argument is not expressible in the route', function (): void {
        Route::livewire('test-filtered-list', 'noerd-modal::example.noerd-example-component')
            ->name('test.filtered.list');

        $component = Livewire::test('noerd-modal::noerd-modal')
            ->dispatch('noerdModal', arguments: ['accountId' => 5], route: 'test.filtered.list');

        $modal = array_values($component->get('modals'))[0];

        expect($modal['componentName'])->toBe('noerd-modal::example.noerd-example-component');
        expect($modal['arguments'])->toBe(['accountId' => 5]);
        expect($modal['url'])->toBeNull();

        $component->assertNotDispatched('set-modal-url');
    });

    it('still rewrites the url for chrome-only arguments', function (): void {
        Route::livewire('test-chrome-args/{modelId}', 'noerd-modal::example.noerd-example-component')
            ->name('test.chrome.args');

        $component = Livewire::test('noerd-modal::noerd-modal')
            ->dispatch('noerdModal', arguments: [
                'modelId' => 5,
                'relations' => ['accountId' => 7],
                'quickCreate' => true,
            ], route: 'test.chrome.args');

        $modal = array_values($component->get('modals'))[0];

        expect($modal['url'])->toBe(route('test.chrome.args', ['modelId' => 5, 'modal' => 'true']));

        $component->assertDispatched('set-modal-url');
    });

    it('treats a dotted component name as a component, never as a route', function (): void {
        $component = Livewire::test('noerd-modal::noerd-modal')
            ->dispatch('noerdModal', modalComponent: 'noerd-modal::example.noerd-example-component', arguments: []);

        $modal = array_values($component->get('modals'))[0];

        expect($modal['componentName'])->toBe('noerd-modal::example.noerd-example-component');
        expect($modal['url'])->toBeNull();
    });

    it('opens a flashed modal on mount with its url', function (): void {
        session()->flash('noerd-modal.open', [
            'component' => 'noerd-modal::example.noerd-example-component',
            'arguments' => ['modelId' => 7],
            'url' => 'https://example.test/crm/account/7?modal=true',
        ]);

        $component = Livewire::test('noerd-modal::noerd-modal');

        $modal = array_values($component->get('modals'))[0];
        expect($modal['componentName'])->toBe('noerd-modal::example.noerd-example-component');
        expect($modal['arguments'])->toBe(['modelId' => 7]);
        expect($modal['url'])->toBe('https://example.test/crm/account/7?modal=true');

        $component->assertDispatched('set-modal-url');
    });

    it('mounts without a modal when nothing was flashed', function (): void {
        expect(Livewire::test('noerd-modal::noerd-modal')->get('modals'))->toBeEmpty();
    });

    it('dispatches restore-modal-url for every url modal on closeAllModals', function (): void {
        Livewire::test('noerd-modal::noerd-modal')
            ->dispatch(
                'noerdModal',
                modalComponent: 'noerd-modal::example.noerd-example-component',
                arguments: ['modelId' => 1],
                url: 'https://example.test/crm/account/1',
            )
            ->dispatch(
                'noerdModal',
                modalComponent: 'noerd-modal::example.noerd-example-component',
                arguments: ['modelId' => 2],
                url: 'https://example.test/crm/account/2',
            )
            ->dispatch('closeAllModals')
            ->assertDispatched('restore-modal-url');
    });
});

function renderModalPanel(string $component = 'noerd-modal::example.noerd-example-component'): string
{
    return html_entity_decode(
        Livewire::test('noerd-modal::noerd-modal')
            ->dispatch('noerdModal', modalComponent: $component, arguments: [])
            ->html(),
    );
}

/**
 * @return list<string>
 */
function panelGeometryBindings(string $html): array
{
    preg_match_all('/:class="([^"]*max-w[^"]*)"/', $html, $matches);

    return $matches[1];
}

describe('Example Component', function (): void {
    it('initializes count to 1', function (): void {
        Livewire::test('noerd-modal::example.noerd-example-component')
            ->assertSet('count', 1);
    });

    it('increments count when upCount is called', function (): void {
        Livewire::test('noerd-modal::example.noerd-example-component')
            ->assertSet('count', 1)
            ->call('upCount')
            ->assertSet('count', 2);
    });

    it('increments count multiple times', function (): void {
        Livewire::test('noerd-modal::example.noerd-example-component')
            ->assertSet('count', 1)
            ->call('upCount')
            ->assertSet('count', 2)
            ->call('upCount')
            ->assertSet('count', 3)
            ->call('upCount')
            ->assertSet('count', 4);
    });

    it('can set count via wire:model', function (): void {
        Livewire::test('noerd-modal::example.noerd-example-component')
            ->set('count', 5)
            ->assertSet('count', 5);
    });

    it('displays the current count', function (): void {
        Livewire::test('noerd-modal::example.noerd-example-component')
            ->assertSee('Counter: 1')
            ->call('upCount')
            ->assertSee('Counter: 2');
    });
});
