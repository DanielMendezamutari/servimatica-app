<?php

use App\Infrastructure\Http\Controllers\Api\{
    AuditController,
    AuthController,
    BrandController,
    CashShiftController,
    CategoryController,
    ClientController,
    CompanySettingController,
    PaymentMethodController,
    ProductController,
    ProductModelController,
    PurchaseController,
    QuoteController,
    PublicQuoteController,
    PublicCatalogController,
    SaleController,
    SaleReturnController,
    SupplierController,
    UserController,
    KardexController,
    ReportController,
    DashboardController,
    WhatsAppWebhookController,
    WhatsAppBotController
};
use Illuminate\Support\Facades\Route;

Route::post('/auth/login', [AuthController::class, 'login'])->middleware('throttle:login');

// Vitrina Pública E-Commerce / TikTok Live (sin autenticación)
Route::get('/public/catalog', [PublicCatalogController::class, 'index']);

// Rutas públicas de cotizaciones protegidas por token criptográfico (UUID no secuencial)
Route::get('/quotes/public/{token}', [PublicQuoteController::class, 'show']);
Route::get('/quotes/public/{token}/print', [PublicQuoteController::class, 'print']);
Route::get('/quotes/public/{token}/pdf', [PublicQuoteController::class, 'print']);

// Rutas públicas de comprobantes e impresiones (Accesibles por window.open)
Route::get('/sales/{id}/receipt', [SaleController::class, 'receipt'])->whereNumber('id');
Route::get('/sale-returns/{id}/receipt', [SaleReturnController::class, 'receipt'])->whereNumber('id');
Route::get('/purchases/{id}/receipt', [PurchaseController::class, 'receipt'])->whereNumber('id');
Route::get('/cash-shifts/{id}/receipt', [CashShiftController::class, 'receipt'])->whereNumber('id');
Route::get('/company-settings/public', [CompanySettingController::class, 'publicInfo']);

// Webhook para recepción de mensajes WhatsApp (Gateway QR / Baileys / Evolution API)
Route::get('/webhooks/whatsapp', [WhatsAppWebhookController::class, 'verify']);
Route::post('/webhooks/whatsapp', [WhatsAppWebhookController::class, 'handle']);

Route::middleware('jwt.auth')->group(function () {
    Route::get('/auth/me', [AuthController::class, 'me']);
    Route::post('/auth/logout', [AuthController::class, 'logout']);

    // Impresión interna de cotizaciones (requiere sesión activa)
    Route::get('/quotes/{id}/print', [QuoteController::class, 'printQuote'])->whereNumber('id');
    Route::get('/quotes/{id}/pdf', [QuoteController::class, 'printQuote'])->whereNumber('id');

    // Dashboard Ejecutivo y Operativo
    Route::get('/dashboard/summary', [DashboardController::class, 'summary']);
    Route::get('/v1/dashboard/summary', [DashboardController::class, 'summary']);

    // Catálogo visible para Vendedor y Administrador
    Route::get('/products/export-excel', [ProductController::class, 'exportExcel']);
    Route::get('/products', [ProductController::class, 'index']);
    Route::get('/categories', [CategoryController::class, 'index']);
    Route::get('/categories/options', [CategoryController::class, 'options']);
    Route::get('/categories/{id}/subfamilies', [CategoryController::class, 'subfamilies'])->whereNumber('id');
    Route::get('/brands', [BrandController::class, 'index']);
    Route::get('/brands/options', [BrandController::class, 'options']);
    Route::get('/brands/{id}/models', [BrandController::class, 'models'])->whereNumber('id');

    // Control de Caja Chica y Turnos
    Route::get('/cash-shifts/current', [CashShiftController::class, 'current']);
    Route::post('/cash-shifts/open', [CashShiftController::class, 'open']);
    Route::post('/cash-shifts/close', [CashShiftController::class, 'close']);
    Route::get('/cash-shifts', [CashShiftController::class, 'index']);

    // Gestión Integral de Clientes (CRM)
    Route::get('/clients', [ClientController::class, 'index']);
    Route::post('/clients', [ClientController::class, 'store']);
    Route::get('/clients/export', [ClientController::class, 'export']);
    Route::get('/clients/{id}', [ClientController::class, 'show'])->whereNumber('id');
    Route::put('/clients/{id}', [ClientController::class, 'update'])->whereNumber('id');
    Route::patch('/clients/{id}/toggle-status', [ClientController::class, 'toggleStatus'])->whereNumber('id');
    Route::get('/clients/{id}/sales', [ClientController::class, 'sales'])->whereNumber('id');
    Route::get('/clients/{id}/quotes', [ClientController::class, 'quotes'])->whereNumber('id');
    Route::get('/clients/{id}/warranties', [ClientController::class, 'warranties'])->whereNumber('id');

    // Rutas con alias /v1
    Route::get('/v1/clients', [ClientController::class, 'index']);
    Route::post('/v1/clients', [ClientController::class, 'store']);
    Route::get('/v1/clients/export', [ClientController::class, 'export']);
    Route::get('/v1/clients/{id}', [ClientController::class, 'show'])->whereNumber('id');
    Route::put('/v1/clients/{id}', [ClientController::class, 'update'])->whereNumber('id');
    Route::patch('/v1/clients/{id}/toggle-status', [ClientController::class, 'toggleStatus'])->whereNumber('id');
    Route::get('/v1/clients/{id}/sales', [ClientController::class, 'sales'])->whereNumber('id');
    Route::get('/v1/clients/{id}/quotes', [ClientController::class, 'quotes'])->whereNumber('id');
    Route::get('/v1/clients/{id}/warranties', [ClientController::class, 'warranties'])->whereNumber('id');

    // Proformas / Cotizaciones
    Route::get('/quotes', [QuoteController::class, 'index']);
    Route::post('/quotes', [QuoteController::class, 'store']);
    Route::get('/quotes/{id}', [QuoteController::class, 'show'])->whereNumber('id');
    Route::get('/quotes/{id}/whatsapp-link', [QuoteController::class, 'whatsappLink'])->whereNumber('id');

    // Ventas en Tienda (Punto de Venta POS)
    Route::get('/sales', [SaleController::class, 'index']);
    Route::post('/sales', [SaleController::class, 'store']);
    Route::post('/sales/from-quote', [SaleController::class, 'fromQuote']);
    Route::get('/sales/{id}', [SaleController::class, 'show'])->whereNumber('id');
    Route::get('/sales/{id}/warranty-check', [SaleReturnController::class, 'warrantyCheck'])->whereNumber('id');
    Route::post('/sales/{id}/returns', [SaleReturnController::class, 'store'])->whereNumber('id');

    // Devoluciones y Garantías
    Route::get('/sale-returns', [SaleReturnController::class, 'index']);
    Route::get('/sale-returns/{id}', [SaleReturnController::class, 'show'])->whereNumber('id');

    // Formas de Pago (Opciones para ventas/compras)
    Route::get('/payment-methods/options', [PaymentMethodController::class, 'options']);

    // Kardex de Inventario (Físico y Valorizado según rol)
    Route::get('/kardex/{productId}', [KardexController::class, 'show'])->whereNumber('productId');
    Route::get('/v1/kardex/{productId}', [KardexController::class, 'show'])->whereNumber('productId');

    // Rutas protegidas exclusivas para el Dueño (Administrador)
    Route::middleware('owner')->group(function () {
        // Reportes Financieros y de Rentabilidad (Exclusivo Dueño)
        Route::get('/reports/profitability', [ReportController::class, 'profitability']);
        Route::get('/v1/reports/profitability', [ReportController::class, 'profitability']);
        Route::get('/reports/inventory-valuation', [ReportController::class, 'inventoryValuation']);
        Route::get('/v1/reports/inventory-valuation', [ReportController::class, 'inventoryValuation']);

        Route::get('/users/export-excel', [UserController::class, 'exportExcel']);
        Route::get('/users', [UserController::class, 'index']);
        Route::post('/users', [UserController::class, 'store']);
        Route::put('/users/{id}', [UserController::class, 'update'])->whereNumber('id');
        Route::patch('/users/{id}/toggle-status', [UserController::class, 'toggleStatus'])->whereNumber('id');

        Route::get('/audit/logins', [AuditController::class, 'logins']);

        // Control administrativo de ventas
        Route::post('/sales/{id}/cancel', [SaleController::class, 'cancel'])->whereNumber('id');
        Route::get('/sales/commissions-report', [SaleController::class, 'commissionsReport']);

        Route::post('/categories', [CategoryController::class, 'store']);
        Route::put('/categories/{id}', [CategoryController::class, 'update'])->whereNumber('id');
        Route::patch('/categories/{id}/toggle-status', [CategoryController::class, 'toggleStatus'])->whereNumber('id');
        Route::delete('/categories/{id}', [CategoryController::class, 'destroy'])->whereNumber('id');

        Route::post('/brands', [BrandController::class, 'store']);
        Route::put('/brands/{id}', [BrandController::class, 'update'])->whereNumber('id');
        Route::patch('/brands/{id}/toggle-status', [BrandController::class, 'toggleStatus'])->whereNumber('id');

        Route::post('/product-models/quick-create', [ProductModelController::class, 'quickCreate']);
        Route::post('/product-models', [ProductModelController::class, 'store']);
        Route::put('/product-models/{id}', [ProductModelController::class, 'update'])->whereNumber('id');
        Route::patch('/product-models/{id}/toggle-status', [ProductModelController::class, 'toggleStatus'])->whereNumber('id');

        Route::post('/products', [ProductController::class, 'store']);
        Route::put('/products/{id}', [ProductController::class, 'update'])->whereNumber('id');
        Route::patch('/products/{id}/toggle-status', [ProductController::class, 'toggleStatus'])->whereNumber('id');
        Route::post('/products/{id}/stock', [ProductController::class, 'adjustStock'])->whereNumber('id');
        Route::get('/products/{id}/stock-history', [ProductController::class, 'stockHistory'])->whereNumber('id');

        // Módulo Proveedores Mayoristas
        Route::get('/suppliers/options', [SupplierController::class, 'options']);
        Route::get('/suppliers', [SupplierController::class, 'index']);
        Route::post('/suppliers', [SupplierController::class, 'store']);
        Route::put('/suppliers/{id}', [SupplierController::class, 'update'])->whereNumber('id');
        Route::patch('/suppliers/{id}/toggle-status', [SupplierController::class, 'toggleStatus'])->whereNumber('id');

        // Módulo Órdenes de Compra y Recepción
        Route::get('/purchases', [PurchaseController::class, 'index']);
        Route::post('/purchases', [PurchaseController::class, 'store']);
        Route::get('/purchases/{id}', [PurchaseController::class, 'show'])->whereNumber('id');
        Route::post('/purchases/{id}/cancel', [PurchaseController::class, 'cancel'])->whereNumber('id');

        // Formas de Pago (Gestión Exclusiva Dueño)
        Route::get('/payment-methods', [PaymentMethodController::class, 'index']);
        Route::post('/payment-methods', [PaymentMethodController::class, 'store']);
        Route::put('/payment-methods/{id}', [PaymentMethodController::class, 'update'])->whereNumber('id');
        Route::patch('/payment-methods/{id}/toggle-status', [PaymentMethodController::class, 'toggleStatus'])->whereNumber('id');

        // Mi Empresa / Datos del Negocio (Gestión Exclusiva Dueño)
        Route::get('/company-settings', [CompanySettingController::class, 'show']);
        Route::post('/company-settings', [CompanySettingController::class, 'update']);

        // Módulo Bot de WhatsApp con IA Gemini (Gestión Exclusiva Dueño)
        Route::get('/whatsapp-bot/settings', [WhatsAppBotController::class, 'getSettings']);
        Route::post('/whatsapp-bot/settings', [WhatsAppBotController::class, 'updateSettings']);
        Route::post('/whatsapp-bot/simulate', [WhatsAppBotController::class, 'simulate']);
        Route::get('/whatsapp-bot/conversations', [WhatsAppBotController::class, 'conversations']);
        Route::post('/whatsapp-bot/conversations/{id}/reset-handoff', [WhatsAppBotController::class, 'resetHandoff'])->whereNumber('id');
    });
});
