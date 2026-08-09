<?php

declare(strict_types=1);

namespace Workbench\App\Providers;

use Illuminate\Support\ServiceProvider;

use function Orchestra\Testbench\package_path;

class WorkbenchServiceProvider extends ServiceProvider
{
    #[\Override]
    public function register(): void
    {
        config()->set('webhooks.scan_paths', [package_path('workbench/app/Events')]);
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(package_path('database/migrations'));
    }
}
