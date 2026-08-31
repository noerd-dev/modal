<?php

namespace NoerdModal\Commands;

/**
 * The module ships no app-configs/ folder, so the generic runModuleUpdate() has
 * nothing to copy — republishing config/noerd-modal.php is the whole update.
 * Like every other update command it runs no migrations.
 */
class NoerdModalUpdateCommand extends NoerdModalInstallCommand
{
    protected $signature = 'noerd:update-modal {--force : Overwrite existing files without asking}';

    protected $description = 'Update the Noerd Modal module configuration';

    public function handle(): int
    {
        if (! $this->ensureNoerdInstalled()) {
            return self::FAILURE;
        }

        $this->info('Updating Noerd Modal configuration...');
        $this->line('');

        $this->publishConfig();

        $this->line('');
        $this->info('Noerd Modal configuration updated!');

        return self::SUCCESS;
    }
}
