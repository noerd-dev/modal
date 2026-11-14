<?php

declare(strict_types=1);

use Symfony\Component\Process\ExecutableFinder;
use Symfony\Component\Process\Process;

uses(NoerdModal\Tests\TestCase::class);

it('handles ESC, the loading overlay and the history state in the browser bundle', function (): void {
    $node = (new ExecutableFinder())->find('node');

    if ($node === null) {
        $this->markTestSkipped('node is not available.');
    }

    $process = new Process([$node, 'tests/js/modal-behaviour.mjs'], dirname(__DIR__, 2));
    $process->run();

    expect($process->getExitCode())->toBe(0, $process->getErrorOutput());
});
