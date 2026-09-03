<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\File;
use NoerdModal\Support\AssetManifest;

uses(Tests\TestCase::class);

/*
 | <x-noerd::noerd-modal-assets/> points at the package route in production
 | and at the Vite dev server while a hot file exists. The hot file is looked
 | up below the base path, so a throwaway base path keeps the real public/
 | untouched.
 */
beforeEach(function (): void {
    $this->originalBasePath = $this->app->basePath();
    $this->hostPath = storage_path('framework/testing/zz-noerd-modal-assets-' . getmypid());

    File::deleteDirectory($this->hostPath);
    File::ensureDirectoryExists($this->hostPath);

    // Anonymous component resolution asks for the application namespace,
    // which is read from composer.json below the base path — memoise it
    // while the real base path is still active.
    $this->app->getNamespace();

    $this->app->setBasePath($this->hostPath);
});

afterEach(function (): void {
    $this->app->setBasePath($this->originalBasePath);
    File::deleteDirectory($this->hostPath);
});

it('renders the bundled script through the asset route', function (): void {
    $html = Blade::render('<x-noerd::noerd-modal-assets />');

    expect($html)->toContain('<script type="module" src="' . route('noerd-modal.asset', ['file' => AssetManifest::scriptFile()]) . '"></script>')
        ->and($html)->not->toContain('vendor/noerd-modal/');
});

it('renders the dev server while a hot file exists', function (): void {
    File::ensureDirectoryExists($this->hostPath . '/public/vendor/noerd-modal');
    File::put($this->hostPath . '/public/vendor/noerd-modal/hot', 'http://localhost:5173');

    $html = Blade::render('<x-noerd::noerd-modal-assets />');

    expect($html)->toContain('http://localhost:5173/@vite/client')
        ->and($html)->toContain('http://localhost:5173/' . AssetManifest::SCRIPT_ENTRY)
        ->and($html)->not->toContain('noerd-modal.asset');
});
