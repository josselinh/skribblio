<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Requests allowed per minute on the public endpoints.
     */
    private const int PUBLIC_API_REQUESTS_PER_MINUTE = 120;

    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureRateLimiting();
    }

    /**
     * The public endpoints (search, public groups and collections, export) are open to guests and crawlers:
     * they are limited per user, or per IP address for guests.
     */
    private function configureRateLimiting(): void
    {
        RateLimiter::for('public-api', fn (Request $request): Limit => Limit::perMinute(self::PUBLIC_API_REQUESTS_PER_MINUTE)
            ->by($request->user()?->getKey() ?? $request->ip()));
    }
}
