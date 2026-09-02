<?php

declare(strict_types=1);

use Illuminate\Support\Facades\File;
use NoerdModal\Providers\NoerdModalServiceProvider;

uses(Tests\TestCase::class);

/*
 | The provider writes into the host on EVERY boot: it copies config/noerd-modal.php
 | when the host has none and refreshes public/vendor/noerd-modal whenever the
 | shipped bundle is newer. Both run against a throwaway base path.
 */
beforeEach(function (): void {
    $this->originalBasePath = $this->app->basePath();
    $this->hostPath = storage_path('framework/testing/zz-noerd-modal-boot-' . getmypid());

    File::deleteDirectory($this->hostPath);

    // Deliberately NOT creating config/ — the provider has to create the
    // directory it copies into.
    $this->app->setBasePath($this->hostPath);

    $this->moduleDir = dirname(__DIR__, 2);
    $this->bootProvider = fn() => (new NoerdModalServiceProvider($this->app))->boot();
});

afterEach(function (): void {
    $this->app->setBasePath($this->originalBasePath);
    File::deleteDirectory($this->hostPath);
});

it('publishes the module config on boot when the host has none', function (): void {
    expect(File::isDirectory(config_path()))->toBeFalse();

    ($this->bootProvider)();

    expect(File::get(config_path('noerd-modal.php')))
        ->toBe(File::get($this->moduleDir . '/config/noerd-modal.php'));
});

it('never overwrites a host config the user has edited', function (): void {
    File::ensureDirectoryExists(config_path());
    File::put(config_path('noerd-modal.php'), "<?php\n\nreturn ['position' => 'right'];\n");

    ($this->bootProvider)();

    expect(File::get(config_path('noerd-modal.php')))->toContain("'position' => 'right'");
});

it('publishes the built assets on boot when the host has none', function (): void {
    ($this->bootProvider)();

    expect(File::get(public_path('vendor/noerd-modal/manifest.json')))
        ->toBe(File::get($this->moduleDir . '/dist/build/manifest.json'));
});

it('refreshes published assets that are older than the shipped bundle', function (): void {
    $target = public_path('vendor/noerd-modal/manifest.json');
    File::ensureDirectoryExists(dirname($target));
    File::put($target, '{"stale": true}');
    touch($target, File::lastModified($this->moduleDir . '/dist/build/manifest.json') - 3600);

    ($this->bootProvider)();

    expect(File::get($target))->not->toContain('stale');
});

it('leaves published assets newer than the shipped bundle untouched', function (): void {
    $target = public_path('vendor/noerd-modal/manifest.json');
    File::ensureDirectoryExists(dirname($target));
    File::put($target, '{"local": true}');
    touch($target, File::lastModified($this->moduleDir . '/dist/build/manifest.json') + 3600);

    ($this->bootProvider)();

    expect(File::get($target))->toContain('local');
});
