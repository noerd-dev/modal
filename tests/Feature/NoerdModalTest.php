<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Livewire\Exceptions\MethodNotFoundException;
use Livewire\Features\SupportLockedProperties\CannotUpdateLockedPropertyException;
use Livewire\Livewire;
use NoerdModal\Tests\TestCase;

uses(TestCase::class);

describe('Modal Manager', function (): void {
    it('opens a modal when noerdModal event is dispatched', function (): void {
        $component = Livewire::test('noerd-modal::noerd-modal')
            ->dispatch(
                'noerdModal',
                modalComponent: 'zz-modal-target',
                arguments: ['name' => 'John Doe'],
            );

        $modals = $component->get('modals');
        expect($modals)->toHaveCount(1);

        $modal = array_values($modals)[0];
        expect($modal['componentName'])->toBe('zz-modal-target');
        expect($modal['arguments'])->toBe(['name' => 'John Doe']);
        expect($modal['show'])->toBeTrue();
    });

    it('creates unique modal keys for each modal', function (): void {
        $component = Livewire::test('noerd-modal::noerd-modal')
            ->dispatch('noerdModal', modalComponent: 'zz-modal-target', arguments: [])
            ->dispatch('noerdModal', modalComponent: 'zz-modal-target', arguments: ['count' => 2]);

        $modals = $component->get('modals');
        $keys = array_keys($modals);

        expect($keys)->toHaveCount(2);
        expect($keys[0])->not->toBe($keys[1]);

        // The iteration counter is the stacking order behind those keys.
        $iterations = array_column($modals, 'iteration');

        expect($iterations)->toContain(1);
        expect($iterations)->toContain(2);
    });

    it('marks only the top modal as topModal', function (): void {
        $component = Livewire::test('noerd-modal::noerd-modal')
            ->dispatch('noerdModal', modalComponent: 'zz-modal-target', arguments: [])
            ->dispatch('noerdModal', modalComponent: 'zz-modal-target', arguments: ['count' => 2])
            ->dispatch('noerdModal', modalComponent: 'zz-modal-target', arguments: ['count' => 3]);

        $modals = $component->get('modals');
        $topModals = array_filter($modals, fn($modal) => $modal['topModal'] === true);

        expect($topModals)->toHaveCount(1);

        // The last modal should be the top modal
        $lastModal = end($modals);
        expect($lastModal['topModal'])->toBeTrue();
    });

    it('resizes the top modal in place without closing or reopening it', function (): void {
        $component = Livewire::test('noerd-modal::noerd-modal')
            ->dispatch('noerdModal', modalComponent: 'zz-modal-target', arguments: ['id' => 1], size: 'narrow');

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
            ->dispatch('noerdModal', modalComponent: 'zz-modal-target', arguments: ['id' => 1], size: 'narrow')
            ->dispatch('noerdModal', modalComponent: 'zz-modal-target', arguments: ['id' => 2], size: 'narrow');

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

    it('dispatches modal-closed-global when closeTopModal closes the last modal', function (): void {
        $component = Livewire::test('noerd-modal::noerd-modal')
            ->dispatch('noerdModal', modalComponent: 'zz-modal-target', arguments: []);

        $component->dispatch('closeTopModal')
            ->assertDispatched('modal-closed-global');

        expect($component->get('modals'))->toBeEmpty();
    });

    it('does not dispatch modal-closed-global when modals remain open', function (): void {
        $component = Livewire::test('noerd-modal::noerd-modal')
            ->dispatch('noerdModal', modalComponent: 'zz-modal-target', arguments: [])
            ->dispatch('noerdModal', modalComponent: 'zz-modal-target', arguments: ['count' => 2]);

        $bottomKey = array_keys($component->get('modals'))[0];

        $component->dispatch('closeTopModal')
            ->assertNotDispatched('modal-closed-global');

        // The bottom modal remains and is the top of the stack now.
        expect(array_keys($component->get('modals')))->toBe([$bottomKey]);
    });

    it('offers the browser no way to remove a single entry of the stack', function (): void {
        $component = Livewire::test('noerd-modal::noerd-modal')
            ->dispatch('noerdModal', modalComponent: 'zz-modal-target', arguments: []);

        $modalKey = array_keys($component->get('modals'))[0];

        $component->call('closeModal', 'zz-modal-target', null, $modalKey);
    })->throws(MethodNotFoundException::class);

    it('ends the loading overlay when nothing opens', function (): void {
        Livewire::test('noerd-modal::noerd-modal')
            ->dispatch('noerdModal', route: 'route.that.does.not.exist', arguments: [])
            ->assertDispatched('noerd-modal-loading-end');

        Livewire::test('noerd-modal::noerd-modal')
            ->dispatch('noerdModal', arguments: [])
            ->assertDispatched('noerd-modal-loading-end');
    });

    it('does not end the loading overlay early when a modal opens', function (): void {
        Livewire::test('noerd-modal::noerd-modal')
            ->dispatch('noerdModal', modalComponent: 'zz-modal-target', arguments: [])
            ->assertNotDispatched('noerd-modal-loading-end');
    });

    it('closes nested modals one by one with multiple closeTopModal dispatches', function (): void {
        $component = Livewire::test('noerd-modal::noerd-modal')
            ->dispatch('noerdModal', modalComponent: 'zz-modal-target', arguments: ['id' => 1])
            ->dispatch('noerdModal', modalComponent: 'zz-modal-target', arguments: ['id' => 2]);

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
                modalComponent: 'zz-modal-target',
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

    it('closes every open modal when closeAllModals is dispatched', function (): void {
        $component = Livewire::test('noerd-modal::noerd-modal')
            ->dispatch('noerdModal', modalComponent: 'zz-modal-target', arguments: ['id' => 1])
            ->dispatch('noerdModal', modalComponent: 'zz-modal-target', arguments: ['id' => 2])
            ->dispatch('noerdModal', modalComponent: 'zz-modal-target', arguments: ['id' => 3]);

        expect($component->get('modals'))->toHaveCount(3);

        $component->dispatch('closeAllModals');

        expect($component->get('modals'))->toBeEmpty();
    });

    it('dispatches modal-closed-global once when closeAllModals clears the stack', function (): void {
        $component = Livewire::test('noerd-modal::noerd-modal')
            ->dispatch('noerdModal', modalComponent: 'zz-modal-target', arguments: ['id' => 1])
            ->dispatch('noerdModal', modalComponent: 'zz-modal-target', arguments: ['id' => 2]);

        $component->dispatch('closeAllModals')
            ->assertDispatched('modal-closed-global');
    });

    it('dispatches refreshList for each sourced modal on closeAllModals', function (): void {
        $component = Livewire::test('noerd-modal::noerd-modal')
            ->dispatch(
                'noerdModal',
                modalComponent: 'zz-modal-target',
                source: 'customers-list',
                arguments: ['id' => 1],
            )
            ->dispatch(
                'noerdModal',
                modalComponent: 'zz-modal-target',
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

        // Only the Alpine wiring is pinned — which classes each branch carries
        // is styling and may change with the publishable panel.
        expect($html)->toContain('$store.app.modalFullscreen = false');
        expect($html)->toContain(':class="$store.app.modalFullscreen ?');
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

    it('never reads the shared preference for a forced fullscreen modal', function (): void {
        $html = renderModalPanel('zz-modal-fullscreen-target');

        expect($html)->toContain(':class="true ?');
        expect($html)->not->toContain('$store.app.modalFullscreen ?');
    });

    it('rejects a client update of the modal stack', function (array|int $value): void {
        Livewire::test('noerd-modal::noerd-modal')
            ->set('modals', $value);
    })->with([
        'a scalar entry' => [[1]],
        'a forged component' => [[['componentName' => 'zz-modal-target']]],
    ])->throws(CannotUpdateLockedPropertyException::class);
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

        Route::livewire('zz-query-string/{modelId}', 'zz-modal-query-string-component')
            ->name('zz.query.string');

        $component = Livewire::test('noerd-modal::noerd-modal')
            ->dispatch('noerdModal', route: 'zz.query.string', arguments: ['modelId' => 5]);

        $modal = array_values($component->get('modals'))[0];

        expect($modal['urlParameters'])->toContain('zzId');

        $component->assertDispatched(
            'set-modal-url',
            fn(string $event, array $params): bool => in_array('zzId', $params['clearParams'], true),
        );
    });

    it('stores the route url on the modal and dispatches set-modal-url', function (): void {
        Route::livewire('zz-account/{modelId}', 'zz-modal-target')->name('zz.account.detail');

        $component = Livewire::test('noerd-modal::noerd-modal')
            ->dispatch('noerdModal', route: 'zz.account.detail', arguments: ['modelId' => 5]);

        $modal = array_values($component->get('modals'))[0];
        $url = route('zz.account.detail', ['modelId' => 5, 'modal' => 'true']);

        expect($modal['url'])->toBe($url);

        $component->assertDispatched(
            'set-modal-url',
            fn(string $event, array $params): bool => $params['url'] === $url
                && $params['clearParams'] === $modal['urlParameters'],
        );
    });

    it('never takes the browser url from the event', function (): void {
        $component = Livewire::test('noerd-modal::noerd-modal')
            ->dispatch(
                'noerdModal',
                modalComponent: 'zz-modal-target',
                arguments: ['modelId' => 5],
                url: 'https://example.test/anything',
            );

        $modal = array_values($component->get('modals'))[0];

        expect($modal['url'])->toBeNull();

        $component->assertNotDispatched('set-modal-url');
    });

    it('does not dispatch set-modal-url for modals without a url', function (): void {
        Livewire::test('noerd-modal::noerd-modal')
            ->dispatch('noerdModal', modalComponent: 'zz-modal-target', arguments: [])
            ->assertNotDispatched('set-modal-url');
    });

    it('dispatches restore-modal-url when closeTopModal closes a url modal', function (): void {
        Route::livewire('zz-account/{modelId}', 'zz-modal-target')->name('zz.account.detail');

        Livewire::test('noerd-modal::noerd-modal')
            ->dispatch('noerdModal', route: 'zz.account.detail', arguments: ['modelId' => 5])
            ->dispatch('closeTopModal')
            ->assertDispatched('restore-modal-url');
    });

    it('does not dispatch restore-modal-url when closing a modal without a url', function (): void {
        Livewire::test('noerd-modal::noerd-modal')
            ->dispatch('noerdModal', modalComponent: 'zz-modal-target', arguments: [])
            ->dispatch('closeTopModal')
            ->assertNotDispatched('restore-modal-url');
    });

    it('only restores the url once the url modal itself is closed in a stack', function (): void {
        Route::livewire('zz-account/{modelId}', 'zz-modal-target')->name('zz.account.detail');

        $component = Livewire::test('noerd-modal::noerd-modal')
            ->dispatch('noerdModal', route: 'zz.account.detail', arguments: ['modelId' => 5])
            ->dispatch('noerdModal', modalComponent: 'zz-modal-target', arguments: ['sub' => true]);

        $component->dispatch('closeTopModal')
            ->assertNotDispatched('restore-modal-url');

        $component->dispatch('closeTopModal')
            ->assertDispatched('restore-modal-url');
    });

    it('resolves a livewire route name to its component and route url', function (): void {
        Route::livewire('test-example/{modelId}', 'zz-modal-target')
            ->name('test.example.detail');

        $component = Livewire::test('noerd-modal::noerd-modal')
            ->dispatch('noerdModal', route: 'test.example.detail', arguments: ['modelId' => 5]);

        $modal = array_values($component->get('modals'))[0];

        expect($modal['componentName'])->toBe('zz-modal-target');
        expect($modal['url'])->toBe(route('test.example.detail', ['modelId' => 5, 'modal' => 'true']));

        $component->assertDispatched('set-modal-url');
    });

    it('resolves a parameterless livewire route with a plain modal url', function (): void {
        Route::livewire('test-examples', 'zz-modal-target')
            ->name('test.examples');

        $component = Livewire::test('noerd-modal::noerd-modal')
            ->dispatch('noerdModal', route: 'test.examples', arguments: []);

        $modal = array_values($component->get('modals'))[0];

        expect($modal['componentName'])->toBe('zz-modal-target');
        expect($modal['url'])->toBe(route('test.examples', ['modal' => 'true']));
    });

    it('uses the new sentinel in the url when the modelId param is missing', function (): void {
        Route::livewire('test-example/{modelId}', 'zz-modal-target')
            ->name('test.example.detail');

        $component = Livewire::test('noerd-modal::noerd-modal')
            ->dispatch('noerdModal', route: 'test.example.detail', arguments: ['modelId' => null]);

        $modal = array_values($component->get('modals'))[0];

        expect($modal['componentName'])->toBe('zz-modal-target');
        expect($modal['url'])->toBe(route('test.example.detail', ['modelId' => 'new', 'modal' => 'true']));

        $component->assertDispatched('set-modal-url');
    });

    it('resolves the route component without a url when a required non-modelId param is missing', function (): void {
        Route::livewire('test-example-other/{otherId}', 'zz-modal-target')
            ->name('test.example.other');

        $component = Livewire::test('noerd-modal::noerd-modal')
            ->dispatch('noerdModal', route: 'test.example.other', arguments: []);

        $modal = array_values($component->get('modals'))[0];

        expect($modal['componentName'])->toBe('zz-modal-target');
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
                modalComponent: 'zz-modal-target',
                arguments: ['modelId' => 5],
                route: 'route.that.does.not.exist',
            );

        $modal = array_values($component->get('modals'))[0];

        expect($modal['componentName'])->toBe('zz-modal-target');
        expect($modal['url'])->toBeNull();

        $component->assertNotDispatched('set-modal-url');
    });

    it('prefers the route component over the fallback modalComponent', function (): void {
        Route::livewire('test-route-wins/{modelId}', 'zz-modal-fullscreen-target')
            ->name('test.route.wins');

        $component = Livewire::test('noerd-modal::noerd-modal')
            ->dispatch(
                'noerdModal',
                modalComponent: 'zz-modal-target',
                arguments: ['modelId' => 5],
                route: 'test.route.wins',
            );

        $modal = array_values($component->get('modals'))[0];

        expect($modal['componentName'])->toBe('zz-modal-fullscreen-target');
        expect($modal['url'])->toBe(route('test.route.wins', ['modelId' => 5, 'modal' => 'true']));
    });

    it('skips the url rewrite when rewriteUrl is false', function (): void {
        Route::livewire('test-no-rewrite/{modelId}', 'zz-modal-target')
            ->name('test.no.rewrite');

        $component = Livewire::test('noerd-modal::noerd-modal')
            ->dispatch('noerdModal', arguments: ['modelId' => 5], route: 'test.no.rewrite', rewriteUrl: false);

        $modal = array_values($component->get('modals'))[0];

        expect($modal['componentName'])->toBe('zz-modal-target');
        expect($modal['url'])->toBeNull();

        $component->assertNotDispatched('set-modal-url');
    });

    it('skips the url rewrite when an argument is not expressible in the route', function (): void {
        Route::livewire('test-filtered-list', 'zz-modal-target')
            ->name('test.filtered.list');

        $component = Livewire::test('noerd-modal::noerd-modal')
            ->dispatch('noerdModal', arguments: ['accountId' => 5], route: 'test.filtered.list');

        $modal = array_values($component->get('modals'))[0];

        expect($modal['componentName'])->toBe('zz-modal-target');
        expect($modal['arguments'])->toBe(['accountId' => 5]);
        expect($modal['url'])->toBeNull();

        $component->assertNotDispatched('set-modal-url');
    });

    it('still rewrites the url for chrome-only arguments', function (): void {
        Route::livewire('test-chrome-args/{modelId}', 'zz-modal-target')
            ->name('test.chrome.args');

        $component = Livewire::test('noerd-modal::noerd-modal')
            ->dispatch('noerdModal', arguments: [
                'modelId' => 5,
                'relations' => [],
                'quickCreate' => true,
            ], route: 'test.chrome.args');

        $modal = array_values($component->get('modals'))[0];

        expect($modal['url'])->toBe(route('test.chrome.args', ['modelId' => 5, 'modal' => 'true']));

        $component->assertDispatched('set-modal-url');
    });

    it('skips the url rewrite when relations prefill the form', function (): void {
        // "New" from a list narrowed by a parent record: a reload of
        // /…/new would open the form WITHOUT that prefill.
        Route::livewire('test-prefilled/{modelId}', 'zz-modal-target')
            ->name('test.prefilled');

        $component = Livewire::test('noerd-modal::noerd-modal')
            ->dispatch('noerdModal', arguments: [
                'modelId' => null,
                'relations' => ['accountId' => 7],
            ], route: 'test.prefilled');

        $modal = array_values($component->get('modals'))[0];

        expect($modal['componentName'])->toBe('zz-modal-target')
            ->and($modal['url'])->toBeNull();

        $component->assertNotDispatched('set-modal-url');
    });

    it('treats a dotted component name as a component, never as a route', function (): void {
        $component = Livewire::test('noerd-modal::noerd-modal')
            ->dispatch('noerdModal', modalComponent: 'zz-modal-target', arguments: []);

        $modal = array_values($component->get('modals'))[0];

        expect($modal['componentName'])->toBe('zz-modal-target');
        expect($modal['url'])->toBeNull();
    });

    it('opens a flashed modal on mount with its url', function (): void {
        session()->flash('noerd-modal.open', [
            'component' => 'zz-modal-target',
            'arguments' => ['modelId' => 7],
            'url' => 'https://example.test/crm/account/7?modal=true',
        ]);

        $component = Livewire::test('noerd-modal::noerd-modal');

        $modal = array_values($component->get('modals'))[0];
        expect($modal['componentName'])->toBe('zz-modal-target');
        expect($modal['arguments'])->toBe(['modelId' => 7]);
        expect($modal['url'])->toBe('https://example.test/crm/account/7?modal=true');

        $component->assertDispatched('set-modal-url');
    });

    it('skips a flashed modal whose component is refused', function (): void {
        session()->flash('noerd-modal.open', [
            'component' => 'zz-modal-refused-target',
            'arguments' => ['modelId' => 7],
            'url' => 'https://example.test/zz/7?modal=true',
        ]);

        $component = Livewire::test('noerd-modal::noerd-modal');

        expect($component->get('modals'))->toBeEmpty();

        $component->assertNotDispatched('set-modal-url');
    });

    it('skips a flashed modal whose component no longer exists', function (): void {
        session()->flash('noerd-modal.open', [
            'component' => 'zz-modal-component-that-does-not-exist',
            'arguments' => [],
            'url' => 'https://example.test/zz?modal=true',
        ]);

        expect(Livewire::test('noerd-modal::noerd-modal')->get('modals'))->toBeEmpty();
    });

    it('renders the page behind a refused flashed modal', function (): void {
        Route::get('zz-modal-host-page', fn(): string => \Illuminate\Support\Facades\Blade::render(
            '<div>zz host page <livewire:noerd-modal/></div>',
        ))->middleware('web')->name('zz.modal.host.page');

        $this->withSession(['noerd-modal.open' => [
            'component' => 'zz-modal-refused-target',
            'arguments' => [],
        ]])->get(route('zz.modal.host.page'))
            ->assertOk()
            ->assertSee('zz host page')
            ->assertDontSee('zz-modal-refused-target');
    });

    it('mounts without a modal when nothing was flashed', function (): void {
        expect(Livewire::test('noerd-modal::noerd-modal')->get('modals'))->toBeEmpty();
    });

    it('dispatches restore-modal-url for every url modal on closeAllModals', function (): void {
        Route::livewire('zz-account/{modelId}', 'zz-modal-target')->name('zz.account.detail');

        Livewire::test('noerd-modal::noerd-modal')
            ->dispatch('noerdModal', route: 'zz.account.detail', arguments: ['modelId' => 1])
            ->dispatch('noerdModal', route: 'zz.account.detail', arguments: ['modelId' => 2])
            ->dispatch('closeAllModals')
            ->assertDispatched('restore-modal-url');
    });
});

function renderModalPanel(string $component = 'zz-modal-target'): string
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
