<?php

declare(strict_types=1);

namespace NoerdModal\Tests\Fixtures;

use Livewire\Component;

/**
 * A plain component the tests open in the modal stack.
 */
class ZzModalTarget extends Component
{
    public mixed $modelId = null;

    /** @var array<string, mixed> */
    public array $relations = [];

    public function render(): string
    {
        return '<div>zz-modal-target</div>';
    }
}
