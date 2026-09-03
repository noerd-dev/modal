<?php

declare(strict_types=1);

use Illuminate\Support\Facades\File;
use Illuminate\Support\ServiceProvider;
use NoerdModal\Providers\NoerdModalServiceProvider;

uses(Tests\TestCase::class);

/*
 | The provider must never write into the host on its own: the config is
 | merged and only published on request, the bundle is served by the asset
 | route. Everything runs against a throwaway base path so the real
 | config/noerd-modal.php of the project is never touched.
 */
beforeEach(function (): void {
    $this->originalBasePath = $this->app->basePath();
    $this->hostPath = storage_path('framework/testing/zz-noerd-modal-boot-' . getmypid());

    File::deleteDirectory($this->hostPath);
    File::ensureDirectoryExists($this->hostPath);

    $this->app->setBasePath($this->hostPath);

    $this->moduleDir = dirname(__DIR__, 2);
    // Re-booting registers the publishable config against the throwaway
    // base path (the registry is keyed by source path, so the entry replaces
    // the one registered during the real boot).
    $this->bootProvider = fn() => (new NoerdModalServiceProvider($this->app))->boot();
});

afterEach(function (): void {
    $this->app->setBasePath($this->originalBasePath);
    (new NoerdModalServiceProvider($this->app))->boot();
    File::deleteDirectory($this->hostPath);
});

it('writes nothing into the host on boot', function (): void {
    ($this->bootProvider)();

    expect(File::exists(config_path('noerd-modal.php')))->toBeFalse()
        ->and(File::isDirectory(public_path('vendor/noerd-modal')))->toBeFalse();
});

it('keeps the merged config resolvable without a published file', function (): void {
    ($this->bootProvider)();

    expect(config('noerd-modal.position'))->toBe('center');
});

it('registers the config under the noerd-modal-config publish tag', function (): void {
    ($this->bootProvider)();

    $paths = ServiceProvider::pathsToPublish(NoerdModalServiceProvider::class, 'noerd-modal-config');

    expect(array_map('realpath', array_keys($paths)))->toBe([$this->moduleDir . '/config/noerd-modal.php'])
        ->and(array_values($paths))->toBe([config_path('noerd-modal.php')]);
});

it('publishes the config through vendor:publish', function (): void {
    ($this->bootProvider)();

    $this->artisan('vendor:publish', ['--tag' => 'noerd-modal-config'])->assertSuccessful();

    expect(File::get(config_path('noerd-modal.php')))
        ->toBe(File::get($this->moduleDir . '/config/noerd-modal.php'));
});

it('never overwrites an edited host config without --force', function (): void {
    ($this->bootProvider)();
    File::ensureDirectoryExists(config_path());
    File::put(config_path('noerd-modal.php'), "<?php\n\nreturn ['position' => 'right'];\n");

    $this->artisan('vendor:publish', ['--tag' => 'noerd-modal-config'])->assertSuccessful();

    expect(File::get(config_path('noerd-modal.php')))->toContain("'position' => 'right'");

    $this->artisan('vendor:publish', ['--tag' => 'noerd-modal-config', '--force' => true])->assertSuccessful();

    expect(File::get(config_path('noerd-modal.php')))
        ->toBe(File::get($this->moduleDir . '/config/noerd-modal.php'));
});
