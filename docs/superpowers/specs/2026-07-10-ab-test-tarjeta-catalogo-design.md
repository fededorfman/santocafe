# Test A/B: tarjeta de catálogo compacta en la home

## Contexto y objetivo

La tarjeta de producto actual en la grilla de catálogo de la home (`content-product.php`) muestra mucha información: imagen, badge SCA, badge de descuento, botón "Ver ficha", título con país/bandera, notas de cata, barras de perfil (intensidad/acidez/cuerpo), selector de formato con dos precios, molienda, y precio + botón "Añadir" en el footer.

La hipótesis a probar: una tarjeta mucho más chica y minimalista —solo imagen, nombre, precio y un botón "Ver"— invita más a los visitantes a entrar a la ficha del producto (en vez de decidir todo desde la home), y esto podría traducirse en más "agregar al carrito" en total durante la visita, aunque la tarjeta chica no tenga botón de agregado rápido.

Se implementa como test A/B propio (sin plugin), usando cookie para asignar variante y un panel propio en wp-admin para ver los resultados (contadores simples, sin depender de saber usar Google Analytics).

## Alcance

- Aplica **solo** a la grilla de catálogo de la home (`template-parts/home/section-catalog.php`).
- **No** afecta la sección de "productos sugeridos" del carrito vacío (`template-parts/cart/cart-empty.php`), que sigue usando siempre la tarjeta actual — ambas secciones comparten la misma plantilla `content-product.php`, así que el switch de variante debe estar acotado por contexto, no ser global a la plantilla.
- No requiere ningún plugin nuevo.
- LiteSpeed Cache está desactivado actualmente en el sitio, así que no hay que resolver ningún problema de caché de página para este test (si se reactivara el caché más adelante, la home tendría que excluirse mientras el test esté corriendo).

## Asignación de variante

- Cookie `sc_ab_card` (`control` o `compact`), asignada la primera vez que un visitante entra a la home, si la cookie no existe todavía.
- Split 50/50 (`wp_rand(0, 1)` o equivalente).
- Duración: 30 días — el mismo visitante sigue viendo la misma variante en visitas posteriores dentro de esa ventana.
- La asignación ocurre en PHP (`template_redirect` o similar, antes de renderizar), así no hay parpadeo ni diferencia entre lo que ve el usuario y lo que se manda a analítica.
- Administradores logueados con capacidad `edit_posts` quedan **excluidos** del test (siempre ven la variante `control`), mismo criterio que ya usa `sc_analytics_enabled()` en `inc/analytics.php` para no ensuciar las métricas con las propias visitas del dueño del sitio.

## Nuevo campo: foto para la tarjeta compacta

En `inc/product-meta.php`, dentro de la pestaña "☕ Café" del panel de producto, nuevo campo de imagen:

- Meta key: `_sc_card_photo` (attachment ID, mismo patrón que un campo de imagen estándar de WooCommerce/ACF: botón "Subir imagen" + preview + "Quitar").
- Si no está seteado, la tarjeta compacta usa el thumbnail normal del producto como respaldo — ningún producto queda sin imagen mientras se van cargando las fotos alternativas.

## La tarjeta compacta (variante `compact`)

Nuevo template part: `template-parts/product/card-compact.php`, usado por `section-catalog.php` cuando la variante activa es `compact`.

Estructura:
- Imagen cuadrada (usa `_sc_card_photo` si existe, si no el thumbnail normal), con overlay de gradiente oscuro abajo.
- Nombre del producto y precio (250g) superpuestos sobre el gradiente, esquina inferior izquierda.
- Botón "Ver" (mismo estilo `btn btn--primary btn--sm` que "Añadir"/"Ver ficha" existentes), esquina inferior derecha, mismo destino que hacer clic en toda la tarjeta: la ficha del producto.
- Sin badge SCA, sin badge de descuento, sin notas de cata, sin barras de perfil, sin selector de formato, sin botón de agregar rápido — todo eso vive únicamente en la ficha.
- Grilla: mantiene el mismo número de columnas que la tarjeta actual en desktop/tablet (4 columnas en desktop, 3 en ≤1280px, 2 en ≤1024px — sin cambios ahí). El único cambio es en el breakpoint `@media (max-width: 600px)`, donde la tarjeta actual cae a 1 columna: la variante compacta usa **2 columnas** en ese breakpoint específico.

La tarjeta `control` (la actual) no se modifica.

## Dónde se ve la info: panel propio en wp-admin

Forma principal de ver resultados, sin depender de saber armar reportes en GA4. Nueva página `WooCommerce > Test A/B Catálogo` (mismo patrón que el panel existente "Emails automáticos" en `inc/scheduled-emails.php`), con una tabla:

| | Vistas | Agregaron al carrito | Conversión |
|---|---|---|---|
| Tarjeta actual | N | N | % |
| Tarjeta chica | N | N | % |

**Contadores** (guardados como `wp_options`, uno por variante y métrica — `sc_ab_views_control`, `sc_ab_views_compact`, `sc_ab_conv_control`, `sc_ab_conv_compact`):

- **Vistas**: se incrementa el contador de la variante **solo la primera vez** que se asigna la cookie `sc_ab_card` a un visitante nuevo (no en cada recarga de la home).
- **Agregaron al carrito**: para no contar de más a alguien que agrega varios productos, se usa una segunda cookie `sc_ab_converted`. Al agregar algo al carrito (hook `woocommerce_add_to_cart`), si esa cookie todavía no existe para este visitante: se incrementa el contador de conversión de su variante y se marca la cookie, así visitas/compras posteriores del mismo visitante no se vuelven a contar.
- Botón "Reiniciar contadores" en el panel, para arrancar de cero si hace falta (por ejemplo, después de pruebas propias antes de lanzar el test de verdad).

## Tracking adicional (opcional, vía GTM)

Además del panel de wp-admin, se deja disponible la variante (`ab_variant`) en el `dataLayer` (GTM ya está cargado en `inc/analytics.php`) por si más adelante se quiere cruzar el resultado con otros datos (origen de tráfico, campaña, etc.) en GA4 — esto es un extra, no la forma principal de ver los resultados:

1. En cada carga de página, push de `{ event: 'sc_ab_ready', ab_test: 'catalog_card', ab_variant: 'control' | 'compact' }` (lee la cookie `sc_ab_card`; no dispara nada si el visitante está excluido del test).
2. Requiere configuración manual del lado de GTM/GA4 (crear la dimensión personalizada, etc.) — no es necesario para usar el panel de wp-admin.

No se declara un "ganador" automáticamente — se decide mirando la tabla del panel cuando haya tráfico suficiente.

## Cómo se cierra el test

Cuando haya una variante ganadora clara:
1. Se hardcodea esa tarjeta como la única (se borra la lógica de cookie/split y el template part que no ganó).
2. Se borra el panel de wp-admin, los contadores en `wp_options`, y el push a `dataLayer` (ya no hacen falta).
3. Si ganó la tarjeta actual (no la compacta), también se borra el campo "Foto para tarjeta compacta" (`_sc_card_photo`) en `inc/product-meta.php` — ya no se usa en ningún lado.
4. Bump de versión y deploy normal.

Esto queda como tarea aparte, a pedido, una vez que haya datos.

## Testing

- Verificar que la cookie se asigna una sola vez y persiste 30 días (no se reasigna en cada visita).
- Verificar que un admin logueado con `edit_posts` siempre ve `control`, sin importar la cookie, y no suma a los contadores.
- Verificar visualmente la tarjeta compacta en desktop y en mobile (2 columnas).
- Verificar que el campo `_sc_card_photo` se guarda/lee bien, y que el fallback al thumbnail normal funciona cuando está vacío.
- Verificar que el contador de "Vistas" solo sube una vez por visitante (no en cada recarga), y que "Agregaron al carrito" solo sube una vez por visitante aunque agregue varios productos.
- Probar el botón "Reiniciar contadores" del panel.
- Confirmar que el carrito vacío (`cart-empty.php`) sigue mostrando siempre la tarjeta actual, sin importar la cookie de variante.
