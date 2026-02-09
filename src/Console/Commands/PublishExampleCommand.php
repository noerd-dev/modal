<?php

namespace NoerdModal\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class PublishExampleCommand extends Command
{
    protected $signature = 'noerd-modal:publish-example {--force : Overwrite existing files}';

    protected $description = 'Publish the noerd-modal example components and route';

    public function handle(): int
    {
        $this->publishComponents();
        $this->addRoute();

        return self::SUCCESS;
    }

    private function publishComponents(): void
    {
        $sourceDir = __DIR__ . '/../../../resources/views/components/example';
        $targetDir = resource_path('views/components/example');

        $files = [
            'noerd-example-component.blade.php',
            'noerd-example-page.blade.php',
        ];

        File::ensureDirectoryExists($targetDir);

        foreach ($files as $file) {
            $source = "{$sourceDir}/{$file}";
            $target = "{$targetDir}/{$file}";

            if (File::exists($target) && ! $this->option('force')) {
                $this->warn("File already exists: resources/views/components/example/{$file}");
                $this->line('Use --force to overwrite.');

                continue;
            }

            File::copy($source, $target);
            $this->info("Published: resources/views/components/example/{$file}");
        }
    }

    private function addRoute(): void
    {
        $routeFile = base_path('routes/web.php');
        $content = File::get($routeFile);

        if (str_contains($content, 'noerd-example-modal')) {
            $this->line('Route already exists in routes/web.php');

            return;
        }

        $route = "\n// Noerd Modal Example\nRoute::livewire('noerd-example-modal', 'example.noerd-example-page')->name('noerd-modal-example');\n";

        File::append($routeFile, $route);
        $this->info('Route added to routes/web.php');
    }
}
