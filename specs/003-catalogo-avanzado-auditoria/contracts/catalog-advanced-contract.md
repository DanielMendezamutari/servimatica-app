# Contrato API: Catálogo Avanzado, Jerarquías y Exportación a Excel

**Ruta Base**: `/api/categories` y `/api/products`
**Autenticación**: Bearer JWT Token

---

## 1. Categorías con Jerarquía (Familias y Subfamilias)

- **Endpoint**: `GET /api/categories?tree=true`
- **Permisos**: Dueño / Vendedor
- **Respuesta (200 OK)**:
  ```json
  {
    "data": [
      {
        "id": 1,
        "name": "Componentes",
        "parent_id": null,
        "status": "active",
        "children": [
          {
            "id": 5,
            "name": "Procesadores",
            "parent_id": 1,
            "status": "active"
          },
          {
            "id": 6,
            "name": "Tarjetas de Video",
            "parent_id": 1,
            "status": "active"
          }
        ]
      }
    ]
  }
  ```

- **Crear Subfamilia**: `POST /api/categories` con `parent_id` opcional que valida existencia en `categories`.

---

## 2. Listado Avanzado de Productos con Filtros Nuevos

- **Endpoint**: `GET /api/products`
- **Query Params**:
  - `page`, `per_page`, `search`
  - `category_id`: ID de categoría principal
  - `subfamily_id`: ID de subfamilia
  - `brand_id`: ID de marca
  - `condition`: `nuevo` | `open_box` | `usado` | `reacondicionado`
- **Respuesta (200 OK - Vendedor)**:
  ```json
  {
    "data": [
      {
        "id": 100,
        "sku": "COM-0012",
        "name": "Laptop ASUS TUF F15 FX506",
        "category": { "id": 2, "name": "Laptops" },
        "subfamily": { "id": 8, "name": "Gamer" },
        "brand": { "id": 1, "name": "ASUS" },
        "model": { "id": 10, "name": "TUF Gaming F15" },
        "condition": "open_box",
        "sale_price": 6850.00,
        "stock": 3,
        "status": "active"
      }
    ]
  }
  ```
- **Respuesta (200 OK - Dueño)**: Incluye adicionalmente `cost_price`, `margin_amount`, `margin_percent`, `min_stock`.

---

## 3. Exportación de Inventario a Excel (.xlsx)

- **Endpoint**: `GET /api/products/export-excel`
- **Permisos**: Dueño / Vendedor
- **Query Params**: Admite los mismos filtros activos de la tabla (`search`, `category_id`, `brand_id`, `condition`, `status`).
- **Respuesta**:
  - `Content-Type`: `application/vnd.openxmlformats-officedocument.spreadsheetml.sheet`
  - `Content-Disposition`: `attachment; filename="inventario_servimatica_YYYY-MM-DD.xlsx"`
- **Diferenciación por Rol**:
  - **Dueño**: Incluye columnas de Costo de compra (Bs.), Precio de venta (Bs.), Margen monetario y porcentual.
  - **Vendedor**: Omite estrictamente todas las columnas de costo y margen; únicamente incluye SKU, Producto, Marca, Modelo, Categoría, Condición, Precio de venta (Bs.) y Stock.
