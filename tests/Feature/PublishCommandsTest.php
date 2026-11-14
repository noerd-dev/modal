<?php

declare(strict_types=1);

use Illuminate\Support\Facades\File;

uses(NoerdModal\Tests\TestCase::class);

/*
 | Both publish commands write into the HOST tree (resources/views/…,
 | routes/web.php). They are therefore exercised against a throwaway base
 | path, so a published panel override or a hand-written route in the real
 | project is never touched by a test run.
 */
beforeEach(function (): void {
    $this->originalBasePath = $this->app->basePath();
    $this->hostPath = storage_path('framework/testing/zz-noerd-modal-publish-' . getmypid());

    File::deleteDirectory($this->hostPath);
    File::ensureDirectoryExists($this->hostPath . '/routes');
    File::put($this->hostPath . '/routes/web.php', "<?php\n");

    $this->app->setBasePath($this->hostPath);
});

afterEach(function (): void {
    $this->app->setBasePath($this->originalBasePath);
    File::deleteDirectory($this->hostPath);
});

describe('PublishPanelCommand', function (): void {
    beforeEach(function (): void {
        $this->targetPath = resource_path('views/vendor/noerd/components/modal/panel.blade.php');
        $this->targetDir = dirname($this->targetPath);
    });

    it('publishes panel view to vendor directory', function (): void {
        $this->artisan('noerd-modal:publish-panel')
            ->assertSuccessful()
            ->expectsOutput('Panel view published to: resources/views/vendor/noerd/components/modal/panel.blade.php');

        expect(File::exists($this->targetPath))->toBeTrue();
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
        $this->fullscreenFile = $this->targetDir . '/noerd-example-fullscreen-component.blade.php';
        $this->routeFile = base_path('routes/web.php');
    });

    it('publishes example component files', function (): void {
        $this->artisan('noerd-modal:publish-example')
            ->assertSuccessful();

        expect(File::exists($this->componentFile))->toBeTrue();
        expect(File::exists($this->pageFile))->toBeTrue();
        expect(File::exists($this->fullscreenFile))->toBeTrue();
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
        expect(File::get($this->routeFile))->not->toContain('noerd-example-modal');

        $this->artisan('noerd-modal:publish-example')
            ->assertSuccessful()
            ->expectsOutput('Route added to routes/web.php');

        $newContent = File::get($this->routeFile);
        expect($newContent)->toContain('noerd-example-modal');
        expect($newContent)->toContain('noerd-modal-example');
        // The route points at the PUBLISHED page, never at a package component.
        expect($newContent)->toContain("'example.noerd-example-page'");
        expect($newContent)->not->toContain('noerd-modal::');
    });

    it('publishes examples that only reference each other', function (): void {
        $this->artisan('noerd-modal:publish-example')->assertSuccessful();

        $sources = File::get($this->pageFile) . File::get($this->componentFile);

        preg_match_all('/\$modal\(\'([^\']+)\'/', $sources, $matches);

        expect($matches[1])->not->toBeEmpty();

        // The published page is what the appended route names: Livewire's
        // default component location of the application resolves it.
        \Livewire\Livewire::addLocation(viewPath: resource_path('views/components'));

        expect(\Livewire\Livewire::new(NoerdModal\Console\Commands\PublishExampleCommand::PAGE_COMPONENT))
            ->toBeInstanceOf(\Livewire\Component::class);

        foreach (array_unique($matches[1]) as $component) {
            $file = $this->targetDir . '/' . str_replace('example.', '', $component) . '.blade.php';

            expect(str_starts_with($component, 'example.'))->toBeTrue()
                ->and(File::exists($file))->toBeTrue();
        }
    });

    it('ships the examples outside every mountable component location', function (): void {
        foreach (['example.noerd-example-page', 'noerd-modal::example.noerd-example-page', 'noerd-modal::examples.noerd-example-page'] as $name) {
            expect(fn() => \Livewire\Livewire::new($name))
                ->toThrow(\Livewire\Exceptions\ComponentNotFoundException::class);
        }
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

        $sourceDir = dirname(__DIR__, 2) . '/resources/views/examples';

        expect(File::get($this->componentFile))
            ->toBe(File::get($sourceDir . '/noerd-example-component.blade.php'));
        expect(File::get($this->pageFile))
            ->toBe(File::get($sourceDir . '/noerd-example-page.blade.php'));
        expect(File::get($this->fullscreenFile))
            ->toBe(File::get($sourceDir . '/noerd-example-fullscreen-component.blade.php'));
    });
});
