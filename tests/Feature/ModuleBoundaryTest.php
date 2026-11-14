<?php

declare(strict_types=1);

use Illuminate\Support\Facades\File;

uses(NoerdModal\Tests\TestCase::class);

/*
 | noerd/modal depends on Laravel and Livewire only — noerd/noerd requires it,
 | never the other way round. The ONLY references to the core are the two
 | try/catch-guarded StaticConfigHelper probes of the quickCreate opt-in.
 */
it('never references noerd/noerd outside the guarded quickCreate probes', function (): void {
    $root = dirname(__DIR__, 2);
    $hits = [];

    foreach (['src', 'resources', 'config'] as $directory) {
        foreach (File::allFiles($root . '/' . $directory) as $file) {
            // The Boost guideline is documentation and names core classes on purpose.
            if (! in_array($file->getExtension(), ['php', 'js'], true) || str_starts_with($file->getRelativePath(), 'boost')) {
                continue;
            }

            foreach (explode("\n", $file->getContents()) as $number => $line) {
                if (preg_match('/(?<![A-Za-z0-9_])Noerd\\\\/', $line) === 1) {
                    $hits[] = $directory . '/' . $file->getRelativePathname() . ':' . ($number + 1) . ' ' . trim($line);
                }
            }
        }
    }

    expect($hits)->toHaveCount(2);

    foreach ($hits as $hit) {
        expect($hit)->toStartWith('resources/views/components/noerd-modal.blade.php:')
            ->toContain('\Noerd\Helpers\StaticConfigHelper::');
    }
});

it('declares no dependency on noerd/noerd', function (): void {
    $composer = json_decode(File::get(dirname(__DIR__, 2) . '/composer.json'), true);

    expect(array_keys($composer['require'] ?? []))->not->toContain('noerd/noerd')
        ->and(array_keys($composer['require-dev'] ?? []))->not->toContain('noerd/noerd');
});
