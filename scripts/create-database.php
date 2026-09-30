<?php

// Solo prepara la base local configurada; no borra tablas ni registros.
require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
$config = config('database.connections.mysql');
if ($config['database'] !== 'servimatica_app' || !in_array($config['host'], ['localhost', '127.0.0.1'], true)) {
    throw new RuntimeException('Este script está limitado a servimatica_app en el servidor local.');
}
$pdo = new PDO('mysql:host='.$config['host'].';port='.$config['port'], $config['username'], $config['password']);
$pdo->exec('CREATE DATABASE IF NOT EXISTS servimatica_app CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
echo "Base local preparada.\n";
