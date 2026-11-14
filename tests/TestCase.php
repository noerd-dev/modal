<?php

declare(strict_types=1);

namespace NoerdModal\Tests;

use Closure;
use Livewire\ComponentHookRegistry;
use Livewire\Livewire;
use Livewire\LivewireServiceProvider;
use NoerdModal\Providers\NoerdModalServiceProvider;
use NoerdModal\Tests\Fixtures\ZzFullscreenModalTarget;
use NoerdModal\Tests\Fixtures\ZzModalTarget;
use NoerdModal\Tests\Fixtures\ZzRefusedModalTarget;
use Orchestra\Testbench\TestCase as BaseTestCase;

/**
 * The package's own Testbench application: Livewire and this package's
 * provider — nothing of noerd/noerd. The suite therefore proves that the modal
 * stack works in a plain Livewire host.
 *
 * It runs standalone (`composer install && vendor/bin/pest` in the package) and
 * from a host application root (`php artisan test app-modules/noerd-modal/tests`),
 * where every test file binds this class itself through uses().
 */
abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        self::flushLivewireComponentHooks();

        parent::setUp();

        Livewire::component('zz-modal-target', ZzModalTarget::class);
        Livewire::component('zz-modal-fullscreen-target', ZzFullscreenModalTarget::class);
        Livewire::component('zz-modal-refused-target', ZzRefusedModalTarget::class);
    }

    protected function getPackageProviders($app): array
    {
        return [
            LivewireServiceProvider::class,
            NoerdModalServiceProvider::class,
        ];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('app.key', 'base64:' . base64_encode(str_repeat('a', 32)));
        $app['config']->set('session.driver', 'array');
        $app['config']->set('app.locale', 'en');
    }

    /**
     * Livewire keeps the registered component hooks in a process-global static
     * list. In a host run, the suites before this one registered the hooks of
     * every host package (noerd's access guard among them) — this app boots
     * none of them, so only the hooks of the providers above may be attached.
     */
    private static function flushLivewireComponentHooks(): void
    {
        Closure::bind(static function (): void {
            ComponentHookRegistry::$componentHooks = [];
        }, null, ComponentHookRegistry::class)();
    }
}
