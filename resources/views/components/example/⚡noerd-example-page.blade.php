<?php

use Livewire\Component;

new class extends Component {

};
?>

<div>

    <div class="m-4">
        <button type="button"
                class="rounded-md bg-indigo-600 px-3.5 py-2.5 text-sm font-semibold text-white shadow-xs hover:bg-indigo-500 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600 dark:bg-indigo-500 dark:shadow-none dark:hover:bg-indigo-400 dark:focus-visible:outline-indigo-500"
                @click="$modal('example.noerd-example-component', { name: 'John Doe' })">
            Open Livewire Component in a Modal
        </button>
    </div>
</div>
