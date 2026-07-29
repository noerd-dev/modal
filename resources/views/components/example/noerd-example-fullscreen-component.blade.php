<?php

use Livewire\Component;

new class extends Component {
    /**
     * Opens permanently in fullscreen — the panel therefore ignores the
     * user's fullscreen preference and renders no toggle button.
     */
    public bool $forceModalFullscreen = true;
};
?>

<div>
    <div class="py-4">
        This example modal is always fullscreen.
    </div>
</div>
