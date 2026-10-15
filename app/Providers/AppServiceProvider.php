<?php

declare(strict_types=1);

namespace App\Providers;

use App\Models\PersonalAccessToken;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;

class AppServiceProvider extends ServiceProvider
{
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
        Sanctum::usePersonalAccessTokenModel(PersonalAccessToken::class);

        $this->configureRateLimiting();
    }

    private function configureRateLimiting(): void
    {
        $emailAndIp = static fn (Request $request): string => Str::lower((string) $request->input('email')).'|'.$request->ip();

        RateLimiter::for('login', fn (Request $request) => Limit::perMinute(5)->by($emailAndIp($request)));
        RateLimiter::for('auth-link', fn (Request $request) => Limit::perMinute(3)->by($emailAndIp($request)));
        RateLimiter::for('auth-link-verify', fn (Request $request) => Limit::perMinute(10)->by((string) $request->ip()));
    }
}
