<?php

declare(strict_types=1);

uses(Tests\TestCase::class);

it('declares every module dependency it uses', function (): void {
    // Allowed: noerd/noerd REQUIRES noerd/modal, so the dependency cannot be
    // declared in this direction without a cycle. The blade references a
    // noerd:: component that exists in every host that loads this panel.
    assertModuleDependenciesDeclared(dirname(__DIR__, 2), ['noerd/noerd']);
});
