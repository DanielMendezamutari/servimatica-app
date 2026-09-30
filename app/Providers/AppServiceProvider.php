<?php

namespace App\Providers;

use App\Domain\User\UserRepositoryInterface;
use App\Infrastructure\Persistence\Eloquent\EloquentUserRepository;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

final class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(UserRepositoryInterface::class, EloquentUserRepository::class);
        $this->app->bind(\App\Domain\Category\CategoryRepositoryInterface::class, \App\Infrastructure\Persistence\Eloquent\EloquentCategoryRepository::class);
        $this->app->bind(\App\Domain\Product\ProductRepositoryInterface::class, \App\Infrastructure\Persistence\Eloquent\EloquentProductRepository::class);
        $this->app->bind(\App\Domain\StockMovement\StockMovementRepositoryInterface::class, \App\Infrastructure\Persistence\Eloquent\EloquentStockMovementRepository::class);
    }
    public function boot(): void
    {
        RateLimiter::for('login', function (Request $request) {
            $response = fn (Request $request, array $headers) => response()->json(
                ['message' => 'Demasiados intentos. Espere un minuto y vuelva a intentar.'],
                429,
                $headers
            );
            return [Limit::perMinute(10)->by('login:'.strtolower((string) $request->input('login')).'|'.$request->ip())->response($response),
                Limit::perMinute(60)->by('ip:'.$request->ip())->response($response)];
        });
    }
}
