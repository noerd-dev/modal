<?php

declare(strict_types=1);

use Livewire\Livewire;

uses(Tests\TestCase::class);

describe('Modal Manager', function (): void {
    it('opens a modal when noerdModal event is dispatched', function (): void {
        $component = Livewire::test('noerd-modal')
            ->dispatch(
                'noerdModal',
                modalComponent: 'example.noerd-example-component',
                arguments: ['name' => 'John Doe'],
            );

        $modals = $component->get('modals');
        expect($modals)->toHaveCount(1);

        $modal = array_values($modals)[0];
        expect($modal['componentName'])->toBe('example.noerd-example-component');
        expect($modal['arguments'])->toBe(['name' => 'John Doe']);
        expect($modal['show'])->toBeTrue();
    });

    it('creates unique modal keys for each modal', function (): void {
        $component = Livewire::test('noerd-modal')
            ->dispatch('noerdModal', modalComponent: 'example.noerd-example-component', arguments: [])
            ->dispatch('noerdModal', modalComponent: 'example.noerd-example-component', arguments: ['count' => 2]);

        $modals = $component->get('modals');
        $keys = array_keys($modals);

        expect($keys)->toHaveCount(2);
        expect($keys[0])->not->toBe($keys[1]);
    });

    it('tracks modal iteration for stacking', function (): void {
        $component = Livewire::test('noerd-modal')
            ->dispatch('noerdModal', modalComponent: 'example.noerd-example-component', arguments: [])
            ->dispatch('noerdModal', modalComponent: 'example.noerd-example-component', arguments: ['count' => 2]);

        $modals = $component->get('modals');
        $iterations = array_column($modals, 'iteration');

        expect($iterations)->toContain(1);
        expect($iterations)->toContain(2);
    });

    it('marks only the top modal as topModal', function (): void {
        $component = Livewire::test('noerd-modal')
            ->dispatch('noerdModal', modalComponent: 'example.noerd-example-component', arguments: [])
            ->dispatch('noerdModal', modalComponent: 'example.noerd-example-component', arguments: ['count' => 2])
            ->dispatch('noerdModal', modalComponent: 'example.noerd-example-component', arguments: ['count' => 3]);

        $modals = $component->get('modals');
        $topModals = array_filter($modals, fn($modal) => $modal['topModal'] === true);

        expect($topModals)->toHaveCount(1);

        // The last modal should be the top modal
        $lastModal = end($modals);
        expect($lastModal['topModal'])->toBeTrue();
    });

    it('closes a modal when closeModal event is dispatched', function (): void {
        $component = Livewire::test('noerd-modal')
            ->dispatch('noerdModal', modalComponent: 'example.noerd-example-component', arguments: []);

        $modals = $component->get('modals');
        $modalKey = array_keys($modals)[0];

        $component->call('closeModal', 'example.noerd-example-component', null, $modalKey);

        expect($component->get('modals'))->toBeEmpty();
    });

    it('dispatches modal-closed-global when all modals are closed', function (): void {
        $component = Livewire::test('noerd-modal')
            ->dispatch('noerdModal', modalComponent: 'example.noerd-example-component', arguments: []);

        $modals = $component->get('modals');
        $modalKey = array_keys($modals)[0];

        $component->call('closeModal', 'example.noerd-example-component', null, $modalKey)
            ->assertDispatched('modal-closed-global');
    });

    it('does not dispatch modal-closed-global when modals remain open', function (): void {
        $component = Livewire::test('noerd-modal')
            ->dispatch('noerdModal', modalComponent: 'example.noerd-example-component', arguments: [])
            ->dispatch('noerdModal', modalComponent: 'example.noerd-example-component', arguments: ['count' => 2]);

        $modals = $component->get('modals');
        $firstModalKey = array_keys($modals)[0];

        $component->call('closeModal', 'example.noerd-example-component', null, $firstModalKey)
            ->assertNotDispatched('modal-closed-global');

        // One modal should remain
        expect($component->get('modals'))->toHaveCount(1);
    });

    it('closes only the top modal when closeTopModal is dispatched', function (): void {
        $component = Livewire::test('noerd-modal')
            ->dispatch('noerdModal', modalComponent: 'example.noerd-example-component', arguments: ['id' => 1])
            ->dispatch('noerdModal', modalComponent: 'example.noerd-example-component', arguments: ['id' => 2])
            ->dispatch('noerdModal', modalComponent: 'example.noerd-example-component', arguments: ['id' => 3]);

        expect($component->get('modals'))->toHaveCount(3);

        // Dispatch closeTopModal (simulates ESC key press)
        $component->dispatch('closeTopModal');

        // Should directly close the top modal
        expect($component->get('modals'))->toHaveCount(2);
    });

    it('closes nested modals one by one with multiple closeTopModal dispatches', function (): void {
        $component = Livewire::test('noerd-modal')
            ->dispatch('noerdModal', modalComponent: 'example.noerd-example-component', arguments: ['id' => 1])
            ->dispatch('noerdModal', modalComponent: 'example.noerd-example-component', arguments: ['id' => 2]);

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
        $component = Livewire::test('noerd-modal')
            ->dispatch(
                'noerdModal',
                modalComponent: 'example.noerd-example-component',
                source: 'customers-list',
                arguments: [],
            );

        expect($component->get('modals'))->toHaveCount(1);

        $component->dispatch('closeTopModal')
            ->assertDispatched('refreshList-customers-list');

        expect($component->get('modals'))->toBeEmpty();
    });

    it('does nothing when closeTopModal is dispatched with no modals open', function (): void {
        $component = Livewire::test('noerd-modal')
            ->dispatch('closeTopModal');

        // Should not dispatch any refresh event since there's nothing to close
        $component->assertNotDispatched('refreshList-*');
        $component->assertNotDispatched('modal-closed-global');
        expect($component->get('modals'))->toBeEmpty();
    });

    it('stores source parameter in modal', function (): void {
        $component = Livewire::test('noerd-modal')
            ->dispatch(
                'noerdModal',
                modalComponent: 'example.noerd-example-component',
                source: 'test-source',
                arguments: [],
            );

        $modals = $component->get('modals');
        $modal = array_values($modals)[0];

        expect($modal['source'])->toBe('test-source');
    });

    it('toggles fullscreen session state', function (): void {
        $component = Livewire::test('noerd-modal');

        expect(session('modal_fullscreen'))->toBeNull();

        $component->call('toggleFullscreen');
        expect(session('modal_fullscreen'))->toBeTrue();

        $component->call('toggleFullscreen');
        expect(session('modal_fullscreen'))->toBeNull();
    });
});

describe('Example Component', function (): void {
    it('initializes count to 1', function (): void {
        Livewire::test('example.noerd-example-component')
            ->assertSet('count', 1);
    });

    it('increments count when upCount is called', function (): void {
        Livewire::test('example.noerd-example-component')
            ->assertSet('count', 1)
            ->call('upCount')
            ->assertSet('count', 2);
    });

    it('increments count multiple times', function (): void {
        Livewire::test('example.noerd-example-component')
            ->assertSet('count', 1)
            ->call('upCount')
            ->assertSet('count', 2)
            ->call('upCount')
            ->assertSet('count', 3)
            ->call('upCount')
            ->assertSet('count', 4);
    });

    it('can set count via wire:model', function (): void {
        Livewire::test('example.noerd-example-component')
            ->set('count', 5)
            ->assertSet('count', 5);
    });

    it('displays the current count', function (): void {
        Livewire::test('example.noerd-example-component')
            ->assertSee('Counter: 1')
            ->call('upCount')
            ->assertSee('Counter: 2');
    });
});
