<?php

namespace App\Providers;

use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        // Admin-only
        Gate::define('manage-users', fn (User $user) => $user->isAdmin());

        // Admin + Operator
        Gate::define('manage-devices',    fn (User $user) => $user->hasRole(['admin', 'operator']));
        Gate::define('manage-batches',    fn (User $user) => $user->hasRole(['admin', 'operator']));
        Gate::define('manage-knowledge',  fn (User $user) => $user->hasRole(['admin', 'operator']));
        Gate::define('ai-override',       fn (User $user) => $user->hasRole(['admin', 'operator']));

        // All authenticated (read-only actions gated here for Blade @can)
        Gate::define('view-dashboard',    fn (User $user) => $user->hasRole(['admin', 'operator', 'viewer']));
        Gate::define('view-sensor',       fn (User $user) => $user->hasRole(['admin', 'operator', 'viewer']));
        Gate::define('view-logs',         fn (User $user) => $user->hasRole(['admin', 'operator', 'viewer']));
    }
}
