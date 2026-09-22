<?php

namespace App\Providers;

use App\Interfaces\PaymentGatewayInterface;
use App\Services\AlRajhiService;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->register(RouteServiceProvider::class);

        // Al Rajhi Bank is the single and exclusive payment gateway.
        $this->app->bind(PaymentGatewayInterface::class, AlRajhiService::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
