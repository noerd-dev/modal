<?php

use Livewire\Component;
use Livewire\Livewire;
use Illuminate\Support\Str;
use Livewire\Attributes\On;
use Livewire\Attributes\Url;
use Livewire\Attributes\Isolate;

new #[Isolate] class extends Component {

    private const URL_PARAM_BLACKLIST = ['filter', 'currentTab'];

    public array $modals = [];

    #[On('noerdModal')]
    public function bootModal(
        string  $modalComponent,
        array   $arguments = [],
        ?string $source = null,
        ?string $position = null,
    ): void
    {
        $modal = [];
        $modal['componentName'] = $modalComponent;
        $modal['arguments'] = $arguments;
        $modal['show'] = true;
        $modal['topModal'] = false;
        $modal['source'] = $source;
        $modal['position'] = $position ?? config('noerd-modal.position', 'center');
        $modal['key'] = md5(serialize($arguments) . microtime());

        $iteration = 1;
        foreach ($this->modals as $checkModal) {
            if ($checkModal['show'] === true) {
                $iteration++;
            }
        }

        $modal['iteration'] = $iteration;
        $modal['urlParameters'] = $this->resolveUrlParameters($modalComponent);
        $this->modals[$modal['key']] = $modal;

        $this->markTopModal();
    }

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
                foreach ($modal['urlParameters'] ?? [] as $paramName) {
                    $this->dispatch('clear-modal-url-params', modal: $paramName);
                }

                // Close the modal
                $this->closeModal($modal['componentName'], $modal['source'], $modal['key']);
                if ($modal['source']) {
                    $this->dispatch('refreshList-' . Str::afterLast($modal['source'], '.'));
                }

                break;
            }
        }
    }

    private function resolveUrlParameters(string $componentName): array
    {
        try {
            $component = Livewire::new($componentName);
            $reflection = new \ReflectionClass($component);
            $urlParameters = [];

            // Collect from #[Url] attributes on properties
            foreach ($reflection->getProperties() as $property) {
                $attributes = $property->getAttributes(Url::class);
                if (!empty($attributes) && !in_array($property->getName(), self::URL_PARAM_BLACKLIST)) {
                    $urlInstance = $attributes[0]->newInstance();
                    $urlParameters[] = $urlInstance->as ?: $property->getName();
                }
            }

            // Collect from queryString*() methods (trait-based query strings like queryStringNoerdDetail)
            foreach (get_class_methods($component) as $method) {
                if (str_starts_with($method, 'queryString') && $method !== 'queryString') {
                    foreach ($component->$method() as $property => $config) {
                        if (is_array($config) && !in_array($property, self::URL_PARAM_BLACKLIST)) {
                            $urlParameters[] = $config['as'] ?? $property;
                        }
                    }
                }
            }

            return array_unique($urlParameters);
        } catch (\Throwable) {
            return [];
        }
    }

} ?>

<div x-data="{selectedRow: 0, isDragging: false, isLoading: false}">
    <!-- Global modal loading overlay -->
    <div x-show="$store.app.modalLoading"
         x-transition:enter="transition ease-out duration-150"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-100"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 z-[60] flex items-center justify-center bg-gray-800/50"
         style="display: none;"
    >
        <svg class="animate-spin h-10 w-10 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
        </svg>
    </div>

    @isset($modals)
        @foreach($modals as $key => $modal)
            @teleport('body')
                <div x-data="{ show: true }" wire:key="modal-wrapper-{{$modal['key']}}"
                     @if($modal['show'] && $modal['topModal'])
                         x-init="$store.app.modalOpen = true; $store.app.modalLoading = false"
                    @endif
                >
                    <div x-show="show">
                        <x-noerd::modal>
                            <x-noerd::modal.panel :ml="$modal['arguments']['ml'] ?? ''"
                                                  :iteration="$modal['iteration']"
                                                  :source="$modal['source']"
                                                  :modalKey="$modal['key']"
                                                  :modal="$modal['componentName']"
                                                  :position="$modal['position']">
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
