# productbadges

Módulo PrestaShop 1.7.8.x para gestionar badges visuales reutilizables sobre los productos del catálogo.

## Requisitos

| Componente | Versión |
|---|---|
| PrestaShop | 1.7.8.x |
| PHP | 7.4.33 (versión probada) |
| MySQL / MariaDB | 5.7+ / 10.2+ |
| Tema | Classic (probado) |

No requiere Composer ni dependencias JavaScript externas más allá de jQuery (ya incluido en PrestaShop).

## Instalación

### Opción A — Subir ZIP desde el Back Office

1. Abrir la carpeta `modules/` del repositorio.
2. Hacer clic derecho sobre la carpeta `productbadges` → **Comprimir** (o **Enviar a → Carpeta comprimida**).
3. Acceder al Back Office → **Módulos y servicios** → **Subir módulo**.
4. Seleccionar el archivo `productbadges.zip` generado.

### Opción B — Instalación manual

1. Copiar la carpeta `productbadges` dentro de `[prestashop]/modules/`.
2. Acceder al Back Office → **Módulos y servicios**.
3. Buscar **Product Badges** e instalar.

La instalación crea automáticamente tres tablas en base de datos (`productbadges`, `productbadges_lang`, `productbadges_product`) y registra la pestaña **Product Badges** bajo el menú **Catálogo**.

## Desinstalación

La desinstalación elimina completamente las tres tablas y todos los valores de configuración asociados. No queda ningún rastro en la base de datos.

## Uso

### Crear una badge

1. Ir a **Catálogo → Product Badges → Añadir nuevo**.
2. Rellenar texto (por idioma), color de fondo, color de texto y posición.
3. Guardar.

### Asignar productos

En el formulario de edición de una badge aparece el panel **Asignar productos**. Seleccionar los productos y guardar la asignación.

### Configuración global

**Módulos → Product Badges → Configurar** ofrece cuatro opciones:

| Opción | Descripción |
|---|---|
| Módulo activo | Activa o desactiva todo el módulo |
| Mostrar en listados | Muestra badges en páginas de categoría/búsqueda |
| Mostrar en ficha de producto | Muestra badges en la página de producto |
| Máximo de badges por producto | Límite de badges visibles (1-10) |

## Estructura de ficheros

```
productbadges/
├── productbadges.php                          # Módulo principal + ObjectModel ProductBadge
├── controllers/
│   └── admin/
│       └── AdminProductBadgesController.php   # CRUD de badges y asignación de productos
├── sql/
│   ├── install.php                            # DDL de creación de tablas
│   └── uninstall.php                          # DDL de eliminación de tablas
├── views/
│   ├── css/
│   │   └── productbadges.css                  # Estilos frontend
│   ├── js/
│   │   └── admin.js                           # Color picker y buscador en Back Office
│   └── templates/
│       ├── admin/
│       │   └── assign_products.tpl            # Panel de asignación de productos
│       └── hook/
│           ├── displayProductFlags.tpl        # Plantilla de fallback (temas legacy)
│           └── displayAfterProductThumbs.tpl  # Plantilla de fallback (temas legacy)
└── translations/
    ├── es.php
    └── en.php
```

## Decisiones técnicas

### ObjectModel único con `multilang => true`

El spec sugería tres ObjectModels separados (`ProductBadge`, `ProductBadgeLang`, `ProductBadgeProduct`). Se optó por un único `ProductBadge extends ObjectModel` con `'multilang' => true` en la definición, aprovechando el mecanismo nativo de PrestaShop para tablas `_lang`. La tabla de relación N:M con productos (`productbadges_product`) se gestiona directamente con `Db::getInstance()`, siguiendo el patrón estándar de módulos oficiales de PrestaShop.

### Nomenclatura de columnas

El spec indicaba `id_badge` y `background_color`. Se usó `id_productbadge` y `bg_color` para:
- Evitar colisiones con otros módulos que puedan usar `id_badge` genérico.
- Seguir la convención de nombres largos y descriptivos de PS (`id_productbadge` es unívoco).

### Hook `actionProductFlagsModifier` sobre `displayProductFlags`

En PrestaShop 1.7.6+, el tema Classic gestiona los flags de producto a través de `ProductLazyArray::getFlags()`, que dispara `actionProductFlagsModifier`. El hook `displayProductFlags` queda como punto de extensión para temas más antiguos pero en Classic no produce la integración visual nativa.

Se decidió usar `actionProductFlagsModifier` porque:
- Inyecta las badges directamente en la lista nativa de flags (`<ul class="product-flags">`).
- Funciona en listados, página de home y ficha de producto con la misma lógica.
- El comportamiento visual (colores, posicionamiento, apilado) es coherente con los flags nativos.

### CSS dinámico via `displayHeader`

Los colores de fondo y texto de cada badge son configurables desde el Back Office. Smarty activa `escape_html = true` en el front office, por lo que inyectar los colores directamente en el valor de `$flag.label` no es viable. Se opta por generar un bloque `<style>` en el `<head>` mediante el hook `displayHeader`, con selectores de mayor especificidad que el tema Classic (0,3,1 frente a 0,2,1).

### Sin dependencias de terceros

El color picker de Back Office se implementa con `<input type="color">` nativo (HTML5) sincronizado mediante jQuery. Sin librerías externas, sin Composer.

## Caché de productos destacados

El módulo `ps_featuredproducts` (usado en la página de inicio) cachea su salida Smarty. Tras asignar o modificar badges, es necesario limpiar la caché de PrestaShop desde:

**Parámetros avanzados → Rendimiento → Limpiar caché**

## Seguridad

- Todos los IDs se castean a `(int)`.
- Los colores se validan con `preg_match('/^#[0-9a-fA-F]{6}$/', ...)` antes de guardar.
- Las posiciones se validan con `in_array(..., ['top-left', 'top-right'], true)`.
- Las salidas Smarty usan `|escape:'html':'UTF-8'` o `|intval`.
- No se construye SQL con datos de usuario sin sanitizar.

## Lo que se omitió y por qué

| Funcionalidad | Motivo |
|---|---|
| Badges distintas por tienda | El spec indica que no es obligatorio. La consulta de asignación es global y funciona correctamente en multitienda. |
| Drag & drop para ordenar badges | Fuera del alcance del spec. El orden se gestiona por `id_productbadge`. |
| Compatibilidad con otros temas (Warehouse, etc.) | Solo se requería y probó Classic. Los hooks `displayProductFlags` y `displayAfterProductThumbs` están registrados como fallback para otros temas. |
| Tests automáticos | No requeridos en el spec. |
