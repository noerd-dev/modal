<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Blade;

uses(Tests\TestCase::class);

/*
 | A stacked panel is scaled down a little so the modals below it stay
 | visible at the edges. A `forceModalFullscreen` panel covers the whole
 | viewport by definition and must therefore keep its size at every depth.
 */
function zzRenderPanel(int $depth, bool $forceFullscreen): string
{
    return Blade::render(
        '<x-noerd::modal.panel :modal="1" :depth="$depth" :forceFullscreen="$forceFullscreen">body</x-noerd::modal.panel>',
        ['depth' => $depth, 'forceFullscreen' => $forceFullscreen],
    );
}

it('scales a stacked panel down', function (): void {
    expect(zzRenderPanel(2, false))->toContain('scale-[0.94]');
});

it('keeps a forced fullscreen panel at full size on every depth', function (): void {
    foreach ([0, 1, 2, 5] as $depth) {
        expect(zzRenderPanel($depth, true))->not->toContain('scale-[');
    }
});
