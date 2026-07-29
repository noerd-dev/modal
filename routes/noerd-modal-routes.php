<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Livewire\Mechanisms\HandleRouting\LivewirePageController;

Route::middleware(['web', 'auth'])->group(function (): void {
    Route::get('/modal-page', function (Request $request) {
        $component = $request->query('component');
        abort_unless($component, 404);

        $route = $request->route();
        $route->action['livewire_component'] = $component;

        if ($modelId = $request->query('modelId')) {
            $route->setParameter('modelId', $modelId);
        }

        return app(LivewirePageController::class)->__invoke();
    })->name('modal.page');
});
