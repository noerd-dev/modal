<?php

use Livewire\Component;
use Livewire\Livewire;
use Illuminate\Support\Str;
use Livewire\Attributes\On;
use Livewire\Attributes\Url;
use Livewire\Attributes\Isolate;

new #[Isolate] class extends Component {

    private const URL_PARAM_BLACKLIST = ['filter', 'currentTableFilter', 'currentTab'];

    public array $modals = [];

    #[On('noerdModal')]
    public function bootModal(
        string  $modalComponent,
        array   $arguments = [],
        ?string $source = null,
    ): void
    {
        $modal = [];
        $modal['componentName'] = $modalComponent;
        $modal['arguments'] = $arguments;
        $modal['show'] = true;
        $modal['topModal'] = false;
        $modal['source'] = $source;
        $modal['key'] = md5(serialize($arguments) . microtime());

        $iteration = 1;
        foreach ($this->modals as $checkModal) {
            if ($checkModal['show'] === true) {
                $iteration++;
            }
        }

        $modal['iteration'] = $iteration;
        $this->modals[$modal['key']] = $modal;

        $this->markTopModal();
    }

    #[On('closeModal')]
    public function closeModal(string $componentName, ?string $source, ?string $modalKey): void
    {
        $modals = $this->modals;
        foreach ($modals as $modal) {
            if ($modal['componentName'] === $componentName && $modal['key'] === $modalKey) {
                unset($this->modals[$modal['key']]);
            }
        }

        $this->markTopModal();

        $hasOpenModal = false;
        foreach ($this->modals as $modal) {
            if ($modal['show'] === true) {
                $hasOpenModal = true;
                break;
            }
        }

        if (!$hasOpenModal) {
            $this->dispatch('modal-closed-global');
        }
    }

    private function markTopModal(): void
    {
        foreach ($this->modals as $key => $modal) {
            $this->modals[$key]['topModal'] = false;
        }
        $lastKey = null;
        if (count($this->modals) > 0) {
            foreach ($this->modals as $key => $modal) {
                if ($modal['show'] === true) {
                    $lastKey = $key;
                }
            }

            if ($lastKey) {
                $this->modals[$lastKey]['topModal'] = true;
            }
        }
    }

    public function toggleFullscreen(): void
    {
        if (session('modal_fullscreen')) {
            session()->forget('modal_fullscreen');
        } else {
            session(['modal_fullscreen' => true]);
        }
    }

    #[On('closeTopModal')]
    public function closeTopModal(): void
    {
        foreach ($this->modals as $modal) {
            if ($modal['topModal']) {
                // Auto-detect #[Url] properties and clear them (except blacklisted)
                try {
                    $component = Livewire::new($modal['componentName']);
                    $reflection = new \ReflectionClass($component);

                    foreach ($reflection->getProperties() as $property) {
                        $attributes = $property->getAttributes(Url::class);
                        $propertyName = $property->getName();

                        if (!empty($attributes) && !in_array($propertyName, self::URL_PARAM_BLACKLIST)) {
                            $this->dispatch('clear-modal-url-params', modal: $propertyName);
                        }
                    }
                } catch (\Throwable) {
                    // Component couldn't be resolved, skip URL param cleanup
                }

                // Close the modal
                $this->closeModal($modal['componentName'], $modal['source'], $modal['key']);

                if ($modal['source']) {
                    $this->dispatch('refreshList-' . $modal['source']);
                }

                break;
            }
        }
    }

} ?>

<div x-data="{selectedRow: 0, isDragging: false, isLoading: false}">
    @isset($modals)
        @foreach($modals as $key => $modal)
            @teleport('body')
                <div x-data="{ show: true }" wire:key="modal-wrapper-{{$modal['key']}}"
                     @if($modal['show'] && $modal['topModal'])
                         x-init="$store.app.modalOpen = true"
                    @endif
                >
                    <div x-show="show">
                        <x-noerd::modal>
                            <x-noerd::modal.panel :ml="$modal['arguments']['ml'] ?? ''"
                                                  :iteration="$modal['iteration']"
                                                  :source="$modal['source']"
                                                  :modalKey="$modal['key']"
                                                  :modal="$modal['componentName']">
                                <div wire:ignore>
                                    @livewire($modal['componentName'], $modal['arguments'], key($modal['key']))
                                </div>
                            </x-noerd::modal.panel>
                        </x-noerd::modal>
                    </div>
                </div>
            @endteleport
        @endforeach
    @endisset
</div>
