<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Artisan;

uses(Tests\TestCase::class);

it('registers the install and update commands', function (): void {
    $commands = Artisan::all();

    expect($commands)->toHaveKey('noerd:install-modal');
    expect($commands)->toHaveKey('noerd:update-modal');
});
