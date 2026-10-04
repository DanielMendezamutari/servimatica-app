<?php

namespace App\Application\Ai;

use App\Infrastructure\Persistence\Eloquent\CompanySettingModel;
use App\Infrastructure\Persistence\Eloquent\ProductModel;

final class GeminiCatalogContextBuilder
{
    /**
     * Construye el contexto de conocimiento en tiempo real para Google Gemini.
     * Solo incluye productos activos con stock físico disponible y excluye terminantemente costos de compra.
     */
    public function buildSystemPrompt(): string
    {
        $company = CompanySettingModel::first();
        $storeName = $company?->trade_name ?: 'Servimática PC';
        $city = $company?->city ?: 'Santa Cruz de la Sierra';
        $address = $company?->address ?: 'Comercial Chiriguano pasillo 2 local # 333';
        $phone = $company?->mobile ?: ($company?->phone ?: '67369293');

        $products = ProductModel::with(['category', 'brand', 'model'])
            ->where('status', 'active')
            ->where('stock', '>', 0)
            ->whereHas('category', fn($q) => $q->where('status', 'active'))
            ->orderBy('category_id')
            ->orderBy('name')
            ->get();

        $baseUrl = $this->getCatalogBaseUrl();
        $catalogText = "";
        foreach ($products as $p) {
            $cat = $p->category?->name ?? 'General';
            $brand = $p->brand?->name ?? 'Genérica';
            $model = $p->model?->name ?? '';
            $condition = match ($p->condition) {
                'nuevo' => 'Nuevo de paquete',
                'open_box' => 'Open Box (caja abierta, sin uso)',
                'usado' => 'Seminuevo garantizado',
                'reacondicionado' => 'Reacondicionado certificado',
                default => 'Disponible'
            };
            $priceBs = number_format((float) $p->sale_price, 2, '.', ',');
            $warranty = $p->warranty_days > 0 ? "{$p->warranty_days} días de garantía" : "Garantía de tienda";
            $has360 = is_array($p->gallery_images) && count($p->gallery_images) >= 2;
            $link360 = "{$baseUrl}?product={$p->id}";

            $desc = !empty($p->description) ? " | Specs: " . trim(preg_replace('/\s+/', ' ', $p->description)) : "";

            $catalogText .= sprintf(
                "- ID %d: *%s* | Cat: %s | Marca/Mod: %s %s | Estado: %s | Precio: *Bs. %s* | Stock: %d disp. | Garantía: %s%s | Link 360°: %s%s\n",
                $p->id,
                $p->name,
                $cat,
                $brand,
                $model,
                $condition,
                $priceBs,
                $p->stock,
                $warranty,
                $desc,
                $link360,
                $has360 ? " (Cuenta con fotos multi-ángulo 360°)" : ""
            );
        }

        if (empty($catalogText)) {
            $catalogText = "Actualmente todos los productos en catálogo se encuentran en proceso de reposición de inventario.\n";
        }

        return <<<PROMPT
Eres el Asesor Comercial Oficial y Experto Tecnológico de "{$storeName}", una prestigiosa tienda de computación en {$city}, Bolivia.
Ubicación física: {$address}.
Teléfono / WhatsApp oficial: {$phone}.
Moneda oficial: Bolivianos (Bs.).

OBJETIVO PRINCIPAL:
Asesorar de manera humana, cálida, vendedora y empática a los clientes que escriben por WhatsApp o redes sociales (Facebook, TikTok Live, Marketplace), resolviendo dudas técnicas y cerrando la venta o invitándolos a pasar por la tienda en el Comercial Chiriguano.

REGLAS CRÍTICAS Y NO NEGOCIABLES (Constitución del Negocio):
1. VERDAD ÚNICA: Solo puedes recomendar y confirmar disponibilidad de los productos que aparecen en el INVENTARIO REAL listado abajo. NUNCA inventes productos, procesadores, memorias o precios que no estén en la lista. Si el cliente pide algo que no tenemos, dile amablemente que por ahora no lo tienes en stock y recomiéndale la mejor alternativa de la lista.
2. ENLACES 360°: Siempre que recomiendes un producto o te pregunten por él, comparte su enlace directo exacto (Link 360°) para que el cliente lo abra en su celular y lo vea rotar en 360°.
3. PRECIOS: Expresa siempre los precios en Bolivianos (ejemplo: *Bs. 3,000.00*). NUNCA hables de dólares a menos que el cliente lo pida expresamente (usando el tipo de cambio oficial de referencia 6.96 Bs/USD).
4. TONO: Habla en español latinoamericano cálido y cordial con modismos amables de Bolivia ("¡Buenas! Claro que sí...", "con gusto te ayudo hermano/amigo...", "te garantizo..."). Usa negritas con asteriscos `*texto*` para resaltar nombres y precios como en WhatsApp. Sé conciso: no escribas párrafos interminables.
5. DERIVACIÓN HUMANA: Si el cliente insiste en hablar con una persona, regatear precios fuera de lista, o pide transferir dinero a una cuenta bancaria específica, dile amablemente que con gusto le pasarás la consulta a un asesor de tienda física de inmediato.
6. CONFIDENCIALIDAD: Queda estrictamente prohibido hablar de costos de compra, distribuidores o utilidades.
7. ASESORAMIENTO SEGÚN LA GAMA CONSULTADA:
- GAMA ALTA / GAMER / TRABAJO PESADO: Si el cliente consulta por "gama alta", "gamer", "potente", "computadora para arquitectura", "render 3D", "edición 4K" o "ingeniería", identifícale los equipos de Gama Alta de tu catálogo (ejemplo: equipos con Core i7/i9 o Ryzen 7, tarjetas gráficas dedicadas NVIDIA RTX serie 40, memorias de 16GB a 32GB y pantallas de altos Hz). Explica sus ventajas técnicas y proporciona de inmediato el enlace 360° de cada una para que las aprecie.
- GAMA MEDIA / PRODUCTIVIDAD: Si consulta por "gama media", "para trabajar", "programar" o "diseño gráfico intermedio", recomiéndale las laptops de Gama Media (Core i5 o Ryzen 5 con 16GB de RAM y SSD rápido).
- GAMA BAJA / ECONÓMICAS: Si consulta por "gama baja", "económica", "para colegio", "estudiantes" o "oficina básica", preséntale las opciones accesibles más convenientes (Celeron, Core i3 o Ryzen 3) destacando su precio económico en Bs. y garantía.

INVENTARIO REAL DISPONIBLE EN TIENDA EN ESTE MOMENTO:
{$catalogText}
PROMPT;
    }

    /**
     * Resuelve la URL base pública del catálogo (configurable desde settings / .env o por defecto en desarrollo local).
     */
    public function getCatalogBaseUrl(): string
    {
        $saved = \Illuminate\Support\Facades\Cache::get('servimatica_whatsapp_bot_settings', []);
        if (!empty($saved['catalog_base_url'])) {
            return rtrim((string) $saved['catalog_base_url'], '/');
        }

        if (env('PUBLIC_CATALOG_URL')) {
            return rtrim((string) env('PUBLIC_CATALOG_URL'), '/');
        }

        // Si estamos en entorno local, el catálogo Vite se sirve en el puerto 5173
        if (app()->environment('local')) {
            return 'http://127.0.0.1:5173/catalogo';
        }

        return url('/catalogo');
    }
}

