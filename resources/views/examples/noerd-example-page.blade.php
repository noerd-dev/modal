<?php

use Livewire\Component;

/*
 | Example page of noerd/modal, published by `noerd-modal:publish-example` as
 | `example.noerd-example-page` together with the route `/noerd-example-modal`.
 */
new class extends Component {
};
?>

<div>
    <div class="m-4 flex gap-4">
        <button type="button"
                class="rounded-md bg-indigo-600 px-3.5 py-2.5 text-sm font-semibold text-white shadow-xs hover:bg-indigo-500 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600 dark:bg-indigo-500 dark:shadow-none dark:hover:bg-indigo-400 dark:focus-visible:outline-indigo-500"
                @click="$modal('example.noerd-example-component', { count: 1 })">
            Open Livewire Component in a Modal
        </button>

        <button type="button"
                class="rounded-md bg-white px-3.5 py-2.5 text-sm font-semibold text-gray-900 shadow-xs ring-1 ring-gray-300 ring-inset hover:bg-gray-50"
                @click="$modal('example.noerd-example-fullscreen-component')">
            Open a fullscreen Modal
        </button>
    </div>
</div>
