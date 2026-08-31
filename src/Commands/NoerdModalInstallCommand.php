<?php

namespace NoerdModal\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Noerd\Traits\HasModuleInstallation;
use Noerd\Traits\RequiresNoerdInstallation;

/**
 * Noerd Modal ships no app-configs/ folder — it is a support module, not a tenant app.
 * Installing it therefore means publishing config/noerd-modal.php and running the
 * module migrations; no tenant app is registered.
 */
class NoerdModalInstallCommand extends Command
{
    use HasModuleInstallation;
    use RequiresNoerdInstallation;

    protected $signature = 'noerd:install-modal {--force : Overwrite existing files without asking}';

    protected $description = 'Install the Noerd Modal module configuration';

    public function handle(): int
    {
        if (! $this->ensureNoerdInstalled()) {
            return self::FAILURE;
        }

        $this->info('Installing Noerd Modal...');
        $this->line('');

        $this->publishConfig();

        $this->line('');
        $this->info('Noerd Modal successfully installed!');

        return self::SUCCESS;
    }

    protected function publishConfig(): void
    {
        $target = config_path('noerd-modal.php');
        $source = dirname(__DIR__, 2) . '/config/noerd-modal.php';

        if (File::exists($target) && ! $this->option('force')) {
            if (! $this->confirm('Config file config/noerd-modal.php already exists. Overwrite?', false)) {
                $this->line('<comment>Skipped publishing config file.</comment>');

                return;
            }
        }

        File::copy($source, $target);
        $this->line('<info>Published config file:</info> config/noerd-modal.php');
    }

    protected function getModuleName(): string
    {
        return 'Noerd Modal';
    }

    protected function getModuleKey(): string
    {
        return 'modal';
    }

    protected function getDefaultAppTitle(): string
    {
        return 'Noerd Modal';
    }

    protected function getAppIcon(): string
    {
        return '';
    }

    protected function getAppRoute(): string
    {
        return '';
    }

    protected function getSourceDir(): string
    {
        return dirname(__DIR__, 2);
    }
}
