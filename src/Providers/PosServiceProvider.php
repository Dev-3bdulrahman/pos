<?php

namespace Dev3bdulrahman\Pos\Providers;

use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Dev3bdulrahman\Pos\Events\PosSaleCompleted;
use Dev3bdulrahman\Pos\Models\PosSale;
use Dev3bdulrahman\Pos\Policies\PosPolicy;
use Livewire\Livewire;

class PosServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // Load migrations
        $this->loadMigrationsFrom(__DIR__ . '/../../database/migrations');

        // Load routes
        $this->loadRoutesFrom(__DIR__ . '/../Routes/web.php');
        $this->loadRoutesFrom(__DIR__ . '/../Routes/api.php');

        // Load views
        $this->loadViewsFrom(__DIR__ . '/../Views', 'pos');

        // Load translations
        $this->loadTranslationsFrom(__DIR__ . '/../Translations', 'pos');

        // Register Policies
        Gate::policy(PosSale::class, PosPolicy::class);

        // Register Livewire Components
        if (class_exists(Livewire::class)) {
            Livewire::component('pos-terminals', \Dev3bdulrahman\Pos\Http\Controllers\Web\Admin\Pos\Terminals::class);
            Livewire::component('pos-shifts', \Dev3bdulrahman\Pos\Http\Controllers\Web\Admin\Pos\Shifts::class);
            Livewire::component('pos-register', \Dev3bdulrahman\Pos\Http\Controllers\Web\Admin\Pos\Register::class);
        }
    }
}
