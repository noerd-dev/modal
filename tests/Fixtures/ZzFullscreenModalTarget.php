<?php

declare(strict_types=1);

namespace NoerdModal\Tests\Fixtures;

use Livewire\Component;

/**
 * Opts into the permanent fullscreen panel.
 */
class ZzFullscreenModalTarget extends Component
{
    public mixed $modelId = null;

    public bool $forceModalFullscreen = true;

    public function render(): string
    {
        return '<div>zz-modal-fullscreen-target</div>';
    }
}
