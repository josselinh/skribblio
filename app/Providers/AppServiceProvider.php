<?php

namespace App\Providers;

use App\Models\Group;
use App\Models\User;
use App\Models\Vote;
use App\Observers\VoteObserver;
use App\Policies\GroupPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot(): void
    {
        Gate::policy(Group::class, GroupPolicy::class);

        Vote::observe(VoteObserver::class);

        Gate::define('sentence-import', function (User $user): bool {
            return $user->isAdmin();
        });
    }
}
