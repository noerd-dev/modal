<?php

declare(strict_types=1);

namespace NoerdModal\Tests\Fixtures;

use Livewire\Component;

/**
 * Refuses every mount, like a component an access hook denies the user.
 */
class ZzRefusedModalTarget extends Component
{
    public mixed $modelId = null;

    public function mount(): void
    {
        abort(403);
    }

    public function render(): string
    {
        return '<div>zz-modal-refused-target</div>';
    }
}
