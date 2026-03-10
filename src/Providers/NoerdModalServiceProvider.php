<?php

namespace NoerdModal\Providers;

use Illuminate\Support\Facades\File;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;
use NoerdModal\Console\Commands\PublishExampleCommand;
use NoerdModal\Console\Commands\PublishPanelCommand;

class NoerdModalServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../../config/noerd-modal.php', 'noerd-modal');

        $this->loadViewsFrom(__DIR__ . '/../../resources/views', 'noerd');
        Livewire::addLocation(viewPath: __DIR__ . '/../../resources/views/components');

        // Publish config
        $this->publishes([
            __DIR__ . '/../../config/noerd-modal.php' => config_path('noerd-modal.php'),
        ], 'noerd-modal-config');

        // Publish built Vite assets
        $this->publishes([
            __DIR__ . '/../../dist/build' => public_path('vendor/noerd-modal'),
        ], 'noerd-modal-assets');

        // Auto-publish config if not exists
        $this->publishConfigIfNotExists();

        // Auto-publish built assets if not exists
        $this->publishBuiltAssetsIfNotExist();

        if ($this->app->runningInConsole()) {
            $this->commands([
                PublishExampleCommand::class,
                PublishPanelCommand::class,
            ]);
        }
    }

    private function publishConfigIfNotExists(): void
    {
        $targetPath = config_path('noerd-modal.php');
        $sourcePath = __DIR__ . '/../../config/noerd-modal.php';

        if (! File::exists($sourcePath) || File::exists($targetPath)) {
            return;
        }

        File::copy($sourcePath, $targetPath);
    }

    private function publishBuiltAssetsIfNotExist(): void
    {
        $targetPath = public_path('vendor/noerd-modal/manifest.json');
        $sourcePath = __DIR__ . '/../../dist/build/manifest.json';

        if (! File::exists($sourcePath)) {
            return;
        }

        $shouldPublish = ! File::exists($targetPath)
            || File::lastModified($sourcePath) > File::lastModified($targetPath);

        if ($shouldPublish) {
            File::ensureDirectoryExists(public_path('vendor/noerd-modal'));
            File::copyDirectory(__DIR__ . '/../../dist/build', public_path('vendor/noerd-modal'));
        }
    }
}
