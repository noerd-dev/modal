<?php

use Livewire\Component;

new class extends Component {
    public int $count = 1;

    public function upCount()
    {
        $this->count = $this->count + 1;
    }
};
?>

<div>
    <div class="py-4 gap-8">


        <input wire:model.live="count" class="border px-2 py-1 rounded" type="number"/>

        Counter: {{$count}}
        <button wire:click="upCount">Up</button>
    </div>


    <button type="button"
            class="rounded-md bg-indigo-600 px-3.5 py-2.5 text-sm font-semibold text-white shadow-xs hover:bg-indigo-500 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600 dark:bg-indigo-500 dark:shadow-none dark:hover:bg-indigo-400 dark:focus-visible:outline-indigo-500"
            @click="$modal('example.noerd-example-component', { count: '{{$count}}' })">
        Open new Modal with count {{$count}}
    </button>
</div>
