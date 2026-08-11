<?php

declare(strict_types=1);

use Symfony\Component\Process\ExecutableFinder;
use Symfony\Component\Process\Process;

uses(Tests\TestCase::class);

describe('Alpine modal source', function (): void {
    it('tags every modal opened from Alpine with the owning Livewire component', function (): void {
        $node = (new ExecutableFinder())->find('node');

        if ($node === null) {
            $this->markTestSkipped('node is not available.');
        }

        $moduleDir = dirname(__DIR__, 2);

        $process = new Process([$node, 'tests/js/modal-source.mjs'], $moduleDir);
        $process->run();

        expect($process->getExitCode())->toBe(0, $process->getErrorOutput());
    });

    it('ships the source resolution in the built asset', function (): void {
        $moduleDir = dirname(__DIR__, 2);
        $manifest = json_decode((string) file_get_contents($moduleDir . '/dist/build/manifest.json'), true);
        $asset = $manifest['resources/js/noerd-modal.js']['file'] ?? null;

        expect($asset)->not->toBeNull();

        // The module ships a prebuilt bundle — a JS change that was never rebuilt
        // would leave every installation on the old behaviour.
        expect(file_get_contents($moduleDir . '/dist/build/' . $asset))
            ->toContain('[wire\\\\:id]');
    });
});
