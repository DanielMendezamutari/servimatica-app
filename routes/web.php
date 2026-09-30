<?php

use Illuminate\Support\Facades\Route;

Route::get('/{path?}', function () {
    $index = public_path('app/index.html');
    if (is_file($index)) {
        return response()->file($index, ['Cache-Control' => 'no-store']);
    }
    return response('Servimática: ejecute npm run dev en admin-starter-kit para abrir la interfaz de desarrollo.', 200);
})->where('path', '(?!api(?:/|$)).*');
