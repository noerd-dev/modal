<?php

declare(strict_types=1);

use Livewire\Livewire;

uses(Tests\TestCase::class);

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

    it('never reads the shared preference for a forced fullscreen modal', function (): void {
        $html = renderModalPanel('noerd-modal::example.noerd-example-fullscreen-component');

        expect($html)->toContain("true ? 'sm:max-w-full");
        expect($html)->not->toContain("\$store.app.modalFullscreen ? 'sm:max-w-full");
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

describe('Open as page', function (): void {
    it('renders no open-as-page button by default', function (): void {
        $component = Livewire::test('noerd-modal::noerd-modal')
            ->dispatch(
                'noerdModal',
                modalComponent: 'noerd-modal::example.noerd-example-component',
                arguments: [],
            );

        expect(array_values($component->get('modals'))[0]['openAsPage'])->toBeFalse();

        $component->assertDontSee(__('Open as page'));
    });

    it('renders the button for a component that opts in via its openAsPage property', function (): void {
        Livewire::component('open-as-page-opt-in', new class extends \Livewire\Component {
            public bool $openAsPage = true;

            public function render(): string
            {
                return '<div>opt-in body</div>';
            }
        });

        $component = Livewire::test('noerd-modal::noerd-modal')
            ->dispatch('noerdModal', modalComponent: 'open-as-page-opt-in', arguments: []);

        expect(array_values($component->get('modals'))[0]['openAsPage'])->toBeTrue();

        $component->assertSee(__('Open as page'));
    });

    it('renders no button for an opted-in component in the narrow panel', function (): void {
        Livewire::component('open-as-page-narrow', new class extends \Livewire\Component {
            public bool $openAsPage = true;

            public function render(): string
            {
                return '<div>narrow body</div>';
            }
        });

        Livewire::test('noerd-modal::noerd-modal')
            ->dispatch('noerdModal', modalComponent: 'open-as-page-narrow', arguments: [], size: 'narrow')
            ->assertDontSee(__('Open as page'));
    });
});
