## Flujo de trabajo

Antes de modificar archivos:

1. Analizar la estructura actual.
2. Proponer un plan breve.
3. Implementar por fases.
4. Explicar cualquier decisión que se aparte de las prácticas estándar de PrestaShop 1.7.

Al generar código:

- Mostrar únicamente el código necesario.
- Evitar placeholders.
- Evitar pseudocódigo.
- Mantener compatibilidad con PHP 7.4.

# CLAUDE.md

## Proyecto

Desarrollar un módulo PrestaShop 1.7.8.x llamado productbadges.

Objetivo: gestionar badges reutilizables para productos del catálogo con soporte multilenguaje, multitienda y configuración desde Back Office.

## Restricciones técnicas

- Compatibilidad objetivo: PrestaShop 1.7.8.11
- PHP 7.4+
- No utilizar Composer salvo necesidad justificada
- Bootstrap activado
- Compatible con multitienda
- Compatible con multilenguaje
- Instalación y desinstalación limpia
- Sin dependencias JS externas distintas de jQuery

## Normas de desarrollo

### Seguridad

Siempre:

- Validar todos los inputs en servidor
- Escapar todas las salidas Smarty
- Usar pSQL() para cadenas
- Convertir IDs mediante (int)
- Validar colores hexadecimales antes de guardar
- No confiar en validaciones JavaScript

Nunca:

- Construir SQL concatenando datos sin sanitizar
- Mostrar datos del usuario sin escape
- Ejecutar consultas directas desde plantillas

### Arquitectura

Mantener separación clara de responsabilidades:

#### productbadges.php

Responsabilidades:

- Registro de hooks
- Instalación
- Desinstalación
- Configuración global
- Carga de assets

No incluir:

- Formularios complejos
- Lógica CRUD de badges
- Consultas extensas

#### ObjectModels

Crear:

- ProductBadge
- ProductBadgeLang
- ProductBadgeProduct

Usar definición estándar de PrestaShop.

#### Admin Controller

AdminProductBadgesController

Responsabilidades:

- CRUD de badges
- Formularios
- Listados
- Validaciones

Usar:

- HelperForm
- HelperList

### Base de datos

Crear únicamente las tablas necesarias:

**productbadges**
- id_badge
- background_color
- text_color
- position
- active

**productbadges_lang**
- id_badge
- id_lang
- text

**productbadges_product**
- id_badge
- id_product

Eliminar completamente todas las tablas durante uninstall.

### Hooks recomendados

Evaluar:

- displayProductAdditionalInfo
- displayProductPriceBlock
- displayHome
- displayHeader

Seleccionar únicamente los necesarios para:

- Listados
- Búsqueda
- Home
- Ficha de producto

No registrar hooks innecesarios.

### Frontend

Mostrar badges sobre la imagen del producto.

Posiciones permitidas:

- top-left
- top-right

Respetar:

- límite máximo configurado
- activación global
- activación por contexto (listado o ficha)

Cargar CSS únicamente cuando el módulo esté activo.

### Configuración

Crear opciones:

- Activar módulo
- Mostrar en listados
- Mostrar en ficha
- Máximo de badges visibles

Guardar mediante Configuration.

### Multilenguaje

El texto de la badge debe:

- almacenarse por idioma
- mostrarse según idioma activo
- soportar mínimo español e inglés

### Multitienda

El módulo debe funcionar correctamente con Shop::isFeatureActive().

No es obligatorio tener badges distintas por tienda.

Evitar consultas que ignoren el contexto actual.

## Calidad del código

Prioridades:

1. Seguridad
2. Compatibilidad PrestaShop
3. Mantenibilidad
4. Rendimiento

Preferir código simple y entendible frente a soluciones complejas.

Si una decisión técnica es discutible, documentarla en README.md.

## Antes de finalizar

Verificar:

- instalación limpia
- desinstalación limpia
- CRUD completo
- traducciones funcionando
- badges visibles en frontend
- configuración aplicada correctamente
- ausencia de warnings PHP
- ausencia de errores SQL

Generar commits pequeños y descriptivos durante el desarrollo.
