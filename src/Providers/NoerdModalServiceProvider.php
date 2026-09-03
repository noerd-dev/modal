<?php

namespace NoerdModal\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;
use NoerdModal\Console\Commands\PublishExampleCommand;
use NoerdModal\Console\Commands\PublishPanelCommand;
use NoerdModal\Http\Controllers\AssetController;

class NoerdModalServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../../config/noerd-modal.php', 'noerd-modal');

        $this->loadViewsFrom(__DIR__ . '/../../resources/views', 'noerd');
        $this->loadJsonTranslationsFrom(__DIR__ . '/../../resources/lang');
        Livewire::addNamespace('noerd-modal', viewPath: __DIR__ . '/../../resources/views/components');
        Livewire::addLocation(viewPath: __DIR__ . '/../../resources/views/components');

        $this->registerAssetRoute();

        if ($this->app->runningInConsole()) {
            // The only thing a host may publish is the config — and only on
            // request. The bundle is served from the package directory by the
            // asset route, so nothing is ever copied into public/ and no
            // noerd:install-modal / noerd:update-modal command exists.
            $this->publishes([
                __DIR__ . '/../../config/noerd-modal.php' => config_path('noerd-modal.php'),
            ], 'noerd-modal-config');

            $this->commands([
                PublishExampleCommand::class,
                PublishPanelCommand::class,
            ]);
        }
    }

    /**
     * Serves the prebuilt bundle from dist/build — like Livewire's livewire.js
     * route. No middleware: a static asset needs neither a session nor CSRF.
     */
    private function registerAssetRoute(): void
    {
        Route::get('/noerd-modal/{file}', AssetController::class)
            ->where('file', '[A-Za-z0-9._-]+')
            ->name('noerd-modal.asset');
    }
}
