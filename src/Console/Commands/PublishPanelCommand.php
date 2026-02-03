<?php

namespace NoerdModal\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class PublishPanelCommand extends Command
{
    protected $signature = 'noerd-modal:publish-panel {--force : Overwrite existing file}';

    protected $description = 'Publish the modal panel view for customization';

    public function handle(): int
    {
        $source = __DIR__ . '/../../../resources/views/components/modal/panel.blade.php';
        $target = resource_path('views/vendor/noerd/components/modal/panel.blade.php');

        if (File::exists($target) && ! $this->option('force')) {
            $this->error('Panel view already exists. Use --force to overwrite.');

            return self::FAILURE;
        }

        File::ensureDirectoryExists(dirname($target));
        File::copy($source, $target);

        $this->info('Panel view published to: resources/views/vendor/noerd/components/modal/panel.blade.php');

        return self::SUCCESS;
    }
}
