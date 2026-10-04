<?php

use App\Infrastructure\Persistence\Eloquent\ProductModel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/{path?}', function (Request $request) {
    $index = public_path('app/index.html');
    if (is_file($index)) {
        $html = file_get_contents($index);

        // Inyección dinámica de Meta Tags Open Graph para Facebook, WhatsApp, TikTok y Twitter
        $productId = $request->query('product');
        if ($productId && is_numeric($productId)) {
            $product = ProductModel::with(['category', 'brand'])->find((int) $productId);
            if ($product) {
                $title = e("{$product->name} • Bs. " . number_format((float) $product->sale_price, 2) . " - Servimática PC");
                $desc = e("Garantía técnica de {$product->warranty_days} días. Vitrina interactiva 360° y entrega inmediata en Santa Cruz de la Sierra, Bolivia.");
                $img = e($product->image_url);

                $html = preg_replace('/<title>.*?<\/title>/', "<title>{$title}</title>", $html);
                $html = preg_replace('/<meta property="og:title" content=".*?"\s*\/?>/', "<meta property=\"og:title\" content=\"{$title}\" />", $html);
                $html = preg_replace('/<meta property="og:description" content=".*?"\s*\/?>/', "<meta property=\"og:description\" content=\"{$desc}\" />", $html);
                $html = preg_replace('/<meta property="og:image" content=".*?"\s*\/?>/', "<meta property=\"og:image\" content=\"{$img}\" />", $html);
                $html = preg_replace('/<meta name="twitter:title" content=".*?"\s*\/?>/', "<meta name=\"twitter:title\" content=\"{$title}\" />", $html);
                $html = preg_replace('/<meta name="twitter:description" content=".*?"\s*\/?>/', "<meta name=\"twitter:description\" content=\"{$desc}\" />", $html);
                $html = preg_replace('/<meta name="twitter:image" content=".*?"\s*\/?>/', "<meta name=\"twitter:image\" content=\"{$img}\" />", $html);
            }
        }

        return response($html, 200, [
            'Content-Type' => 'text/html; charset=UTF-8',
            'Cache-Control' => 'no-store, no-cache, must-revalidate',
        ]);
    }
    return response('Servimática: ejecute npm run dev en admin-starter-kit para abrir la interfaz de desarrollo.', 200);
})->where('path', '(?!api(?:/|$)).*');
