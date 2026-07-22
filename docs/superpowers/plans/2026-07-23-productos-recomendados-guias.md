# Productos recomendados en guías — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Agregar un campo "Productos recomendados" al editor de posts, y mostrar esos productos (tarjeta compacta) al final de las guías que lo tengan configurado, justo arriba del CTA genérico existente. Configurar las 3 guías que ya recomiendan productos en su propio texto (espresso, italiana/moka, filtro/V60).

**Architecture:** Nuevo módulo `inc/guide-related-products.php` con un meta box nativo de WordPress (`add_meta_box`) en la pantalla de edición de posts, guardando un array de IDs de producto en `_sc_related_products`. `single.php` lee ese meta y, si hay productos válidos, renderiza una sección nueva reutilizando `template-parts/product/card-compact.php` (mismo template del test A/B) y las clases de grilla `.catalog-section__grid`/`.catalog-section__grid--compact` ya existentes.

**Tech Stack:** PHP 8 (WordPress meta boxes nativos), sin plugins ni librerías nuevas.

**Nota sobre testing:** sin suite automatizada. Verificación vía `php -l`, scripts que bootean `wp-load.php`, y `curl`/navegador contra `http://santocafe.local`. Rutas usadas:
- Binario PHP: `/Users/fede/Library/Application Support/Local/lightning-services/php-8.2.29+0/bin/darwin-arm64/bin/php`
- php.ini del sitio: `/Users/fede/Library/Application Support/Local/run/X9GxT_pm-/conf/php/php.ini`
- MySQL: binario `/Users/fede/Library/Application Support/Local/lightning-services/mysql-8.4.0/bin/darwin-arm64/bin/mysql`, socket `/Users/fede/Library/Application Support/Local/run/X9GxT_pm-/mysql/mysqld.sock`, usuario/clave `root`/`root`, base `local`
- Scratchpad: `/private/tmp/claude-501/-Users-fede-Documents-SantoCafe/84ca3a79-655e-4964-97f1-4cd93c722c70/scratchpad/`

**Estado verificado antes de escribir este plan:**
- `style.css` está en versión `0.1.269`.
- `functions.php` carga módulos desde un array `$modules` (líneas ~101-116).
- Los 3 posts de guías de método de preparación y los productos que ya mencionan por nombre en su propio texto:
  - Post 69 "Cómo preparar espresso en casa": Santo Ouro Doce (producto 32), Santo Sereno (35), Los Santos (41).
  - Post 70 "Cómo preparar café en cafetera italiana (moka)": Macondo (20), Santo Yungas (29).
  - Post 71 "Cómo preparar café de filtro (V60 y otros)": Cielo Andino (26), Camino Inca (23), Santo Equilibrio (38).
- `.catalog-section__grid` (`assets/css/_home.css:381`) y `.catalog-section__grid--compact` (`assets/css/_product-card-compact.css`) ya existen y no dependen de estar dentro de `.catalog-section` — se pueden reusar tal cual en la página de guía.
- `template-parts/product/card-compact.php` ya existe (test A/B), lee `global $product` y no necesita cambios.

---

### Task 1: Meta box "Productos recomendados" en el editor de posts

**Files:**
- Create: `apps/web/app/public/wp-content/themes/santocafe/inc/guide-related-products.php`
- Modify: `apps/web/app/public/wp-content/themes/santocafe/functions.php:101-116` (agregar el módulo nuevo al array `$modules`)

- [ ] **Step 1: Crear el archivo del módulo**

```php
<?php
defined('ABSPATH') || exit;

/**
 * Santo Café — Campo "Productos recomendados" en posts (guías).
 *
 * Ver docs/superpowers/specs/2026-07-23-productos-recomendados-guias-design.md
 */

add_action( 'add_meta_boxes', function (): void {
    add_meta_box(
        'sc_guide_related_products',
        'Productos recomendados',
        'sc_guide_related_products_meta_box',
        'post',
        'normal',
        'default'
    );
} );

function sc_guide_related_products_meta_box( WP_Post $post ): void {
    wp_nonce_field( 'sc_guide_products_save', 'sc_guide_products_nonce' );

    $selected = get_post_meta( $post->ID, '_sc_related_products', true );
    $selected = is_array( $selected ) ? array_map( 'absint', $selected ) : [];

    $products = function_exists( 'wc_get_products' ) ? wc_get_products( [
        'status'  => 'publish',
        'limit'   => -1,
        'orderby' => 'title',
        'order'   => 'ASC',
    ] ) : [];

    if ( empty( $products ) ) {
        echo '<p>No hay productos publicados.</p>';
        return;
    }
    ?>
    <p class="description">
        Elegí los cafés que se muestran como "Recomendados para esta preparación"
        al final de esta guía. Si no marcás ninguno, esa sección no aparece.
    </p>
    <?php foreach ( $products as $sc_product ) :
        $sc_pid   = $sc_product->get_id();
        $sc_pais  = sc_get_product_meta( $sc_pid, 'pais' );
        $sc_label = $sc_product->get_name() . ( $sc_pais ? ' — ' . $sc_pais : '' );
    ?>
        <label style="display:block;margin-bottom:6px;">
            <input type="checkbox" name="sc_related_products[]"
                   value="<?php echo esc_attr( $sc_pid ); ?>"
                   <?php checked( in_array( $sc_pid, $selected, true ) ); ?>>
            <?php echo esc_html( $sc_label ); ?>
        </label>
    <?php endforeach; ?>
    <?php
}

add_action( 'save_post_post', function ( int $post_id ): void {
    if ( ! isset( $_POST['sc_guide_products_nonce'] )
        || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['sc_guide_products_nonce'] ) ), 'sc_guide_products_save' ) ) {
        return;
    }
    if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
        return;
    }
    if ( ! current_user_can( 'edit_post', $post_id ) ) {
        return;
    }

    $ids = ( isset( $_POST['sc_related_products'] ) && is_array( $_POST['sc_related_products'] ) )
        ? array_map( 'absint', wp_unslash( $_POST['sc_related_products'] ) )
        : [];

    update_post_meta( $post_id, '_sc_related_products', $ids );
} );
```

- [ ] **Step 2: Registrar el módulo en `functions.php`**

En el array `$modules`, agregar `'inc/guide-related-products.php',` después de `'inc/ab-testing.php',`:

```php
$modules = [
    'inc/theme-helpers.php',
    'inc/woocommerce.php',
    'inc/product-meta.php',
    'inc/ab-testing.php',
    'inc/guide-related-products.php',
    'inc/ajax-handlers.php',
    'inc/stock.php',
    'inc/seo.php',
    'inc/llms.php',
    'inc/security.php',
    'inc/analytics.php',
    'inc/contact.php',
    'inc/emails.php',
    'inc/scheduled-emails.php',
    'inc/email-tracking.php',
    'inc/order-review.php',
];
```

- [ ] **Step 3: Verificar sintaxis**

```bash
"/Users/fede/Library/Application Support/Local/lightning-services/php-8.2.29+0/bin/darwin-arm64/bin/php" -l "/Users/fede/Documents/SantoCafe/apps/web/app/public/wp-content/themes/santocafe/inc/guide-related-products.php"
"/Users/fede/Library/Application Support/Local/lightning-services/php-8.2.29+0/bin/darwin-arm64/bin/php" -l "/Users/fede/Documents/SantoCafe/apps/web/app/public/wp-content/themes/santocafe/functions.php"
```
Expected: `No syntax errors detected` en ambos.

- [ ] **Step 4: Probar guardado/lectura del meta con un script que bootea WordPress**

Crear `/private/tmp/claude-501/-Users-fede-Documents-SantoCafe/84ca3a79-655e-4964-97f1-4cd93c722c70/scratchpad/test_guide_products.php`:

```php
<?php
define( 'WP_USE_THEMES', false );
require '/Users/fede/Documents/SantoCafe/apps/web/app/public/wp-load.php';

$test_post_id = 72; // una guía que NO se va a configurar de verdad (Task 3 no la toca)

delete_post_meta( $test_post_id, '_sc_related_products' );
echo "Antes: " . var_export( get_post_meta( $test_post_id, '_sc_related_products', true ), true ) . " (esperado: '' vacío)\n";

update_post_meta( $test_post_id, '_sc_related_products', [ 20, 29 ] );
$saved = get_post_meta( $test_post_id, '_sc_related_products', true );
echo "Después de guardar [20,29]: " . var_export( $saved, true ) . " (esperado: array(20,29))\n";

delete_post_meta( $test_post_id, '_sc_related_products' );
echo "Limpio: " . var_export( get_post_meta( $test_post_id, '_sc_related_products', true ), true ) . " (esperado: '' vacío)\n";
```

Correr:
```bash
"/Users/fede/Library/Application Support/Local/lightning-services/php-8.2.29+0/bin/darwin-arm64/bin/php" -c "/Users/fede/Library/Application Support/Local/run/X9GxT_pm-/conf/php/php.ini" "/private/tmp/claude-501/-Users-fede-Documents-SantoCafe/84ca3a79-655e-4964-97f1-4cd93c722c70/scratchpad/test_guide_products.php" 2>&1 | grep -v "Xdebug\|Zend Engine\|imagick\|module API\|Warning: PHP Startup\|These options"
rm -f "/private/tmp/claude-501/-Users-fede-Documents-SantoCafe/84ca3a79-655e-4964-97f1-4cd93c722c70/scratchpad/test_guide_products.php"
```
Expected:
```
Antes: '' (esperado: '' vacío)
Después de guardar [20,29]: array (
  0 => 20,
  1 => 29,
) (esperado: array(20,29))
Limpio: '' (esperado: '' vacío)
```
(el formato exacto de `var_export` de un array puede variar en el salto de línea, lo importante es que muestre los valores 20 y 29 en el paso del medio, y `''` vacío antes/después).

- [ ] **Step 5: Verificar el meta box en el navegador**

Entrar a editar el post 72 ("Qué es el café de especialidad...") en wp-admin —
**no usar los posts 69, 70 ni 71**, que Task 3 configura de verdad más adelante y
cuyo estado debe quedar limpio (sin meta) hasta ese momento. Confirmar que aparece
la caja "Productos recomendados" con los 8 cafés listados como checkboxes, tildar
2 o 3, guardar el post (Actualizar), recargar la pantalla de edición y confirmar
que los mismos checkboxes siguen tildados. Después, **destildar todos y guardar de
nuevo**, dejando el post 72 sin ningún producto marcado (mismo estado que tenía
antes de esta prueba), para no interferir con la verificación del Task 2 ni con el
alcance decidido (72 no debe tener productos recomendados).

- [ ] **Step 6: Commit**

```bash
cd /Users/fede/Documents/SantoCafe
git add apps/web/app/public/wp-content/themes/santocafe/inc/guide-related-products.php apps/web/app/public/wp-content/themes/santocafe/functions.php
git commit -m "feat(web): campo Productos recomendados en el editor de posts (guías)"
```

---

### Task 2: Render de la sección en `single.php` + estilos

**Files:**
- Modify: `apps/web/app/public/wp-content/themes/santocafe/single.php`
- Modify: `apps/web/app/public/wp-content/themes/santocafe/assets/css/_content.css`

- [ ] **Step 1: Insertar la sección nueva en `single.php`, justo antes del CTA genérico**

El archivo hoy tiene, dentro del `<article>`, esta secuencia (verificada antes de escribir este plan):

```php
            <div class="sc-article__body">
                <?php the_content(); ?>
            </div>

            <?php
            // CTA block — link to store
            ?>
            <div class="sc-article__cta">
```

Insertar el bloque nuevo entre el `</div>` que cierra `sc-article__body` y el comentario `// CTA block`:

```php
            <div class="sc-article__body">
                <?php the_content(); ?>
            </div>

            <?php
            $sc_related_product_ids = get_post_meta( get_the_ID(), '_sc_related_products', true );
            $sc_related_product_ids = is_array( $sc_related_product_ids ) ? $sc_related_product_ids : [];
            ?>
            <?php if ( ! empty( $sc_related_product_ids ) ) : ?>
            <section class="sc-article__products">
                <h2 class="sc-article__products-title">Recomendados para esta preparación</h2>
                <div class="catalog-section__grid catalog-section__grid--compact sc-article__products-grid">
                    <?php
                    global $product;
                    foreach ( $sc_related_product_ids as $sc_rp_id ) {
                        $product = wc_get_product( $sc_rp_id );
                        if ( $product && $product->is_visible() ) {
                            get_template_part( 'template-parts/product/card-compact' );
                        }
                    }
                    $product = null; // esta página no es de producto — no dejar $product global apuntando al último recomendado
                    ?>
                </div>
            </section>
            <?php endif; ?>

            <?php
            // CTA block — link to store
            ?>
            <div class="sc-article__cta">
```

- [ ] **Step 2: Agregar el CSS del título/sección en `_content.css`**

Agregar, después del bloque `.sc-article__cta` existente (antes de `/* ---- Related posts ---- */`):

```css
/* ---- Recomendados para esta preparación (guías) ---- */
.sc-article__products {
    margin: var(--spacing-xl) 0;
}

.sc-article__products-title {
    font-size: var(--font-size-lg);
    color: var(--color-oscuro);
    margin-bottom: var(--spacing-lg);
}
```

- [ ] **Step 3: Verificar sintaxis**

```bash
"/Users/fede/Library/Application Support/Local/lightning-services/php-8.2.29+0/bin/darwin-arm64/bin/php" -l "/Users/fede/Documents/SantoCafe/apps/web/app/public/wp-content/themes/santocafe/single.php"
```
Expected: `No syntax errors detected`.

- [ ] **Step 4: Verificar que sin productos configurados, no cambia nada**

Ningún post tiene todavía `_sc_related_products` configurado en este punto del plan (Task 3 es quien lo configura). Confirmar que una guía cualquiera se sigue viendo exactamente igual que antes (solo el CTA genérico, sin la sección nueva):

```bash
curl -s "http://santocafe.local/?p=69" | grep -c "sc-article__products"
```
Expected: `0` (todavía no hay productos configurados en el post 69, así que la sección no debe aparecer en el HTML).

- [ ] **Step 5: Commit**

```bash
cd /Users/fede/Documents/SantoCafe
git add apps/web/app/public/wp-content/themes/santocafe/single.php apps/web/app/public/wp-content/themes/santocafe/assets/css/_content.css
git commit -m "feat(web): sección de productos recomendados en las guías"
```

---

### Task 3: Configurar las 3 guías + verificación final + bump de versión

**Files:**
- Modify: `apps/web/app/public/wp-content/themes/santocafe/style.css`

- [ ] **Step 1: Configurar los productos recomendados de los 3 posts**

Crear `/private/tmp/claude-501/-Users-fede-Documents-SantoCafe/84ca3a79-655e-4964-97f1-4cd93c722c70/scratchpad/set_guide_products.php`:

```php
<?php
define( 'WP_USE_THEMES', false );
require '/Users/fede/Documents/SantoCafe/apps/web/app/public/wp-load.php';

$map = [
    69 => [ 32, 35, 41 ], // Espresso: Santo Ouro Doce, Santo Sereno, Los Santos
    70 => [ 20, 29 ],     // Italiana/moka: Macondo, Santo Yungas
    71 => [ 26, 23, 38 ], // Filtro/V60: Cielo Andino, Camino Inca, Santo Equilibrio
];

foreach ( $map as $post_id => $product_ids ) {
    $post = get_post( $post_id );
    if ( ! $post ) {
        echo "Post {$post_id} no encontrado, salto.\n";
        continue;
    }
    update_post_meta( $post_id, '_sc_related_products', $product_ids );
    $saved = get_post_meta( $post_id, '_sc_related_products', true );
    echo "Post {$post_id} ({$post->post_title}): " . implode( ',', $saved ) . "\n";
}
```

Correr:
```bash
"/Users/fede/Library/Application Support/Local/lightning-services/php-8.2.29+0/bin/darwin-arm64/bin/php" -c "/Users/fede/Library/Application Support/Local/run/X9GxT_pm-/conf/php/php.ini" "/private/tmp/claude-501/-Users-fede-Documents-SantoCafe/84ca3a79-655e-4964-97f1-4cd93c722c70/scratchpad/set_guide_products.php" 2>&1 | grep -v "Xdebug\|Zend Engine\|imagick\|module API\|Warning: PHP Startup\|These options"
rm -f "/private/tmp/claude-501/-Users-fede-Documents-SantoCafe/84ca3a79-655e-4964-97f1-4cd93c722c70/scratchpad/set_guide_products.php"
```
Expected:
```
Post 69 (Cómo preparar espresso en casa: guía completa): 32,35,41
Post 70 (Cómo preparar café en cafetera italiana (moka): paso a paso): 20,29
Post 71 (Cómo preparar café de filtro (V60 y otros): guía para principiantes): 26,23,38
```

- [ ] **Step 2: Verificar en el navegador las 3 guías configuradas + las 2 que no**

Con `http://santocafe.local/` abierto (o vía curl como fallback):

```bash
curl -s "http://santocafe.local/?p=69" | grep -c "product-card-compact__view"
curl -s "http://santocafe.local/?p=70" | grep -c "product-card-compact__view"
curl -s "http://santocafe.local/?p=71" | grep -c "product-card-compact__view"
```
Expected: `3`, `2` y `3` respectivamente (una tarjeta compacta por cada producto recomendado configurado).

```bash
curl -s "http://santocafe.local/?p=72" | grep -c "sc-article__products"
curl -s "http://santocafe.local/?p=73" | grep -c "sc-article__products"
```
Expected: `0` en ambos — estas 2 guías no tienen productos configurados, así que la sección no debe aparecer, y el CTA genérico sigue igual.

Si hay navegador disponible, entrar a `http://santocafe.local/?p=69` y confirmar visualmente: la sección "Recomendados para esta preparación" aparece arriba del CTA "Explora nuestros cafés...", con 3 tarjetas compactas (imagen + nombre/precio + botón "Ver"), en grilla de 2 columnas si se achica la ventana a menos de 600px de ancho.

- [ ] **Step 3: Bump de versión**

En `style.css`, incrementar el número de versión (revisar el valor actual antes de editar).

- [ ] **Step 4: Commit**

```bash
cd /Users/fede/Documents/SantoCafe
git add apps/web/app/public/wp-content/themes/santocafe/style.css
git commit -m "chore(web): bump de versión — productos recomendados en guías"
```

---

## Fuera de alcance de este plan (documentado, no implementar todavía)

- Selección automática de productos por contenido/categoría — se descartó en el brainstorming, cada guía se configura a mano.
- Configurar productos recomendados en las guías 72 y 73 (explicativas, sin recomendación de producto en su texto) — quedan sin sección, tal como se decidió.
