<?php

declare(strict_types=1);

use Illuminate\Support\Facades\File;

uses(Tests\TestCase::class);

describe('PublishPanelCommand', function (): void {
    beforeEach(function (): void {
        $this->targetPath = resource_path('views/vendor/noerd/components/modal/panel.blade.php');
        $this->targetDir = dirname($this->targetPath);

        if (File::exists($this->targetPath)) {
            File::delete($this->targetPath);
        }
        if (File::isDirectory($this->targetDir) && count(File::files($this->targetDir)) === 0) {
            File::deleteDirectory($this->targetDir);
        }
    });

    afterEach(function (): void {
        if (File::exists($this->targetPath)) {
            File::delete($this->targetPath);
        }
        if (File::isDirectory($this->targetDir) && count(File::files($this->targetDir)) === 0) {
            File::deleteDirectory($this->targetDir);
        }
    });

    it('publishes panel view to vendor directory', function (): void {
        $this->artisan('noerd-modal:publish-panel')
            ->assertSuccessful()
            ->expectsOutput('Panel view published to: resources/views/vendor/noerd/components/modal/panel.blade.php');

        expect(File::exists($this->targetPath))->toBeTrue();
    });

    it('creates directory structure if it does not exist', function (): void {
        expect(File::isDirectory($this->targetDir))->toBeFalse();

        $this->artisan('noerd-modal:publish-panel')
            ->assertSuccessful();

        expect(File::isDirectory($this->targetDir))->toBeTrue();
    });

    it('fails when file already exists without force flag', function (): void {
        File::ensureDirectoryExists($this->targetDir);
        File::put($this->targetPath, 'existing content');

        $this->artisan('noerd-modal:publish-panel')
            ->assertFailed()
            ->expectsOutput('Panel view already exists. Use --force to overwrite.');

        expect(File::get($this->targetPath))->toBe('existing content');
    });

    it('overwrites existing file with force flag', function (): void {
        File::ensureDirectoryExists($this->targetDir);
        File::put($this->targetPath, 'existing content');

        $this->artisan('noerd-modal:publish-panel', ['--force' => true])
            ->assertSuccessful();

        expect(File::get($this->targetPath))->not->toBe('existing content');
    });

    it('copies the correct source file', function (): void {
        $this->artisan('noerd-modal:publish-panel')
            ->assertSuccessful();

        $sourcePath = dirname(__DIR__, 2) . '/resources/views/components/modal/panel.blade.php';
        expect(File::get($this->targetPath))->toBe(File::get($sourcePath));
    });
});

describe('PublishExampleCommand', function (): void {
    beforeEach(function (): void {
        $this->targetDir = resource_path('views/components/example');
        $this->componentFile = $this->targetDir . '/noerd-example-component.blade.php';
        $this->pageFile = $this->targetDir . '/noerd-example-page.blade.php';
        $this->routeFile = base_path('routes/web.php');

        // Snapshot the UNTOUCHED file first — afterEach restores exactly this
        // content, so a route a developer added by hand is never lost. The
        // stripped copy below is only the clean baseline the tests run against.
        $this->originalRouteContent = File::get($this->routeFile);

        $content = $this->originalRouteContent;
        $content = preg_replace('/\n*\/\/ Noerd Modal Example\nRoute::livewire\(\'noerd-example-modal\'.*?\n/s', '', $content);
        $content = preg_replace('/\nRoute::livewire\(\'noerd-example-modal\'[^\n]*\n?/', '', $content);
        File::put($this->routeFile, $content);

        foreach ([$this->componentFile, $this->pageFile] as $file) {
            if (File::exists($file)) {
                File::delete($file);
            }
        }
    });

    afterEach(function (): void {
        foreach ([$this->componentFile, $this->pageFile] as $file) {
            if (File::exists($file)) {
                File::delete($file);
            }
        }

        File::put($this->routeFile, $this->originalRouteContent);
    });

    it('publishes example component files', function (): void {
        $this->artisan('noerd-modal:publish-example')
            ->assertSuccessful();

        expect(File::exists($this->componentFile))->toBeTrue();
        expect(File::exists($this->pageFile))->toBeTrue();
    });

    it('creates target directory if it does not exist', function (): void {
        if (File::isDirectory($this->targetDir)) {
            File::deleteDirectory($this->targetDir);
        }

        $this->artisan('noerd-modal:publish-example')
            ->assertSuccessful();

        expect(File::isDirectory($this->targetDir))->toBeTrue();
    });

    it('warns when files already exist without force flag', function (): void {
        File::ensureDirectoryExists($this->targetDir);
        File::put($this->componentFile, 'existing component');
        File::put($this->pageFile, 'existing page');

        $this->artisan('noerd-modal:publish-example')
            ->assertSuccessful()
            ->expectsOutput('File already exists: resources/views/components/example/noerd-example-component.blade.php')
            ->expectsOutput('File already exists: resources/views/components/example/noerd-example-page.blade.php');

        expect(File::get($this->componentFile))->toBe('existing component');
        expect(File::get($this->pageFile))->toBe('existing page');
    });

    it('overwrites existing files with force flag', function (): void {
        File::ensureDirectoryExists($this->targetDir);
        File::put($this->componentFile, 'existing component');
        File::put($this->pageFile, 'existing page');

        $this->artisan('noerd-modal:publish-example', ['--force' => true])
            ->assertSuccessful();

        expect(File::get($this->componentFile))->not->toBe('existing component');
        expect(File::get($this->pageFile))->not->toBe('existing page');
    });

    it('adds route to web.php', function (): void {
        // The stripped baseline written in beforeEach — not the raw snapshot,
        // which may legitimately carry a developer's own example route.
        expect(File::get($this->routeFile))->not->toContain('noerd-example-modal');

        $this->artisan('noerd-modal:publish-example')
            ->assertSuccessful()
            ->expectsOutput('Route added to routes/web.php');

        $newContent = File::get($this->routeFile);
        expect($newContent)->toContain('noerd-example-modal');
        expect($newContent)->toContain('noerd-modal-example');
    });

    it('does not duplicate route if already exists', function (): void {
        $routeContent = File::get($this->routeFile) . "\nRoute::livewire('noerd-example-modal', 'test');";
        File::put($this->routeFile, $routeContent);

        $this->artisan('noerd-modal:publish-example')
            ->assertSuccessful();

        $newContent = File::get($this->routeFile);
        expect(mb_substr_count($newContent, 'noerd-example-modal'))->toBe(1);
    });

    it('copies correct source files', function (): void {
        $this->artisan('noerd-modal:publish-example')
            ->assertSuccessful();

        $sourceDir = dirname(__DIR__, 2) . '/resources/views/components/example';

        expect(File::get($this->componentFile))
            ->toBe(File::get($sourceDir . '/noerd-example-component.blade.php'));
        expect(File::get($this->pageFile))
            ->toBe(File::get($sourceDir . '/noerd-example-page.blade.php'));
    });
});
