@php
    use Illuminate\Foundation\Vite;
    $vite = clone app(Vite::class);
@endphp

{{
    $vite->useHotFile(base_path('public/vendor/noerd-modal/hot'))
        ->useBuildDirectory('vendor/noerd-modal')
        ->withEntryPoints([
            'resources/js/noerd-modal.js',
        ])
}}
