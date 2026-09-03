<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Artisan;

uses(Tests\TestCase::class);

it('registers only its own publish commands', function (): void {
    $commands = Artisan::all();

    expect($commands)->toHaveKey('noerd-modal:publish-example');
    expect($commands)->toHaveKey('noerd-modal:publish-panel');
});

/**
 * noerd/modal does not depend on noerd/noerd (the dependency runs the other way),
 * so it must never register install/update commands built on the core's
 * HasModuleInstallation / RequiresNoerdInstallation traits — they would fatal in a
 * standalone Livewire host. config/noerd-modal.php is merged via mergeConfigFrom()
 * and published only on request (vendor:publish --tag=noerd-modal-config); the
 * bundle is served by the package's asset route, never copied into public/.
 */
it('ships no install or update command', function (): void {
    $commands = Artisan::all();

    expect($commands)->not->toHaveKey('noerd:install-modal');
    expect($commands)->not->toHaveKey('noerd:update-modal');
});
