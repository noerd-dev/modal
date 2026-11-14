<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Blade;

uses(NoerdModal\Tests\TestCase::class);

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

it('renders the panel as a labelled modal dialog', function (): void {
    $html = Blade::render(
        '<x-noerd::modal.panel :modal="1" modalKey="zz-key">body</x-noerd::modal.panel>',
    );

    expect($html)->toContain('role="dialog"')
        ->toContain('aria-modal="true"')
        ->toContain('aria-labelledby="noerd-modal-zz-key-title"')
        ->toContain("modalTitleId: 'noerd-modal-zz-key-title'")
        ->toContain('id="noerd-modal-zz-key"')
        ->not->toContain('id="modal"')
        ->not->toContain('x-noerd::');
});

it('gives every stacked panel its own id', function (): void {
    $first = Blade::render('<x-noerd::modal.panel :modal="1" modalKey="zz-a">a</x-noerd::modal.panel>');
    $second = Blade::render('<x-noerd::modal.panel :modal="1" modalKey="zz-b">b</x-noerd::modal.panel>');

    preg_match('/id="(noerd-modal-[^"]+)"/', $first, $firstId);
    preg_match('/id="(noerd-modal-[^"]+)"/', $second, $secondId);

    expect($firstId[1])->not->toBe($secondId[1]);
});
