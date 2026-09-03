@php
    use Illuminate\Foundation\Vite;
    use NoerdModal\Support\AssetManifest;

    // Written by `npm run dev` inside the package — while the Vite dev server
    // runs, the bundle is served from there instead of the package route.
    $hotFile = base_path('public/vendor/noerd-modal/hot');
@endphp

@if (file_exists($hotFile))
    {{ (clone app(Vite::class))->useHotFile($hotFile)->withEntryPoints([AssetManifest::SCRIPT_ENTRY]) }}
@else
    @foreach (AssetManifest::styleFiles() as $styleFile)
        <link rel="stylesheet" href="{{ route('noerd-modal.asset', ['file' => $styleFile]) }}">
    @endforeach
    <script type="module" src="{{ route('noerd-modal.asset', ['file' => AssetManifest::scriptFile()]) }}"></script>
@endif
