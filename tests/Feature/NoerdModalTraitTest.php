<?php

declare(strict_types=1);

use Livewire\Component;
use Livewire\Livewire;
use NoerdModal\Traits\NoerdModalTrait;

uses(Tests\TestCase::class);

/**
 * Test component that uses the NoerdModalTrait.
 */
class TraitTestComponent extends Component
{
    use NoerdModalTrait;

    public const COMPONENT = 'customers-list';

    public array $model = [];

    public function render()
    {
        return '<div>Test Component</div>';
    }

    public function getSelectEventPublic(): string
    {
        return $this->getSelectEvent();
    }

    public function extractRulesFromFieldsPublic(array $fields): array
    {
        $rules = [];
        $this->extractRulesFromFields($fields, $rules);

        return $rules;
    }
}

class ProductsListComponent extends Component
{
    use NoerdModalTrait;

    public const COMPONENT = 'products-list';

    public function render()
    {
        return '<div>Products List</div>';
    }

    public function getSelectEventPublic(): string
    {
        return $this->getSelectEvent();
    }
}

class BankAccountsListComponent extends Component
{
    use NoerdModalTrait;

    public const COMPONENT = 'bank-accounts-list';

    public function render()
    {
        return '<div>Bank Accounts List</div>';
    }

    public function getSelectEventPublic(): string
    {
        return $this->getSelectEvent();
    }
}

beforeEach(function (): void {
    Livewire::component('trait-test-component', TraitTestComponent::class);
    Livewire::component('products-list-component', ProductsListComponent::class);
    Livewire::component('bank-accounts-list-component', BankAccountsListComponent::class);
});

describe('NoerdModalTrait - getSelectEvent', function (): void {
    it('derives customerSelected from customers-list', function (): void {
        $component = Livewire::test('trait-test-component');

        expect($component->instance()->getSelectEventPublic())->toBe('customerSelected');
    });

    it('derives productSelected from products-list', function (): void {
        $component = Livewire::test('products-list-component');

        expect($component->instance()->getSelectEventPublic())->toBe('productSelected');
    });

    it('derives bankAccountSelected from bank-accounts-list', function (): void {
        $component = Livewire::test('bank-accounts-list-component');

        expect($component->instance()->getSelectEventPublic())->toBe('bankAccountSelected');
    });
});

describe('NoerdModalTrait - extractRulesFromFields', function (): void {
    it('extracts required rules from simple fields', function (): void {
        $component = Livewire::test('trait-test-component');

        $fields = [
            ['name' => 'model.name', 'required' => true],
            ['name' => 'model.email', 'required' => true],
            ['name' => 'model.phone', 'required' => false],
        ];

        $rules = $component->instance()->extractRulesFromFieldsPublic($fields);

        expect($rules)->toHaveKey('model.name');
        expect($rules)->toHaveKey('model.email');
        expect($rules)->not->toHaveKey('model.phone');
        expect($rules['model.name'])->toBe(['required']);
        expect($rules['model.email'])->toBe(['required']);
    });

    it('skips fields without name property', function (): void {
        $component = Livewire::test('trait-test-component');

        $fields = [
            ['name' => 'model.name', 'required' => true],
            ['type' => 'divider'],
            ['required' => true],
        ];

        $rules = $component->instance()->extractRulesFromFieldsPublic($fields);

        expect($rules)->toHaveCount(1);
        expect($rules)->toHaveKey('model.name');
    });

    it('recursively extracts rules from block fields', function (): void {
        $component = Livewire::test('trait-test-component');

        $fields = [
            ['name' => 'model.name', 'required' => true],
            [
                'type' => 'block',
                'fields' => [
                    ['name' => 'model.address', 'required' => true],
                    ['name' => 'model.city', 'required' => true],
                ],
            ],
        ];

        $rules = $component->instance()->extractRulesFromFieldsPublic($fields);

        expect($rules)->toHaveCount(3);
        expect($rules)->toHaveKey('model.name');
        expect($rules)->toHaveKey('model.address');
        expect($rules)->toHaveKey('model.city');
    });

    it('handles deeply nested block fields', function (): void {
        $component = Livewire::test('trait-test-component');

        $fields = [
            [
                'type' => 'block',
                'fields' => [
                    [
                        'type' => 'block',
                        'fields' => [
                            ['name' => 'model.deep_field', 'required' => true],
                        ],
                    ],
                ],
            ],
        ];

        $rules = $component->instance()->extractRulesFromFieldsPublic($fields);

        expect($rules)->toHaveCount(1);
        expect($rules)->toHaveKey('model.deep_field');
    });

    it('returns empty rules when no required fields exist', function (): void {
        $component = Livewire::test('trait-test-component');

        $fields = [
            ['name' => 'model.name', 'required' => false],
            ['name' => 'model.email'],
        ];

        $rules = $component->instance()->extractRulesFromFieldsPublic($fields);

        expect($rules)->toBeEmpty();
    });
});

describe('NoerdModalTrait - selectAction', function (): void {
    it('dispatches selection event with model id and context', function (): void {
        Livewire::test('trait-test-component')
            ->set('context', 'test-context')
            ->call('selectAction', 123)
            ->assertDispatched('customerSelected', fn ($name, $params) => $params[0] === 123 && $params[1] === 'test-context');
    });

    it('dispatches closeTopModal event', function (): void {
        Livewire::test('trait-test-component')
            ->call('selectAction', 456)
            ->assertDispatched('closeTopModal');
    });
});

describe('NoerdModalTrait - closeModalProcess', function (): void {
    it('resets currentTab to 1', function (): void {
        Livewire::test('trait-test-component')
            ->set('currentTab', 3)
            ->call('closeModalProcess')
            ->assertSet('currentTab', 1);
    });

    it('dispatches closeTopModal event', function (): void {
        Livewire::test('trait-test-component')
            ->call('closeModalProcess')
            ->assertDispatched('closeTopModal');
    });

    it('dispatches refreshList event when source is provided', function (): void {
        Livewire::test('trait-test-component')
            ->call('closeModalProcess', 'customers-list')
            ->assertDispatched('refreshList-customers-list');
    });

    it('does not dispatch refreshList event when source is null', function (): void {
        Livewire::test('trait-test-component')
            ->call('closeModalProcess', null)
            ->assertNotDispatched('refreshList-*');
    });
});

describe('NoerdModalTrait - storeProcess', function (): void {
    it('sets showSuccessIndicator to true', function (): void {
        Livewire::test('trait-test-component')
            ->assertSet('showSuccessIndicator', false)
            ->call('storeProcess', null)
            ->assertSet('showSuccessIndicator', true);
    });
});

describe('NoerdModalTrait - mountModalProcess', function (): void {
    it('sets pageLayout when provided', function (): void {
        $pageLayout = [
            'title' => 'Test Title',
            'fields' => [
                ['name' => 'model.name', 'type' => 'text'],
            ],
        ];

        $component = Livewire::test('trait-test-component');
        $component->instance()->mountModalProcess('test-component', null, $pageLayout);

        expect($component->instance()->pageLayout)->toBe($pageLayout);
    });

    it('does not set pageLayout when null', function (): void {
        $component = Livewire::test('trait-test-component');
        $component->instance()->pageLayout = ['existing' => 'layout'];
        $component->instance()->mountModalProcess('test-component', null, null);

        expect($component->instance()->pageLayout)->toBe(['existing' => 'layout']);
    });
});

describe('NoerdModalTrait - validateFromLayout', function (): void {
    it('validates required fields from pageLayout', function (): void {
        Livewire::test('trait-test-component')
            ->set('pageLayout', [
                'fields' => [
                    ['name' => 'model.name', 'required' => true],
                ],
            ])
            ->set('model', ['name' => ''])
            ->call('validateFromLayout')
            ->assertHasErrors(['model.name' => 'required']);
    });

    it('passes validation when required fields are filled', function (): void {
        Livewire::test('trait-test-component')
            ->set('pageLayout', [
                'fields' => [
                    ['name' => 'model.name', 'required' => true],
                ],
            ])
            ->set('model', ['name' => 'John Doe'])
            ->call('validateFromLayout')
            ->assertHasNoErrors();
    });

    it('does not validate when no required fields exist', function (): void {
        Livewire::test('trait-test-component')
            ->set('pageLayout', [
                'fields' => [
                    ['name' => 'model.name', 'required' => false],
                ],
            ])
            ->set('model', ['name' => ''])
            ->call('validateFromLayout')
            ->assertHasNoErrors();
    });
});
