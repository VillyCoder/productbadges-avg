# Uso de IA en este proyecto

## 1. Herramientas utilizadas

| Herramienta | Versión / Modelo | Modo de uso | Aprox. % del trabajo |
|---|---|---|---|
| Claude Code CLI | Claude Sonnet 4.6 | Terminal integrado en Windows, sesiones interactivas durante todo el desarrollo | 80% |
| Yo mismo, sin IA | — | Revisión manual de código, testing en navegador, commits, decisiones finales de arquitectura | 20% |

---

## 2. Configuración del proyecto

### CLAUDE.md / AGENTS.md

Sí. El archivo está en `.claude/CLAUDE.md` del repositorio.

Contiene:
- Flujo de trabajo (analizar → proponer → implementar → explicar desviaciones)
- Restricciones técnicas (PS 1.7.8.11, PHP 7.4, sin Composer, Bootstrap activado, multitienda, multilenguaje)
- Normas de seguridad (pSQL, (int), validación de colores hex, escapado Smarty)
- Arquitectura esperada (tres ObjectModels, separación de responsabilidades)
- Esquema de base de datos con nombres de columna
- Hooks a evaluar
- Requisitos de frontend (posiciones, límite, activación por contexto)
- Checklist de verificación antes de finalizar

### settings.json

Existe en `.claude/settings.json` pero está vacío. No se configuraron permisos personalizados, modelo específico ni herramientas bloqueadas. Se usó la configuración por defecto de Claude Code.

---

## 3. Skills personalizadas

Ninguna. No se usaron skills propias, de la comunidad ni adaptadas.

---

## 4. Slash commands personalizados

Ninguno. No se crearon comandos custom en `.claude/commands/` ni equivalentes.

---

## 5. Sub-agentes invocados

No se usaron sub-agentes, Task tool ni Plan Mode. Todo el desarrollo se gestionó en una única sesión conversacional con Claude Code, dado que el alcance del módulo era lo suficientemente acotado.

---

## 6. MCPs (Model Context Protocol)

No se conectó ningún MCP.

| MCP | Para qué lo usaste | ¿Qué te aportó? |
|---|---|---|
| filesystem | — | No se usó |
| github | — | No se usó |
| context7 | — | No se usó |

Con más tiempo habría conectado **context7** con la documentación oficial de PrestaShop. Habría evitado varias iteraciones de corrección en los hooks del frontend: la IA sugirió inicialmente hooks que no están activos en el tema Classic de PS 1.7.6+, lo que requirió depuración manual para encontrar `actionProductFlagsModifier`. Con acceso a la documentación real del core habría llegado al hook correcto en el primer intento.

---

## 7. Prompts importantes

### Prompt 1
- **Herramienta:** Claude Code CLI
- **Prompt:** *"Desarrolla un módulo PrestaShop 1.7.8.11 llamado productbadges. Objetivo: gestionar badges reutilizables para productos del catálogo con soporte multilenguaje, multitienda y configuración desde Back Office. Sigue exactamente la estructura y normas del CLAUDE.md."*
- **Qué generó:** Estructura inicial del módulo con `productbadges.php`, `AdminProductBadgesController.php`, SQL de instalación, plantillas y traducciones.
- **Qué hice con el output:** Revisé la estructura archivo por archivo antes de copiar nada al servidor. Detecté que la IA había creado carpetas extra no previstas en el spec y le pedí que las eliminara.

### Prompt 2
- **Herramienta:** Claude Code CLI
- **Prompt:** *"No, mira, tenemos que seguir a rajatabla esto: [pegué la estructura exacta de carpetas del spec]. No puedo ver ningún archivo fuera de esta estructura."*
- **Qué generó:** Revisión y eliminación de archivos extra. La IA ajustó la estructura al spec sin crear nada adicional.
- **Qué hice con el output:** Acepté y continué. Este prompt fue necesario porque la primera generación añadió un directorio `models/` no pedido.

### Prompt 3
- **Herramienta:** Claude Code CLI
- **Prompt:** *"Cuando guardo y aplico la badge y le doy a activo, no me aparece en los productos. Hay que solucionar esto."*
- **Qué generó:** La IA propuso usar `actionProductFlagsModifier` en lugar de `displayProductFlags`, explicando que en PS 1.7.6+ el tema Classic usa `ProductLazyArray::getFlags()`. También añadió `hookDisplayHeader` para inyectar los colores dinámicos.
- **Qué hice con el output:** Acepté la propuesta pero la verifiqué manualmente en el navegador antes de dar el paso por válido.

### Prompt 4
- **Herramienta:** Claude Code CLI
- **Prompt:** *"Sí, aparece ya. Pero no respeta los colores ni nada de lo que hemos puesto y le hemos asignado."*
- **Qué generó:** Análisis de especificidad CSS: el tema Classic usa `.product-flags li.product-flag` (0,2,1) que sobreescribía nuestro selector. Fix: elevar a `.product-flags li.product-flag.badge-N` (0,3,1). También explicó por qué los colores inline en `$flag.label` no funcionan por el `escape_html = true` de Smarty.
- **Qué hice con el output:** Probé el fix en el navegador. Funcionó. Acepté.

### Prompt 5
- **Herramienta:** Claude Code CLI
- **Prompt:** *"Es como que creamos un div de más."* / *"Si añadimos dos badges, se pone una encima de la otra. Debería ponerse justo al lado."*
- **Qué generó:** Fix del doble renderizado (hacer `hookDisplayAfterProductThumbs` vacío) y fix CSS para que múltiples badges queden en horizontal (`flex-direction: row; flex-wrap: wrap` en `.product-flags`).
- **Qué hice con el output:** Verifiqué ambas correcciones en el navegador antes de aceptar.

### Prompt 6
- **Herramienta:** Claude Code CLI
- **Prompt:** *"Hay que revisar que todo funcione correctamente, que no haya bugs, ver si te has dejado algún error. Igualmente revisaré el código a mano y todo una vez esté listo."*
- **Qué generó:** Revisión completa de todos los archivos del módulo. Detectó el doble renderizado potencial en listados por `displayProductFlags` + `actionProductFlagsModifier`, la clave de traducción faltante del buscador, y generó el README.md e IA.md completos.
- **Qué hice con el output:** Revisé las correcciones una a una. Acepté las que tenían sentido tras verificarlas.

---

## 8. Errores de la IA que detecté

### Error 1 — Admin UI completamente rota
- **Qué generó la IA (mal):** El controlador `AdminProductBadgesController` sin `$this->bootstrap = true` en el constructor.
- **Por qué estaba mal:** `ModuleAdminController::$bootstrap` es `false` por defecto. Sin él, los assets de Bootstrap no cargan y el panel queda con íconos apilados verticalmente, texto invisible hasta hacer hover y layout inutilizable.
- **Cómo lo corregiste:** Lo detecté visualmente al entrar al panel por primera vez. Informé a la IA, que localizó la causa comparando con controladores del core. Añadí `$this->bootstrap = true;` como primera línea del constructor.

### Error 2 — Badges no visibles en el frontend
- **Qué generó la IA (mal):** Implementación inicial usando `hookDisplayProductFlags` como hook principal para el frontend.
- **Por qué estaba mal:** En PS 1.7.6+, el tema Classic no muestra la salida de `displayProductFlags` dentro del contenedor nativo de flags. La IA no contempló que `actionProductFlagsModifier` era el mecanismo correcto para integrarse con `ProductLazyArray::getFlags()`.
- **Cómo lo corregiste:** Al verificar en el navegador que las badges no aparecían, presioné a la IA para que investigara el mecanismo real del tema Classic. Encontró `actionProductFlagsModifier` y reescribimos el hook principal.

### Error 3 — Colores ignorados por el tema
- **Qué generó la IA (mal):** Selector CSS `.product-flag.badge-N` con especificidad 0,2,0.
- **Por qué estaba mal:** El tema Classic aplica `background-color` con especificidad 0,2,1 mediante `.product-flags li.product-flag`, que sobreescribía los colores del módulo.
- **Cómo lo corregiste:** Al ver que las badges aparecían pero sin el color configurado, lo reporté. La IA analizó la especificidad CSS y propuso el selector correcto (0,3,1).

### Error 4 — Doble renderizado en la ficha de producto
- **Qué generó la IA (mal):** `hookActionProductFlagsModifier` activo y simultáneamente `hookDisplayAfterProductThumbs` generando HTML adicional debajo de las miniaturas.
- **Por qué estaba mal:** Las badges aparecían dos veces: una en la lista nativa de flags y otra como bloque separado debajo de las imágenes.
- **Cómo lo corregiste:** Lo detecté visualmente. La IA hizo `hookDisplayAfterProductThumbs` devolver vacío y concentró toda la lógica en `actionProductFlagsModifier`.

### Error 5 — Múltiples badges apiladas verticalmente
- **Qué generó la IA (mal):** CSS con `position: absolute` en `.productbadge` sin limitar el scope, lo que sacaba los elementos del flujo flex y hacía que se solaparan.
- **Por qué estaba mal:** Dentro de `.product-flags` (flex column), los elementos `position: absolute` se superponen en la misma coordenada.
- **Cómo lo corregiste:** Lo detecté al asignar dos badges al mismo producto. La IA introdujo `:not(.product-flag)` para acotar el `position: absolute` a modo standalone, y añadió `flex-direction: row; flex-wrap: wrap` al contenedor de flags.

### Error 6 — Prefijo incorrecto para strings del template y clave de controlador faltante
- **Qué generó la IA (mal):** Las cadenas del template `assign_products.tpl` ("Asignar productos a esta badge", "Guardar asignación", "Producto", "No hay productos disponibles.") registradas bajo el prefijo `adminproductbadgescontroller_` en lugar del correcto `assign_products_`. Además, la clave `adminproductbadgescontroller_*` para el botón "Guardar" del formulario estaba ausente en `en.php`.
- **Por qué estaba mal:** PS usa el nombre del archivo fuente como prefijo de clave. Los strings de `assign_products.tpl` necesitan prefijo `assign_products_`; los de `AdminProductBadgesController.php` necesitan `adminproductbadgescontroller_`. Al tener prefijo incorrecto, PS no encontraba la traducción inglesa y mostraba el string original en español.
- **Cómo lo corregiste:** Lo detecté al cambiar el idioma del Back Office a inglés y ver "Asignar productos a esta badge" y "Guardar asignación" en español. La corrección fue añadir las entradas con el prefijo `assign_products_` correcto (reutilizando los mismos hashes) y la clave faltante del botón Guardar.

### Error 7 — La IA empeoró el Error 6 al intentar corregirlo
- **Qué generó la IA (mal):** Al intentar corregir el Error 6, la IA recalculó todos los hashes con su propia fórmula PHP y reescribió los dos archivos de traducción completos. Esto rompió todas las claves del controlador que sí funcionaban, haciendo que etiquetas como "Color de fondo", "Color de texto", "Posición" y "Activo" también aparecieran en español en el Back Office inglés.
- **Por qué estaba mal:** Los hashes originales los había generado PS internamente con su propio mecanismo. La fórmula `md5(strtolower(trim($s)))` que calculó la IA producía valores distintos a los que PS busca en tiempo de ejecución. El resultado fue que la "corrección" rompió lo que estaba funcionando.
- **Cómo lo corregiste:** Lo detecté al ver que ahora había más strings en español que antes. Restauré los archivos originales desde git (`git checkout -- translations/`) y apliqué únicamente las adiciones necesarias: las claves `assign_products_` con los hashes ya existentes en el archivo, y la clave faltante del botón Guardar.

### Error 8 — Badges invisibles al añadir un idioma nuevo
- **Qué generó la IA (mal):** `getBadgesForProduct()` con `INNER JOIN` en `productbadges_lang`.
- **Por qué estaba mal:** Al instalar el idioma inglés después de haber creado las badges, no existían filas en `productbadges_lang` para el nuevo `id_lang`. El `INNER JOIN` devolvía cero resultados y las badges desaparecían completamente en ese idioma.
- **Cómo lo corregiste:** Lo detecté al cambiar el front office a inglés y ver que las badges no aparecían. Se cambió a `LEFT JOIN` con `COALESCE(NULLIF(bl.text, ''), bl_default.text)` para que cuando no exista traducción en el idioma activo se muestre el texto del idioma por defecto.

---

## 9. Partes que NO usé IA

- **Todos los commits:** Los hice manualmente desde el principio. Decidí así para tener control total del historial y asegurarme de que los mensajes describían exactamente lo que yo había verificado, no lo que la IA creía haber hecho.
- **Testing en navegador:** Cada cambio lo verifiqué yo directamente en el navegador antes de darlo por válido. La IA no tiene acceso al entorno de ejecución y sus afirmaciones de "esto debería funcionar" requieren verificación manual.
- **Instalación en XAMPP:** La copia del módulo al servidor, la instalación desde el Back Office y la creación de datos de prueba (productos, categorías, badges) la hice yo.
- **Revisión línea a línea antes de instalar:** Antes de copiar el módulo al servidor, repasé cada archivo con la IA línea por línea para entender qué hacía cada parte. Fue mi decisión ralentizar el proceso para entender el código, no delegarlo ciegamente.
- **Decisión sobre qué errores aceptar:** En varios momentos la IA propuso soluciones que rechacé o modifiqué. Todas las correcciones pasaron por mi criterio antes de aplicarse.

---

## 10. Reflexión final

**¿Qué te ahorró la IA en este ejercicio?**

El tiempo más grande lo ahorré en dos áreas: la escritura del boilerplate de PrestaShop (estructura de ObjectModel, HelperForm, HelperList, registro de tabs, SQL con las constantes correctas) y la depuración de errores de PS que habría tardado mucho más en encontrar solo, como la propiedad `Language::$id_lang` protegida o el mecanismo `actionProductFlagsModifier` vs `displayProductFlags`. Sin IA, el módulo me habría llevado el doble de tiempo.

**¿En qué te entorpeció o te llevó por mal camino?**

El frontend. La IA generó varias iteraciones erróneas de los hooks antes de llegar a la solución correcta, y cada iteración requería probar en el navegador, detectar el problema y volver a explicárselo. El problema de fondo es que la IA no tiene acceso al entorno real de ejecución ni al código fuente del tema Classic, y razona sobre cómo *debería* funcionar PrestaShop, no sobre cómo *funciona realmente* en 1.7.8.11. También generó la estructura inicial con archivos extra que no estaban en el spec, lo que requirió una corrección explícita.

**¿Qué cambiarías de tu flujo con IA si lo repitieras?**

Conectaría un MCP con el código fuente del core de PrestaShop (o al menos con la documentación oficial actualizada) antes de empezar. El mayor origen de iteraciones innecesarias fue que la IA trabajaba con conocimiento general de PS, no con el comportamiento concreto de PS 1.7.8.11 + tema Classic. Con acceso directo al código de `ProductLazyArray` o de `product-miniature.tpl`, los errores de hooks del frontend no habrían ocurrido.

La otra cosa que cambiaría es invertir más tiempo en el CLAUDE.md antes de escribir la primera línea de código. En este proyecto el CLAUDE.md ya estaba redactado con precisión (restricciones técnicas, normas de seguridad, esquema de base de datos, hooks a evaluar), y eso marcó una diferencia enorme: la IA no necesitó adivinar el contexto ni reinventar decisiones ya tomadas. Cuando las instrucciones son precisas, la IA actúa como un desarrollador que ya conoce el proyecto; cuando son vagas, actúa como uno que acaba de llegar y toma decisiones arbitrarias. Un CLAUDE.md bien trabajado puede traducirse en horas de programación ahorradas y en código que no necesita rehacerse: en lugar de corregir output, simplemente lo revisas y apruebas. Para proyectos más grandes o en equipo, ese archivo se convierte en el documento más valioso del repositorio.
