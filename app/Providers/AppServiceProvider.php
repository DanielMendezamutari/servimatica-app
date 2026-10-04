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
        $this->app->bind(\App\Domain\Brand\BrandRepositoryInterface::class, \App\Infrastructure\Persistence\Eloquent\EloquentBrandRepository::class);
        $this->app->bind(\App\Domain\ProductModel\ProductModelRepositoryInterface::class, \App\Infrastructure\Persistence\Eloquent\EloquentProductModelRepository::class);
        $this->app->bind(\App\Domain\Audit\LoginLogRepositoryInterface::class, \App\Infrastructure\Persistence\Eloquent\EloquentLoginLogRepository::class);
        $this->app->bind(\App\Domain\Client\ClientRepositoryInterface::class, \App\Infrastructure\Persistence\Eloquent\EloquentClientRepository::class);
        $this->app->bind(\App\Domain\CashShift\CashShiftRepositoryInterface::class, \App\Infrastructure\Persistence\Eloquent\EloquentCashShiftRepository::class);
        $this->app->bind(\App\Domain\Quote\QuoteRepositoryInterface::class, \App\Infrastructure\Persistence\Eloquent\EloquentQuoteRepository::class);
        $this->app->bind(\App\Domain\Sale\SaleRepositoryInterface::class, \App\Infrastructure\Persistence\Eloquent\EloquentSaleRepository::class);
        $this->app->bind(\App\Domain\Supplier\SupplierRepositoryInterface::class, \App\Infrastructure\Persistence\Eloquent\EloquentSupplierRepository::class);
        $this->app->bind(\App\Domain\Purchase\PurchaseRepositoryInterface::class, \App\Infrastructure\Persistence\Eloquent\EloquentPurchaseRepository::class);
        $this->app->bind(\App\Domain\PaymentMethod\PaymentMethodRepositoryInterface::class, \App\Infrastructure\Persistence\Eloquent\EloquentPaymentMethodRepository::class);
        $this->app->bind(\App\Domain\Company\CompanySettingRepositoryInterface::class, \App\Infrastructure\Persistence\Eloquent\EloquentCompanySettingRepository::class);
        $this->app->bind(\App\Domain\Sale\SaleReturnRepositoryInterface::class, \App\Infrastructure\Persistence\Eloquent\EloquentSaleReturnRepository::class);
        $this->app->bind(\App\Domain\Kardex\KardexRepositoryInterface::class, \App\Infrastructure\Persistence\Eloquent\EloquentKardexRepository::class);
        $this->app->bind(\App\Domain\WhatsApp\WhatsAppGatewayInterface::class, \App\Infrastructure\Gateways\WhatsApp\QrGatewayClient::class);
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
