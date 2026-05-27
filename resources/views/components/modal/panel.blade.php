@php
    $forceFullscreen = $forceFullscreen ?? false;
    $isFullscreen = $forceFullscreen || session('modal_fullscreen', false);
    $isRight = ($position ?? 'center') === 'right';
    $topModal = $topModal ?? true;
    $isStacked = ($iteration ?? 1) > 1;
    $depth = $depth ?? 0;
    $modelId = $modelId ?? null;
    $disableOpenAsPage = $disableOpenAsPage ?? false;

    $detailUrl = null;
    if (isset($modal) && ! $disableOpenAsPage && config('noerd-modal.open_as_page', true)) {
        foreach (\Illuminate\Support\Facades\Route::getRoutes() as $registeredRoute) {
            if (($registeredRoute->getAction()['livewire_component'] ?? null) === $modal) {
                try {
                    $detailUrl = $modelId
                        ? route($registeredRoute->getName(), ['modelId' => $modelId])
                        : route($registeredRoute->getName());
                } catch (\Throwable) {
                    $detailUrl = null;
                }
                break;
            }
        }

        if (! $detailUrl) {
            $params = ['component' => $modal];
            if ($modelId) {
                $params['modelId'] = $modelId;
            }
            $detailUrl = route('modal.page', $params);
        }
    }
@endphp
<div
    x-noerd::dialog
    x-show="open"
    x-init="setTimeout(() => { open = true; $store.app.modalFullscreen = {{ $isFullscreen ? 'true' : 'false' }} }, 0)"
    x-transition:enter="transition ease-out duration-300"
    x-transition:enter-start="opacity-0"
    x-transition:enter-end="opacity-100"
    x-transition:leave="transition ease-in duration-300"
    x-transition:leave-start="opacity-100"
    x-transition:leave-end="opacity-0"
    @class([
        'fixed transition-opacity w-full ml-auto inset-0 flex z-50',
        'justify-end' => $isRight,
    ])
>
    <!-- Overlay -->
    <div x-noerd::dialog:overlay
        @class([
            'fixed inset-0',
            'bg-gray-800/50' => !$isStacked,
        ])>
    </div>

    @if($isRight)
        <!-- Panel (right position) -->
        <div x-show="open" id="modal" modal="{{$modal}}"
             class="relative w-full h-[100dvh] items-start justify-end"
             x-transition:enter="transition transform ease-out duration-200"
             x-transition:enter-start="translate-x-full"
             x-transition:enter-end="translate-x-0"
             x-transition:leave="transition transform ease-in duration-200"
             x-transition:leave-start="translate-x-0"
             x-transition:leave-end="translate-x-full"
        >
            <div x-trap="open" @class([
                'bg-white ml-auto shadow-sm relative h-[100dvh] transition-all duration-200 ease-out origin-right',
                'max-w-full' => $isFullscreen,
                'max-w-7xl' => !$isFullscreen,
                'scale-[0.97]' => $depth === 1,
                'scale-[0.94]' => $depth === 2,
                'scale-[0.91]' => $depth === 3,
                'scale-[0.88]' => $depth === 4,
                'scale-[0.85]' => $depth >= 5,
            ]) x-data="{ isRight: true }">

                @if(!$topModal)
                    <div class="absolute inset-0 bg-gray-800/20 z-[51] pointer-events-none"></div>
                @endif

                @if(!$forceFullscreen)
                    <!-- Fullscreen Toggle Button (desktop only) -->
                    <button type="button"
                            wire:click.prevent="toggleFullscreen"
                            class="my-auto inline-flex items-center justify-center transition focus:outline-hidden focus:ring-2 focus:ring-offset-2 rounded-sm h-8 w-8 text-gray-700 hover:bg-gray-100 hidden! sm:flex! absolute! right-0 top-4 mt-2 mr-16 border! border-gray-300!">
                        <span class="sr-only">Toggle fullscreen</span>
                        @if($isFullscreen)
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 9V4.5M9 9H4.5M9 9 3.75 3.75M9 15v4.5M9 15H4.5M9 15l-5.25 5.25M15 9h4.5M15 9V4.5M15 9l5.25-5.25M15 15h4.5M15 15v4.5m0-4.5 5.25 5.25" />
                            </svg>
                        @else
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 3.75v4.5m0-4.5h4.5m-4.5 0L9 9m11.25-5.25v4.5m0-4.5h-4.5m4.5 0L15 9m5.25 11.25v-4.5m0 4.5h-4.5m4.5 0L15 15M3.75 20.25v-4.5m0 4.5h4.5m-4.5 0L9 15" />
                            </svg>
                        @endif
                    </button>
                @endif

                @if($detailUrl)
                    <!-- Open as Page Button (desktop only) -->
                    <a href="{{ $detailUrl }}"
                       class="my-auto inline-flex items-center justify-center transition focus:outline-hidden focus:ring-2 focus:ring-offset-2 rounded-sm h-8 w-8 text-gray-700 hover:bg-gray-100 hidden! sm:flex! absolute! right-0 top-4 mt-2 mr-[6.5rem] border! border-gray-300!">
                        <span class="sr-only">{{ __('Open as page') }}</span>
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 6H5.25A2.25 2.25 0 0 0 3 8.25v10.5A2.25 2.25 0 0 0 5.25 21h10.5A2.25 2.25 0 0 0 18 18.75V10.5m-10.5 6L21 3m0 0h-5.25M21 3v5.25" />
                        </svg>
                    </a>
                @endif

                <!-- Close Button -->
                <button type="button"
                        @click="open = false; setTimeout(() => Livewire.dispatch('closeTopModal'), 200)"
                        class="my-auto inline-flex items-center justify-center transition focus:outline-hidden focus:ring-2 focus:ring-offset-2 rounded-sm h-8 w-8 hover:bg-gray-100 absolute! right-0 top-4 mt-2 mr-6 border! border-gray-300! text-gray-600!">
                    <span class="sr-only">Close modal</span>
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                    </svg>
                </button>

                <div x-data="{ isModal: true, isRight: true }" class="p-6 pt-12 h-full overflow-y-auto"
                     x-effect="if(open) setTimeout(() => { const el = $el.querySelector('input:not([type=hidden]):not([disabled]), textarea:not([disabled]), select:not([disabled])'); if(el) el.focus(); }, 150)">
                    {{ $slot }}
                </div>
            </div>
        </div>
    @else
        <!-- Panel (center position) -->
        <div x-show="open" id="modal" modal="{{$modal}}"
             @class([
                'relative w-full justify-center',
                'h-[100dvh] my-0 items-start',
                'sm:h-[100dvh] sm:items-start' => $isFullscreen,
                'sm:h-auto sm:max-h-[100dvh] sm:py-14 sm:my-auto sm:items-center' => !$isFullscreen,
            ])
             x-transition:enter="transition transform ease-out duration-100"
             x-transition:enter-start="translate-y-1/2"
             x-transition:enter-end="translate-y-0"
             x-transition:leave="transition transform ease-in duration-100"
             x-transition:leave-start="translate-y-0"
             x-transition:leave-end="translate-y-full"
        >
            <div x-trap="open" @class([
                'bg-white mx-auto shadow-sm relative transition-all duration-200 ease-out',
                'max-w-full h-[100dvh] rounded-none',
                'sm:max-w-full sm:h-[calc(100dvh-3.5rem)] sm:mt-14 sm:rounded-none' => $isFullscreen,
                'sm:max-w-7xl sm:h-full sm:max-h-[calc(100vh-112px)] sm:rounded' => !$isFullscreen,
                'scale-[0.97]' => $depth === 1,
                'scale-[0.94]' => $depth === 2,
                'scale-[0.91]' => $depth === 3,
                'scale-[0.88]' => $depth === 4,
                'scale-[0.85]' => $depth >= 5,
            ])>

                @if(!$topModal)
                    <div class="absolute inset-0 bg-gray-800/20 z-[51] pointer-events-none"></div>
                @endif

                @if(!$forceFullscreen)
                    <!-- Fullscreen Toggle Button (desktop only) -->
                    <button type="button"
                            wire:click.prevent="toggleFullscreen"
                            class="my-auto inline-flex items-center justify-center transition focus:outline-hidden focus:ring-2 focus:ring-offset-2 rounded-sm h-8 w-8 text-gray-700 hover:bg-gray-100 hidden! sm:flex! absolute! right-0 top-4 mt-2 mr-16 border! border-gray-300!">
                        <span class="sr-only">Toggle fullscreen</span>
                        @if($isFullscreen)
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 9V4.5M9 9H4.5M9 9 3.75 3.75M9 15v4.5M9 15H4.5M9 15l-5.25 5.25M15 9h4.5M15 9V4.5M15 9l5.25-5.25M15 15h4.5M15 15v4.5m0-4.5 5.25 5.25" />
                            </svg>
                        @else
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 3.75v4.5m0-4.5h4.5m-4.5 0L9 9m11.25-5.25v4.5m0-4.5h-4.5m4.5 0L15 9m5.25 11.25v-4.5m0 4.5h-4.5m4.5 0L15 15M3.75 20.25v-4.5m0 4.5h4.5m-4.5 0L9 15" />
                            </svg>
                        @endif
                    </button>
                @endif

                @if($detailUrl)
                    <!-- Open as Page Button (desktop only) -->
                    <a href="{{ $detailUrl }}"
                       class="my-auto inline-flex items-center justify-center transition focus:outline-hidden focus:ring-2 focus:ring-offset-2 rounded-sm h-8 w-8 text-gray-700 hover:bg-gray-100 hidden! sm:flex! absolute! right-0 top-4 mt-2 mr-[6.5rem] border! border-gray-300!">
                        <span class="sr-only">{{ __('Open as page') }}</span>
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 6H5.25A2.25 2.25 0 0 0 3 8.25v10.5A2.25 2.25 0 0 0 5.25 21h10.5A2.25 2.25 0 0 0 18 18.75V10.5m-10.5 6L21 3m0 0h-5.25M21 3v5.25" />
                        </svg>
                    </a>
                @endif

                <!-- Close Button -->
                <button @click="open = false; setTimeout(() => Livewire.dispatch('closeTopModal'), 200)" type="button"
                        class="my-auto inline-flex items-center justify-center transition focus:outline-hidden focus:ring-2 focus:ring-offset-2 rounded-sm h-8 w-8 hover:bg-gray-100 absolute! right-0 top-4 mt-2 mr-6 border! border-gray-300! text-gray-600!">
                    <span class="sr-only">Close modal</span>
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd"
                              d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z"
                              clip-rule="evenodd"/>
                    </svg>
                </button>

                <div x-data="{ isModal: true, isRight: false }" class="p-6 pt-12"
                     x-effect="if(open) setTimeout(() => { const el = $el.querySelector('input:not([type=hidden]):not([disabled]), textarea:not([disabled]), select:not([disabled])'); if(el) el.focus(); }, 150)">
                    {{ $slot }}
                </div>
            </div>
        </div>
    @endif
</div>
