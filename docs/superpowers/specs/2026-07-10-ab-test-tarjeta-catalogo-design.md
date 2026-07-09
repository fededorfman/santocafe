# Test A/B: tarjeta de catálogo compacta en la home

## Contexto y objetivo

La tarjeta de producto actual en la grilla de catálogo de la home (`content-product.php`) muestra mucha información: imagen, badge SCA, badge de descuento, botón "Ver ficha", título con país/bandera, notas de cata, barras de perfil (intensidad/acidez/cuerpo), selector de formato con dos precios, molienda, y precio + botón "Añadir" en el footer.

La hipótesis a probar: una tarjeta mucho más chica y minimalista —solo imagen, nombre, precio y un botón "Ver"— invita más a los visitantes a entrar a la ficha del producto (en vez de decidir todo desde la home), y esto podría traducirse en más "agregar al carrito" en total durante la visita, aunque la tarjeta chica no tenga botón de agregado rápido.

Se implementa como test A/B propio (sin plugin), usando cookie para asignar variante y el Google Tag Manager / GA4 ya instalados para medir resultados.

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

## Tracking

Todo vía `dataLayer` (GTM ya está cargado en `inc/analytics.php`), sin tocar la configuración de GTM/GA4 desde el tema (eso se arma en la consola de GTM, fuera de este repo):

1. **Variante asignada** — en cada carga de la home, push de `{ event: 'sc_ab_view', ab_test: 'catalog_card', ab_variant: 'control' | 'compact' }`. Esto permite crear una dimensión personalizada en GA4 para segmentar cualquier reporte por variante.
2. **Clic a la ficha desde la tarjeta** (señal secundaria/diagnóstica) — push de `{ event: 'sc_ab_card_click', ab_variant: '...' , product_id }` al hacer clic en la imagen/nombre/botón "Ver" de cualquier tarjeta.
3. **Agregar al carrito** (métrica principal) — este tema no toca el tracking de "add to cart" en sí (eso ya existe o se configura del lado de GTM). Lo que sí agrega es que la variante (`ab_variant`) quede disponible en el `dataLayer` durante toda la sesión (no solo en la home), leyendo la cookie `sc_ab_card` en cada página. Con eso disponible, en GTM se arma una dimensión que etiqueta cualquier evento de "add to cart" con la variante del visitante, sin importar en qué página del sitio ocurra. Confirmar del lado de GTM que el evento de "add to cart" ya está configurado antes de sacar conclusiones del test.

No se declara un "ganador" automáticamente — se revisa el reporte de GA4 manualmente cuando haya tráfico suficiente.

## Cómo se cierra el test

Cuando haya una variante ganadora clara:
1. Se hardcodea esa tarjeta como la única (se borra la lógica de cookie/split y el template part que no ganó).
2. Se quitan los eventos `sc_ab_view` / `sc_ab_card_click` del dataLayer (ya no hacen falta).
3. Bump de versión y deploy normal.

Esto queda como tarea aparte, a pedido, una vez que haya datos.

## Testing

- Verificar que la cookie se asigna una sola vez y persiste 30 días (no se reasigna en cada visita).
- Verificar que un admin logueado con `edit_posts` siempre ve `control`, sin importar la cookie.
- Verificar visualmente la tarjeta compacta en desktop y en mobile (2 columnas).
- Verificar que el campo `_sc_card_photo` se guarda/lee bien, y que el fallback al thumbnail normal funciona cuando está vacío.
- Verificar en el inspector de red / consola que el `dataLayer.push` de `sc_ab_view` y `sc_ab_card_click` lleva los datos correctos.
- Confirmar que el carrito vacío (`cart-empty.php`) sigue mostrando siempre la tarjeta actual, sin importar la cookie de variante.
