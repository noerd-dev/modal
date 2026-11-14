<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\File;

uses(NoerdModal\Tests\TestCase::class);

/**
 * Laravel Boost renders a package's guideline through Blade and silently DROPS
 * a guideline that fails to render. Only the render mechanics are asserted —
 * the rule texts themselves are content.
 */
it('renders the boost guideline through blade', function (): void {
    $source = File::get(dirname(__DIR__, 2) . '/resources/boost/guidelines/core.blade.php');

    $rendered = Blade::render($source);

    expect(trim($rendered))->not->toBe('')
        ->and($rendered)
        ->not->toContain('@verbatim')
        ->not->toContain('@endverbatim');
});
