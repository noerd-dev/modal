<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use NoerdModal\Support\AssetManifest;

uses(Tests\TestCase::class);

/*
 | The bundle is served straight from the package's dist/build — nothing is
 | published into public/. Only files listed in the Vite manifest are reachable.
 */
beforeEach(function (): void {
    $this->scriptFile = AssetManifest::scriptFile();
    $this->scriptPath = AssetManifest::buildDirectory() . '/assets/' . $this->scriptFile;
});

it('serves the bundled script from the package directory', function (): void {
    $response = $this->get('/noerd-modal/' . $this->scriptFile);

    $response->assertOk()
        ->assertHeader('Content-Type', 'application/javascript; charset=utf-8')
        ->assertHeader('Last-Modified', gmdate('D, d M Y H:i:s', (int) filemtime($this->scriptPath)) . ' GMT');

    // Symfony normalises the directive order, so assert the directives themselves.
    expect($response->headers->get('Cache-Control'))
        ->toContain('public')
        ->toContain('max-age=31536000')
        ->toContain('immutable');

    expect($response->baseResponse->getFile()->getRealPath())->toBe(realpath($this->scriptPath));
});

it('answers a matching If-Modified-Since with 304', function (): void {
    $lastModified = gmdate('D, d M Y H:i:s', (int) filemtime($this->scriptPath)) . ' GMT';

    $this->withHeaders(['If-Modified-Since' => $lastModified])
        ->get('/noerd-modal/' . $this->scriptFile)
        ->assertStatus(304);
});

it('serves only files listed in the manifest', function (string $file): void {
    $this->get('/noerd-modal/' . $file)->assertNotFound();
})->with([
    'unknown script' => 'does-not-exist.js',
    'manifest itself' => 'manifest.json',
    'traversal attempt' => '..%2Fmanifest.json',
]);

it('exposes a named route without web middleware', function (): void {
    $route = Route::getRoutes()->getByName('noerd-modal.asset');

    expect($route)->not->toBeNull()
        ->and($route->uri())->toBe('noerd-modal/{file}')
        ->and($route->gatherMiddleware())->toBe([]);

    expect(route('noerd-modal.asset', ['file' => $this->scriptFile], false))
        ->toBe('/noerd-modal/' . $this->scriptFile);
});

it('lists the servable files from the manifest', function (): void {
    $files = AssetManifest::servableFiles();

    expect($files)->toHaveKey($this->scriptFile)
        ->and($files[$this->scriptFile])->toBe($this->scriptPath)
        ->and(AssetManifest::path('missing.js'))->toBeNull();
});
