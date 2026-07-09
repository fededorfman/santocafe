# Test A/B: Tarjeta de Catálogo Compacta — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Probar una tarjeta de producto compacta (imagen + nombre + precio + botón "Ver") contra la tarjeta actual en la grilla de catálogo de la home, asignando variante por cookie y midiendo resultados (vistas / "agregar al carrito" / conversión) en un panel propio de wp-admin.

**Architecture:** Nuevo módulo `inc/ab-testing.php` centraliza toda la lógica del test (asignación de variante por cookie, contadores en `wp_options`, panel de admin, push opcional a `dataLayer`). Nuevo template part `template-parts/product/card-compact.php` + CSS propio `_product-card-compact.css` para la tarjeta chica. `section-catalog.php` decide cuál renderizar según la variante. Nuevo campo `_sc_card_photo` en el panel de producto para la foto alternativa.

**Tech Stack:** PHP 8 (WordPress hooks/cookies nativos), WooCommerce 10.9, CSS (sin build step, `@import` plano), sin plugins ni librerías nuevas.

**Nota sobre testing:** este proyecto no tiene suite de tests automatizados (WordPress theme sin PHPUnit configurado). La verificación en este plan sigue el patrón ya establecido en el repo: `php -l` para sintaxis, scripts PHP puntuales que bootean `wp-load.php` para probar funciones directamente contra la base de datos local, y `curl` contra `http://santocafe.local` para verificar el HTML/comportamiento real. Las rutas de PHP de Local usadas a lo largo de este plan:
- Binario PHP: `/Users/fede/Library/Application Support/Local/lightning-services/php-8.2.29+0/bin/darwin-arm64/bin/php`
- php.ini del sitio: `/Users/fede/Library/Application Support/Local/run/X9GxT_pm-/conf/php/php.ini`
- Scratchpad para scripts de prueba: `/private/tmp/claude-501/-Users-fede-Documents-SantoCafe/84ca3a79-655e-4964-97f1-4cd93c722c70/scratchpad/`

---

### Task 1: Módulo `inc/ab-testing.php` — asignación de variante

**Files:**
- Create: `apps/web/app/public/wp-content/themes/santocafe/inc/ab-testing.php`
- Modify: `apps/web/app/public/wp-content/themes/santocafe/functions.php:101-116`

- [ ] **Step 1: Crear el archivo con la lógica de asignación de variante**

```php
<?php
defined('ABSPATH') || exit;

/**
 * Santo Café — Test A/B de la tarjeta de catálogo (home).
 *
 * Ver docs/superpowers/specs/2026-07-10-ab-test-tarjeta-catalogo-design.md
 */

const SC_AB_COOKIE           = 'sc_ab_card';
const SC_AB_CONVERTED_COOKIE = 'sc_ab_converted';
const SC_AB_COOKIE_DAYS      = 30;

/**
 * Devuelve la variante del visitante actual: 'control' o 'compact'.
 * Admins con capacidad edit_posts siempre ven 'control' (no ensucian
 * las métricas, mismo criterio que sc_analytics_enabled()).
 */
function sc_ab_get_variant(): string {
    if ( is_user_logged_in() && current_user_can( 'edit_posts' ) ) {
        return 'control';
    }

    $cookie = isset( $_COOKIE[ SC_AB_COOKIE ] ) ? sanitize_text_field( wp_unslash( $_COOKIE[ SC_AB_COOKIE ] ) ) : '';

    return in_array( $cookie, [ 'control', 'compact' ], true ) ? $cookie : 'control';
}

/**
 * Si el visitante todavía no tiene la cookie de variante, se la asigna
 * (50/50) y suma la vista al contador correspondiente. Solo corre en
 * la home, antes de que se imprima cualquier HTML.
 */
function sc_ab_maybe_assign_variant(): void {
    if ( ! is_front_page() ) {
        return;
    }
    if ( is_user_logged_in() && current_user_can( 'edit_posts' ) ) {
        return;
    }
    if ( isset( $_COOKIE[ SC_AB_COOKIE ] ) ) {
        return;
    }

    $variant = ( 0 === wp_rand( 0, 1 ) ) ? 'control' : 'compact';
    $expires = time() + SC_AB_COOKIE_DAYS * DAY_IN_SECONDS;

    setcookie( SC_AB_COOKIE, $variant, $expires, COOKIEPATH ? COOKIEPATH : '/', COOKIE_DOMAIN );
    $_COOKIE[ SC_AB_COOKIE ] = $variant; // disponible ya en este mismo request

    $option_key = ( 'control' === $variant ) ? 'sc_ab_views_control' : 'sc_ab_views_compact';
    update_option( $option_key, (int) get_option( $option_key, 0 ) + 1 );
}
add_action( 'template_redirect', 'sc_ab_maybe_assign_variant' );
```

- [ ] **Step 2: Registrar el módulo en `functions.php`**

En `functions.php`, dentro del array `$modules` (línea 101-116), agregar la línea después de `'inc/product-meta.php',`:

```php
$modules = [
    'inc/theme-helpers.php',
    'inc/woocommerce.php',
    'inc/product-meta.php',
    'inc/ab-testing.php',
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

- [ ] **Step 3: Verificar sintaxis PHP**

```bash
"/Users/fede/Library/Application Support/Local/lightning-services/php-8.2.29+0/bin/darwin-arm64/bin/php" -l "/Users/fede/Documents/SantoCafe/apps/web/app/public/wp-content/themes/santocafe/inc/ab-testing.php"
"/Users/fede/Library/Application Support/Local/lightning-services/php-8.2.29+0/bin/darwin-arm64/bin/php" -l "/Users/fede/Documents/SantoCafe/apps/web/app/public/wp-content/themes/santocafe/functions.php"
```
Expected: `No syntax errors detected` en ambos.

- [ ] **Step 4: Probar la asignación de variante contra el sitio local**

```bash
rm -f /tmp/ab_test_cookies.txt
curl -s -c /tmp/ab_test_cookies.txt "http://santocafe.local/" -o /dev/null
cat /tmp/ab_test_cookies.txt | grep sc_ab_card
```
Expected: una línea con `sc_ab_card` y el valor `control` o `compact`.

```bash
MYSQL_BIN="/Users/fede/Library/Application Support/Local/lightning-services/mysql-8.4.0/bin/darwin-arm64/bin/mysql"
SOCK="/Users/fede/Library/Application Support/Local/run/X9GxT_pm-/mysql/mysqld.sock"
"$MYSQL_BIN" --socket="$SOCK" -u root -proot local -e "SELECT option_name, option_value FROM wp_options WHERE option_name LIKE 'sc_ab_views%';" 2>/dev/null
```
Expected: el contador de la variante asignada en el paso anterior debe estar en `1`.

- [ ] **Step 5: Confirmar que una segunda visita con la misma cookie NO vuelve a sumar vista**

```bash
curl -s -b /tmp/ab_test_cookies.txt -c /tmp/ab_test_cookies.txt "http://santocafe.local/" -o /dev/null
"$MYSQL_BIN" --socket="$SOCK" -u root -proot local -e "SELECT option_name, option_value FROM wp_options WHERE option_name LIKE 'sc_ab_views%';" 2>/dev/null
rm -f /tmp/ab_test_cookies.txt
```
Expected: el contador sigue en `1` (no subió a `2`).

- [ ] **Step 6: Confirmar que un admin logueado siempre ve 'control', sin importar la cookie**

Crear `/private/tmp/claude-501/-Users-fede-Documents-SantoCafe/84ca3a79-655e-4964-97f1-4cd93c722c70/scratchpad/test_ab_admin.php`:

```php
<?php
define( 'WP_USE_THEMES', false );
require '/Users/fede/Documents/SantoCafe/apps/web/app/public/wp-load.php';

// Fuerza la cookie a 'compact' para simular que el admin ya la tenía de antes.
$_COOKIE[ SC_AB_COOKIE ] = 'compact';

echo "Sin loguear, con cookie 'compact': " . sc_ab_get_variant() . " (esperado: compact)\n";

$admins = get_users( [ 'role' => 'administrator', 'number' => 1 ] );
if ( empty( $admins ) ) {
    echo "No hay usuarios administradores en esta instalación para la prueba.\n";
    exit;
}
wp_set_current_user( $admins[0]->ID );

echo "Logueado como admin, con cookie 'compact': " . sc_ab_get_variant() . " (esperado: control)\n";
```

Correr:
```bash
"/Users/fede/Library/Application Support/Local/lightning-services/php-8.2.29+0/bin/darwin-arm64/bin/php" -c "/Users/fede/Library/Application Support/Local/run/X9GxT_pm-/conf/php/php.ini" "/private/tmp/claude-501/-Users-fede-Documents-SantoCafe/84ca3a79-655e-4964-97f1-4cd93c722c70/scratchpad/test_ab_admin.php" 2>&1 | grep -v "Xdebug\|Zend Engine\|imagick\|module API\|Warning: PHP Startup\|These options"
rm -f "/private/tmp/claude-501/-Users-fede-Documents-SantoCafe/84ca3a79-655e-4964-97f1-4cd93c722c70/scratchpad/test_ab_admin.php"
```
Expected:
```
Sin loguear, con cookie 'compact': compact (esperado: compact)
Logueado como admin, con cookie 'compact': control (esperado: control)
```

- [ ] **Step 7: Commit**

```bash
cd /Users/fede/Documents/SantoCafe
git add apps/web/app/public/wp-content/themes/santocafe/inc/ab-testing.php apps/web/app/public/wp-content/themes/santocafe/functions.php
git commit -m "feat(web): asignación de variante para test A/B de tarjeta de catálogo"
```

---

### Task 2: Tracking de conversión ("agregar al carrito")

**Files:**
- Modify: `apps/web/app/public/wp-content/themes/santocafe/inc/ab-testing.php`

- [ ] **Step 1: Agregar la función de tracking de conversión al final del archivo**

```php
/**
 * Suma una conversión (agregar al carrito) para la variante del
 * visitante, una sola vez por visitante — usa la cookie
 * sc_ab_converted para no contar de más a quien agrega varios
 * productos durante la misma visita.
 */
function sc_ab_track_conversion(): void {
    if ( is_user_logged_in() && current_user_can( 'edit_posts' ) ) {
        return;
    }
    if ( isset( $_COOKIE[ SC_AB_CONVERTED_COOKIE ] ) ) {
        return;
    }

    $variant = isset( $_COOKIE[ SC_AB_COOKIE ] ) ? sanitize_text_field( wp_unslash( $_COOKIE[ SC_AB_COOKIE ] ) ) : '';
    if ( ! in_array( $variant, [ 'control', 'compact' ], true ) ) {
        return; // este visitante no está en el test
    }

    $expires = time() + SC_AB_COOKIE_DAYS * DAY_IN_SECONDS;
    setcookie( SC_AB_CONVERTED_COOKIE, '1', $expires, COOKIEPATH ? COOKIEPATH : '/', COOKIE_DOMAIN );
    $_COOKIE[ SC_AB_CONVERTED_COOKIE ] = '1';

    $option_key = ( 'control' === $variant ) ? 'sc_ab_conv_control' : 'sc_ab_conv_compact';
    update_option( $option_key, (int) get_option( $option_key, 0 ) + 1 );
}
add_action( 'woocommerce_add_to_cart', 'sc_ab_track_conversion' );
```

- [ ] **Step 2: Verificar sintaxis**

```bash
"/Users/fede/Library/Application Support/Local/lightning-services/php-8.2.29+0/bin/darwin-arm64/bin/php" -l "/Users/fede/Documents/SantoCafe/apps/web/app/public/wp-content/themes/santocafe/inc/ab-testing.php"
```
Expected: `No syntax errors detected`.

- [ ] **Step 3: Probar el flujo completo con un script que bootea WordPress**

Crear `/private/tmp/claude-501/-Users-fede-Documents-SantoCafe/84ca3a79-655e-4964-97f1-4cd93c722c70/scratchpad/test_ab_conversion.php`:

```php
<?php
define( 'WP_USE_THEMES', false );
require '/Users/fede/Documents/SantoCafe/apps/web/app/public/wp-load.php';

// Reset de contadores para la prueba.
delete_option( 'sc_ab_conv_compact' );

// Simula un visitante ya asignado a 'compact', sin cookie de conversión todavía.
$_COOKIE[ SC_AB_COOKIE ] = 'compact';
unset( $_COOKIE[ SC_AB_CONVERTED_COOKIE ] );

echo "Antes: " . (int) get_option( 'sc_ab_conv_compact', 0 ) . "\n";

// Primera "compra": debe sumar.
do_action( 'woocommerce_add_to_cart' );
echo "Despues de 1er add_to_cart: " . (int) get_option( 'sc_ab_conv_compact', 0 ) . " (esperado: 1)\n";

// setcookie() no actualiza $_COOKIE automáticamente en CLI; lo simulamos
// a mano como lo haría el navegador en la siguiente request.
$_COOKIE[ SC_AB_CONVERTED_COOKIE ] = '1';

// Segunda "compra" del mismo visitante: NO debe sumar de nuevo.
do_action( 'woocommerce_add_to_cart' );
echo "Despues de 2do add_to_cart: " . (int) get_option( 'sc_ab_conv_compact', 0 ) . " (esperado: 1, sin cambio)\n";

delete_option( 'sc_ab_conv_compact' );
```

- [ ] **Step 4: Correr el script**

```bash
"/Users/fede/Library/Application Support/Local/lightning-services/php-8.2.29+0/bin/darwin-arm64/bin/php" -c "/Users/fede/Library/Application Support/Local/run/X9GxT_pm-/conf/php/php.ini" "/private/tmp/claude-501/-Users-fede-Documents-SantoCafe/84ca3a79-655e-4964-97f1-4cd93c722c70/scratchpad/test_ab_conversion.php" 2>&1 | grep -v "Xdebug\|Zend Engine\|imagick\|module API\|Warning: PHP Startup\|These options"
```
Expected:
```
Antes: 0
Despues de 1er add_to_cart: 1 (esperado: 1)
Despues de 2do add_to_cart: 1 (esperado: 1, sin cambio)
```

- [ ] **Step 5: Borrar el script de prueba**

```bash
rm -f "/private/tmp/claude-501/-Users-fede-Documents-SantoCafe/84ca3a79-655e-4964-97f1-4cd93c722c70/scratchpad/test_ab_conversion.php"
```

- [ ] **Step 6: Commit**

```bash
cd /Users/fede/Documents/SantoCafe
git add apps/web/app/public/wp-content/themes/santocafe/inc/ab-testing.php
git commit -m "feat(web): tracking de conversión (agregar al carrito) para el test A/B"
```

---

### Task 3: Panel de resultados en wp-admin

**Files:**
- Modify: `apps/web/app/public/wp-content/themes/santocafe/inc/ab-testing.php`

- [ ] **Step 1: Agregar el registro del submenú y la página de admin al final del archivo**

```php
/**
 * Panel de resultados: WooCommerce > Test A/B Catálogo.
 */
add_action( 'admin_menu', function (): void {
    add_submenu_page(
        'woocommerce',
        'Test A/B Catálogo',
        'Test A/B Catálogo',
        'manage_woocommerce',
        'sc-ab-catalog',
        'sc_ab_admin_page'
    );
} );

function sc_ab_admin_page(): void {
    if ( ! current_user_can( 'manage_woocommerce' ) ) {
        return;
    }

    if ( isset( $_POST['sc_ab_reset'] ) && check_admin_referer( 'sc_ab_reset_action', 'sc_ab_reset_nonce' ) ) {
        foreach ( [ 'sc_ab_views_control', 'sc_ab_views_compact', 'sc_ab_conv_control', 'sc_ab_conv_compact' ] as $option_key ) {
            delete_option( $option_key );
        }
        echo '<div class="notice notice-success"><p>Contadores reiniciados.</p></div>';
    }

    $views_control = (int) get_option( 'sc_ab_views_control', 0 );
    $views_compact = (int) get_option( 'sc_ab_views_compact', 0 );
    $conv_control  = (int) get_option( 'sc_ab_conv_control', 0 );
    $conv_compact  = (int) get_option( 'sc_ab_conv_compact', 0 );

    $rate_control = $views_control > 0 ? round( $conv_control / $views_control * 100, 1 ) : 0;
    $rate_compact = $views_compact > 0 ? round( $conv_compact / $views_compact * 100, 1 ) : 0;
    ?>
    <div class="wrap">
        <h1>Test A/B: Tarjeta de Catálogo</h1>
        <p>Comparación entre la tarjeta actual y la tarjeta compacta en la grilla de la home. "Agregaron al carrito" cuenta una sola vez por visitante, sin importar cuántos productos agregue.</p>

        <table class="widefat striped" style="max-width:640px;margin-top:16px;">
            <thead>
                <tr>
                    <th></th>
                    <th>Vistas</th>
                    <th>Agregaron al carrito</th>
                    <th>Conversión</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><strong>Tarjeta actual</strong></td>
                    <td><?php echo esc_html( $views_control ); ?></td>
                    <td><?php echo esc_html( $conv_control ); ?></td>
                    <td><?php echo esc_html( $rate_control ); ?>%</td>
                </tr>
                <tr>
                    <td><strong>Tarjeta chica</strong></td>
                    <td><?php echo esc_html( $views_compact ); ?></td>
                    <td><?php echo esc_html( $conv_compact ); ?></td>
                    <td><?php echo esc_html( $rate_compact ); ?>%</td>
                </tr>
            </tbody>
        </table>

        <form method="post" style="margin-top:20px;">
            <?php wp_nonce_field( 'sc_ab_reset_action', 'sc_ab_reset_nonce' ); ?>
            <button type="submit" name="sc_ab_reset" value="1" class="button"
                    onclick="return confirm('¿Reiniciar todos los contadores del test?');">
                Reiniciar contadores
            </button>
        </form>
    </div>
    <?php
}
```

- [ ] **Step 2: Verificar sintaxis**

```bash
"/Users/fede/Library/Application Support/Local/lightning-services/php-8.2.29+0/bin/darwin-arm64/bin/php" -l "/Users/fede/Documents/SantoCafe/apps/web/app/public/wp-content/themes/santocafe/inc/ab-testing.php"
```
Expected: `No syntax errors detected`.

- [ ] **Step 3: Verificar en el navegador**

Entrar a `http://santocafe.local/wp-admin/admin.php?page=sc-ab-catalog` logueado como admin. Confirmar que se ve la tabla con los contadores actuales, y que el botón "Reiniciar contadores" pone todo en 0 al confirmarlo.

- [ ] **Step 4: Commit**

```bash
cd /Users/fede/Documents/SantoCafe
git add apps/web/app/public/wp-content/themes/santocafe/inc/ab-testing.php
git commit -m "feat(web): panel de resultados del test A/B en wp-admin"
```

---

### Task 4: Push opcional a dataLayer (GTM)

**Files:**
- Modify: `apps/web/app/public/wp-content/themes/santocafe/inc/ab-testing.php`

- [ ] **Step 1: Agregar el hook al final del archivo**

```php
/**
 * Deja la variante disponible en el dataLayer en cada carga de página
 * (no solo en la home) para poder cruzarla con otros datos en GA4 más
 * adelante. Es tracking adicional — el panel de wp-admin es la forma
 * principal de ver resultados, esto no hace falta para usarlo.
 */
add_action( 'wp_head', function (): void {
    if ( is_admin() ) {
        return;
    }

    $variant = isset( $_COOKIE[ SC_AB_COOKIE ] ) ? sanitize_text_field( wp_unslash( $_COOKIE[ SC_AB_COOKIE ] ) ) : '';
    if ( ! in_array( $variant, [ 'control', 'compact' ], true ) ) {
        return;
    }
    ?>
<script>
window.dataLayer = window.dataLayer || [];
dataLayer.push({ event: 'sc_ab_ready', ab_test: 'catalog_card', ab_variant: '<?php echo esc_js( $variant ); ?>' });
</script>
    <?php
}, 2 );
```

- [ ] **Step 2: Verificar sintaxis**

```bash
"/Users/fede/Library/Application Support/Local/lightning-services/php-8.2.29+0/bin/darwin-arm64/bin/php" -l "/Users/fede/Documents/SantoCafe/apps/web/app/public/wp-content/themes/santocafe/inc/ab-testing.php"
```
Expected: `No syntax errors detected`.

- [ ] **Step 3: Verificar el push en el HTML**

```bash
rm -f /tmp/ab_test_cookies.txt
curl -s -c /tmp/ab_test_cookies.txt "http://santocafe.local/" -o /dev/null
curl -s -b /tmp/ab_test_cookies.txt "http://santocafe.local/" | grep "sc_ab_ready"
rm -f /tmp/ab_test_cookies.txt
```
Expected: una línea con `dataLayer.push({ event: 'sc_ab_ready', ab_test: 'catalog_card', ab_variant: '...' });`.

- [ ] **Step 4: Commit**

```bash
cd /Users/fede/Documents/SantoCafe
git add apps/web/app/public/wp-content/themes/santocafe/inc/ab-testing.php
git commit -m "feat(web): push de variante del test A/B al dataLayer"
```

---

### Task 5: Campo "Foto para tarjeta compacta" en el panel de producto

**Files:**
- Modify: `apps/web/app/public/wp-content/themes/santocafe/inc/product-meta.php`

- [ ] **Step 1: Encolar el media uploader de WordPress solo en la pantalla de edición de producto**

Agregar cerca del inicio de `inc/product-meta.php`, después de la sección 1 (registro de atributos):

```php
// ============================================================
// 1.5 Encolar el selector de medios de WP en la pantalla de producto
//     (lo usa el campo "Foto para tarjeta compacta" del test A/B).
// ============================================================
add_action( 'admin_enqueue_scripts', function ( string $hook ): void {
    if ( 'post.php' !== $hook && 'post-new.php' !== $hook ) {
        return;
    }
    if ( 'product' !== get_post_type() ) {
        return;
    }
    wp_enqueue_media();
} );
```

- [ ] **Step 2: Agregar el campo al panel, dentro de la sección "3. Render meta fields inside the panel"**

En la función que renderiza `sc_cafe_product_data`, después del bloque de "Perfil (1 – 5)" y antes del cierre `</div>` del panel:

```php
        <h4 style="padding:12px 12px 4px; font-size:13px; color:#555;">Test A/B — Tarjeta de catálogo</h4>

        <?php
        $sc_card_photo_id = (int) get_post_meta( $id, '_sc_card_photo', true );
        ?>
        <p class="form-field">
            <label for="_sc_card_photo_button">Foto para tarjeta compacta</label>
            <span class="sc-card-photo-preview" style="display:block;margin:6px 0;">
                <?php if ( $sc_card_photo_id ) : ?>
                    <?php echo wp_get_attachment_image( $sc_card_photo_id, [ 100, 100 ], false, [ 'style' => 'border-radius:8px;object-fit:cover;' ] ); ?>
                <?php endif; ?>
            </span>
            <input type="hidden" id="_sc_card_photo" name="_sc_card_photo" value="<?php echo esc_attr( $sc_card_photo_id ); ?>">
            <button type="button" id="_sc_card_photo_button" class="button sc-card-photo-upload">Subir imagen</button>
            <button type="button" class="button sc-card-photo-remove" <?php echo $sc_card_photo_id ? '' : 'style="display:none;"'; ?>>Quitar</button>
            <span class="description" style="display:block;margin-top:4px;">
                Foto alternativa para la tarjeta chica de la home (test A/B). Si no cargás una, se usa la foto normal del producto.
            </span>
        </p>
        <script>
        jQuery(function ($) {
            var frame;
            $('.sc-card-photo-upload').on('click', function (e) {
                e.preventDefault();
                if (frame) { frame.open(); return; }
                frame = wp.media({
                    title: 'Elegir foto para la tarjeta compacta',
                    button: { text: 'Usar esta foto' },
                    multiple: false
                });
                frame.on('select', function () {
                    var attachment = frame.state().get('selection').first().toJSON();
                    $('#_sc_card_photo').val(attachment.id);
                    $('.sc-card-photo-preview').html(
                        '<img src="' + attachment.url + '" style="width:100px;height:100px;object-fit:cover;border-radius:8px;">'
                    );
                    $('.sc-card-photo-remove').show();
                });
                frame.open();
            });
            $('.sc-card-photo-remove').on('click', function (e) {
                e.preventDefault();
                $('#_sc_card_photo').val('');
                $('.sc-card-photo-preview').empty();
                $(this).hide();
            });
        });
        </script>
```

- [ ] **Step 3: Guardar el campo — agregar al `woocommerce_process_product_meta`**

En la función que ya guarda los otros campos (sección 4 del archivo), agregar antes del cierre `} );`:

```php
    if ( isset( $_POST['_sc_card_photo'] ) ) {
        update_post_meta( $post_id, '_sc_card_photo', absint( $_POST['_sc_card_photo'] ) );
    }
```

- [ ] **Step 4: Verificar sintaxis**

```bash
"/Users/fede/Library/Application Support/Local/lightning-services/php-8.2.29+0/bin/darwin-arm64/bin/php" -l "/Users/fede/Documents/SantoCafe/apps/web/app/public/wp-content/themes/santocafe/inc/product-meta.php"
```
Expected: `No syntax errors detected`.

- [ ] **Step 5: Verificar en el navegador**

Entrar a editar cualquier producto en wp-admin, pestaña "☕ Café". Confirmar que aparece la sección "Test A/B — Tarjeta de catálogo" con el botón "Subir imagen", que abre el selector de medios de WordPress, que al elegir una foto se ve la vista previa y aparece el botón "Quitar", y que al guardar el producto la foto queda guardada (recargar la pantalla de edición y confirmar que la vista previa sigue ahí).

- [ ] **Step 6: Commit**

```bash
cd /Users/fede/Documents/SantoCafe
git add apps/web/app/public/wp-content/themes/santocafe/inc/product-meta.php
git commit -m "feat(web): campo de foto alternativa para la tarjeta compacta del test A/B"
```

---

### Task 6: Template de la tarjeta compacta

**Files:**
- Create: `apps/web/app/public/wp-content/themes/santocafe/template-parts/product/card-compact.php`

- [ ] **Step 1: Crear el archivo**

```php
<?php
/**
 * Santo Café — Tarjeta compacta de catálogo (variante "compact" del
 * test A/B). Ver docs/superpowers/specs/2026-07-10-ab-test-tarjeta-catalogo-design.md
 *
 * Usado por: template-parts/home/section-catalog.php, solo cuando
 * sc_ab_get_variant() devuelve 'compact'.
 */
defined('ABSPATH') || exit;

global $product;

if ( ! $product || ! $product->is_visible() ) {
    return;
}

$id            = $product->get_id();
$card_photo_id = (int) sc_get_product_meta( $id, 'card_photo' );
$prices        = sc_product_weight_prices( $id );
$price_fmt     = sc_format_clp( (int) $prices['p250'] );
?>

<article <?php wc_product_class( 'product-card-compact', $product ); ?>>
    <a href="<?php the_permalink(); ?>" class="product-card-compact__link"
       aria-label="<?php echo esc_attr( 'Ver ficha de ' . get_the_title() ); ?>">

        <div class="product-card-compact__image-zone">
            <?php
            if ( $card_photo_id ) {
                echo wp_get_attachment_image( $card_photo_id, 'woocommerce_single', false, [
                    'class' => 'product-card-compact__image',
                    'alt'   => get_the_title(),
                ] );
            } elseif ( has_post_thumbnail() ) {
                the_post_thumbnail( 'woocommerce_single', [
                    'class' => 'product-card-compact__image',
                    'alt'   => get_the_title(),
                ] );
            } else {
                echo '<div class="product-card-compact__image product-card-compact__image--placeholder"></div>';
            }
            ?>

            <div class="product-card-compact__overlay">
                <span class="product-card-compact__name"><?php the_title(); ?></span>
                <span class="product-card-compact__price"><?php echo esc_html( $price_fmt ); ?></span>
            </div>

            <span class="btn btn--primary btn--sm product-card-compact__view" aria-hidden="true">Ver</span>
        </div>

    </a>
</article>
```

- [ ] **Step 2: Verificar sintaxis**

```bash
"/Users/fede/Library/Application Support/Local/lightning-services/php-8.2.29+0/bin/darwin-arm64/bin/php" -l "/Users/fede/Documents/SantoCafe/apps/web/app/public/wp-content/themes/santocafe/template-parts/product/card-compact.php"
```
Expected: `No syntax errors detected`.

- [ ] **Step 3: Commit**

```bash
cd /Users/fede/Documents/SantoCafe
git add apps/web/app/public/wp-content/themes/santocafe/template-parts/product/card-compact.php
git commit -m "feat(web): template de la tarjeta compacta del test A/B"
```

---

### Task 7: CSS de la tarjeta compacta + grilla mobile

**Files:**
- Create: `apps/web/app/public/wp-content/themes/santocafe/assets/css/_product-card-compact.css`
- Modify: `apps/web/app/public/wp-content/themes/santocafe/assets/css/main.css:25`

- [ ] **Step 1: Crear el archivo CSS**

```css
/* ============================================================
   Tarjeta compacta de catálogo — variante "compact" del test A/B
   Ver docs/superpowers/specs/2026-07-10-ab-test-tarjeta-catalogo-design.md
   ============================================================ */

.product-card-compact__link {
    display: block;
    text-decoration: none;
    color: inherit;
}

.product-card-compact__image-zone {
    position: relative;
    aspect-ratio: 1 / 1;
    border-radius: var(--radius-card);
    overflow: hidden;
    background: var(--color-oscuro);
}

.product-card-compact__image {
    width: 100%;
    height: 100%;
    object-fit: cover;
    display: block;
    transition: transform var(--transition-slow);
}

.product-card-compact__link:hover .product-card-compact__image {
    transform: scale(1.04);
}

.product-card-compact__image--placeholder {
    width: 100%;
    height: 100%;
    background: linear-gradient(135deg, #2a1f1a 0%, #1a1310 100%);
}

.product-card-compact__overlay {
    position: absolute;
    bottom: 0;
    left: 0;
    right: 0;
    padding: var(--spacing-lg) var(--spacing-sm) var(--spacing-sm);
    background: linear-gradient(transparent, rgba(0, 0, 0, 0.75));
    display: flex;
    flex-direction: column;
    gap: 2px;
    pointer-events: none;
}

.product-card-compact__name {
    color: #fff;
    font-size: var(--font-size-sm);
    font-weight: 600;
}

.product-card-compact__price {
    color: var(--color-dorado);
    font-size: var(--font-size-sm);
    font-weight: 700;
}

.product-card-compact__view {
    position: absolute;
    bottom: var(--spacing-sm);
    right: var(--spacing-sm);
    pointer-events: none;
}

/* Grilla de catálogo en modo compacto: 2 columnas en mobile en vez de 1
   (section-catalog.php agrega la clase --compact al contenedor cuando
   la variante activa es 'compact'). */
@media (max-width: 600px) {
    .catalog-section__grid--compact {
        grid-template-columns: 1fr 1fr;
    }
}
```

- [ ] **Step 2: Registrar el `@import` en `main.css`**

En `assets/css/main.css`, agregar la línea después de `@import url('_product-card.css');` (línea 25):

```css
@import url('_product-card.css');
@import url('_product-card-compact.css');
```

- [ ] **Step 3: Commit**

```bash
cd /Users/fede/Documents/SantoCafe
git add apps/web/app/public/wp-content/themes/santocafe/assets/css/_product-card-compact.css apps/web/app/public/wp-content/themes/santocafe/assets/css/main.css
git commit -m "feat(web): estilos de la tarjeta compacta y grilla mobile de 2 columnas"
```

---

### Task 8: Conectar la variante en `section-catalog.php`

**Files:**
- Modify: `apps/web/app/public/wp-content/themes/santocafe/template-parts/home/section-catalog.php`

- [ ] **Step 1: Reemplazar el contenido completo del archivo**

```php
<?php
defined('ABSPATH') || exit;

if ( ! function_exists( 'wc_get_template_part' ) ) {
    return; // WooCommerce not active
}

$products = new WP_Query( [
    'post_type'      => 'product',
    'post_status'    => 'publish',
    'posts_per_page' => 8,
    'orderby'        => 'menu_order title',
    'order'          => 'ASC',
] );

$sc_ab_variant = function_exists( 'sc_ab_get_variant' ) ? sc_ab_get_variant() : 'control';
$sc_grid_class = 'catalog-section__grid' . ( 'compact' === $sc_ab_variant ? ' catalog-section__grid--compact' : '' );
?>

<section class="catalog-section" id="catalogo" aria-label="Catálogo de productos">
    <div class="container">

        <header class="catalog-section__header">
            <h2 class="catalog-section__title">
                Nuestros <span class="text-dorado">Cafés</span>
            </h2>
            <p class="catalog-section__subtitle">Orígenes de especialidad.</p>
        </header>

        <?php if ( $products->have_posts() ) : ?>

        <div class="<?php echo esc_attr( $sc_grid_class ); ?>">
            <?php
            while ( $products->have_posts() ) {
                $products->the_post();
                if ( 'compact' === $sc_ab_variant ) {
                    get_template_part( 'template-parts/product/card-compact' );
                } else {
                    wc_get_template_part( 'content', 'product' );
                }
            }
            wp_reset_postdata();
            ?>
        </div>

        <?php else : ?>
        <p class="catalog-section__empty">
            Los productos estarán disponibles pronto. ¡Vuelve en breve!
        </p>
        <?php endif; ?>

    </div>
</section>
```

- [ ] **Step 2: Verificar sintaxis**

```bash
"/Users/fede/Library/Application Support/Local/lightning-services/php-8.2.29+0/bin/darwin-arm64/bin/php" -l "/Users/fede/Documents/SantoCafe/apps/web/app/public/wp-content/themes/santocafe/template-parts/home/section-catalog.php"
```
Expected: `No syntax errors detected`.

- [ ] **Step 3: Verificar las dos variantes contra el sitio local**

```bash
# Forzar variante 'control' con una cookie manual y confirmar que se ve la tarjeta grande de siempre.
curl -s -b "sc_ab_card=control" "http://santocafe.local/" | grep -c "product-card__weights"

# Forzar variante 'compact' y confirmar que se ve la tarjeta chica.
curl -s -b "sc_ab_card=compact" "http://santocafe.local/" | grep -c "product-card-compact__view"
```
Expected: el primer comando devuelve un número > 0 (la grilla actual tiene selector de formato); el segundo también devuelve un número > 0 (aparece el botón "Ver" de la tarjeta compacta).

- [ ] **Step 4: Confirmar que el carrito vacío sigue mostrando siempre la tarjeta actual**

`cart-empty.php` (productos sugeridos cuando el carrito está vacío) se renderiza dentro del fragmento del mini-carrito, vía `mini-cart.php:209` → `get_template_part('template-parts/cart/cart-empty')` → `wc_get_template_part('content','product')`. Se verifica pegándole directo al endpoint de fragmentos que ya usa el tema, con un carrito garantizado vacío (cookie jar nuevo, sin agregar nada):

```bash
rm -f /tmp/ab_test_cart_empty.txt
curl -s -b "sc_ab_card=compact" -c /tmp/ab_test_cart_empty.txt "http://santocafe.local/?wc-ajax=get_refreshed_fragments" | grep -c "product-card-compact"
rm -f /tmp/ab_test_cart_empty.txt
```
Expected: `0` (cart-empty.php no debe verse afectado por la cookie de variante, porque llama a `wc_get_template_part('content','product')` directamente sin pasar por la lógica de `section-catalog.php`).

- [ ] **Step 5: Commit**

```bash
cd /Users/fede/Documents/SantoCafe
git add apps/web/app/public/wp-content/themes/santocafe/template-parts/home/section-catalog.php
git commit -m "feat(web): conectar la tarjeta compacta al test A/B en la home"
```

---

### Task 9: Verificación visual final + bump de versión

**Files:**
- Modify: `apps/web/app/public/wp-content/themes/santocafe/style.css`

- [ ] **Step 1: Bumpear la versión del tema**

En `style.css`, incrementar el número de versión (revisar el valor actual antes de editar, ya que se bumpea en cada tanda de cambios a lo largo del proyecto).

- [ ] **Step 2: Verificar visualmente ambas variantes en el navegador**

Con `http://santocafe.local/` abierto:
1. Borrar la cookie `sc_ab_card` (DevTools > Application > Cookies), recargar varias veces hasta ver la tarjeta compacta (o forzarla manualmente escribiendo `document.cookie = "sc_ab_card=compact; path=/"` en la consola y recargando).
2. Confirmar: grilla de 4 columnas en desktop, imagen con nombre/precio superpuestos abajo a la izquierda y botón "Ver" abajo a la derecha, sin badges ni selector de formato.
3. Reducir el ancho de la ventana a menos de 600px — confirmar que pasa a 2 columnas (no 1).
4. Cambiar la cookie a `sc_ab_card=control` y recargar — confirmar que se ve la tarjeta de siempre, sin cambios.

- [ ] **Step 3: Commit**

```bash
cd /Users/fede/Documents/SantoCafe
git add apps/web/app/public/wp-content/themes/santocafe/style.css
git commit -m "chore(web): bump de versión — test A/B tarjeta de catálogo"
```

---

## Fuera de alcance de este plan (documentado, no implementar todavía)

- Configuración manual de GTM/GA4 (crear la dimensión personalizada `ab_variant`) — la hace el usuario en la consola de Google Tag Manager cuando quiera usar ese tracking adicional, no requiere código.
- Cierre del test (hardcodear la variante ganadora y borrar la lógica de A/B) — tarea aparte, a pedido, una vez que haya datos suficientes en el panel.
