# Framework genérico de tests A/B — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Generalizar `inc/ab-testing.php`, hoy hardcodeado a un único test (tarjeta de catálogo), en un framework que soporte múltiples tests A/B registrados en código, cada uno con activo/inactivo y % de tráfico por variante editables desde un panel único en wp-admin ("Tests A/B"), sin cambiar el comportamiento visible del test existente.

**Architecture:** Un registro en memoria (`global $sc_ab_registered_tests`) donde cada test se declara con `sc_ab_register_test($key, $label, $variants)`. Las funciones que antes eran específicas de la tarjeta (`sc_ab_get_variant()`, `sc_ab_maybe_assign_variant()`, `sc_ab_track_conversion()`) pasan a tomar `$test_key` como parámetro y a leer pesos/estado desde `wp_options` (`sc_ab_active_{test}`, `sc_ab_weights_{test}`) con fallback a los defaults del código. El panel de wp-admin recorre todos los tests registrados. El test de la tarjeta se re-registra al final del mismo archivo, con su wiring de hooks específico (dónde se dispara, qué cuenta como conversión) igual que antes.

**Tech Stack:** PHP 8 (WordPress hooks/cookies nativos), sin plugins ni librerías nuevas.

**Nota sobre testing:** sin suite automatizada (WordPress theme sin PHPUnit). Verificación vía `php -l`, scripts que bootean `wp-load.php` contra la base local, y `curl`/navegador contra `http://santocafe.local`. Rutas usadas en este plan:
- Binario PHP: `/Users/fede/Library/Application Support/Local/lightning-services/php-8.2.29+0/bin/darwin-arm64/bin/php`
- php.ini del sitio: `/Users/fede/Library/Application Support/Local/run/X9GxT_pm-/conf/php/php.ini`
- MySQL: binario `/Users/fede/Library/Application Support/Local/lightning-services/mysql-8.4.0/bin/darwin-arm64/bin/mysql`, socket `/Users/fede/Library/Application Support/Local/run/X9GxT_pm-/mysql/mysqld.sock`, usuario/clave `root`/`root`, base `local`
- Scratchpad para scripts de prueba: `/private/tmp/claude-501/-Users-fede-Documents-SantoCafe/84ca3a79-655e-4964-97f1-4cd93c722c70/scratchpad/`

**Estado actual verificado antes de escribir este plan:** `style.css` está en versión `0.1.257`. Solo `inc/ab-testing.php` referencia el esquema viejo de nombres (`sc_ab_views_control`, `sc-ab-catalog`, etc.) — ningún otro archivo del tema depende de ellos, así que se pueden reemplazar sin dejar referencias rotas en otro lado.

---

### Task 1: Reescribir `inc/ab-testing.php` como framework multi-test

**Files:**
- Modify (reemplazo completo): `apps/web/app/public/wp-content/themes/santocafe/inc/ab-testing.php`

- [ ] **Step 1: Reemplazar todo el contenido del archivo**

```php
<?php
defined('ABSPATH') || exit;

/**
 * Santo Café — Framework de tests A/B (multi-test).
 *
 * Ver docs/superpowers/specs/2026-07-10-framework-tests-ab-design.md
 */

const SC_AB_COOKIE_DAYS = 30;

// ============================================================
// 1. Registro de tests
// ============================================================

/**
 * Registra un test A/B. Debe llamarse en la carga del tema (código a
 * nivel de archivo), antes de que corra cualquier hook que lo use.
 *
 * @param string $key      Clave única del test (se usa en cookies y en wp_options).
 * @param string $label    Nombre visible en el panel de wp-admin.
 * @param array  $variants Variantes: clave => ['label' => string, 'weight' => int].
 *                          La primera clave del array es la variante "control"
 *                          (la que ven admins y la que se usa si el test está
 *                          inactivo). Los weights por defecto deben sumar 100.
 */
function sc_ab_register_test( string $key, string $label, array $variants ): void {
    global $sc_ab_registered_tests;
    $sc_ab_registered_tests[ $key ] = [
        'label'    => $label,
        'variants' => $variants,
    ];
}

/**
 * @return array<string,array{label:string,variants:array}>
 */
function sc_ab_get_registered_tests(): array {
    global $sc_ab_registered_tests;
    return $sc_ab_registered_tests ?? [];
}

function sc_ab_get_test( string $test_key ): ?array {
    $tests = sc_ab_get_registered_tests();
    return $tests[ $test_key ] ?? null;
}

function sc_ab_first_variant( string $test_key ): string {
    $test = sc_ab_get_test( $test_key );
    if ( ! $test || empty( $test['variants'] ) ) {
        return '';
    }
    $keys = array_keys( $test['variants'] );
    return $keys[0];
}

// ============================================================
// 2. Activo/inactivo y pesos (editables desde wp-admin)
// ============================================================

function sc_ab_is_active( string $test_key ): bool {
    return '0' !== get_option( 'sc_ab_active_' . $test_key, '1' );
}

/**
 * Pesos efectivos de las variantes de un test: los guardados en
 * wp_options si el sitio los editó desde el panel, si no los definidos
 * en el registro de código.
 *
 * @return array<string,int> variante => peso
 */
function sc_ab_get_weights( string $test_key ): array {
    $test = sc_ab_get_test( $test_key );
    if ( ! $test ) {
        return [];
    }

    $saved = get_option( 'sc_ab_weights_' . $test_key, [] );

    $weights = [];
    foreach ( $test['variants'] as $variant_key => $data ) {
        $weights[ $variant_key ] = ( is_array( $saved ) && isset( $saved[ $variant_key ] ) )
            ? (int) $saved[ $variant_key ]
            : (int) $data['weight'];
    }
    return $weights;
}

/**
 * Sortea una variante según los pesos efectivos del test (sorteo
 * ponderado: funciona igual para 2 variantes que para 5).
 */
function sc_ab_pick_weighted_variant( string $test_key ): string {
    $weights = sc_ab_get_weights( $test_key );
    $total   = array_sum( $weights );
    if ( $total <= 0 ) {
        return sc_ab_first_variant( $test_key );
    }

    $roll       = wp_rand( 1, $total );
    $cumulative = 0;
    foreach ( $weights as $variant_key => $weight ) {
        $cumulative += $weight;
        if ( $roll <= $cumulative ) {
            return $variant_key;
        }
    }

    return sc_ab_first_variant( $test_key ); // fallback defensivo, no debería llegar acá
}

// ============================================================
// 3. Asignación de variante y tracking (genéricas, por test)
// ============================================================

/**
 * Devuelve la variante del visitante actual para un test. Si el test
 * está inactivo, o el visitante es admin con edit_posts, siempre
 * devuelve la primera variante (la de "control").
 */
function sc_ab_get_variant( string $test_key ): string {
    $test = sc_ab_get_test( $test_key );
    if ( ! $test ) {
        return '';
    }
    if ( ! sc_ab_is_active( $test_key ) ) {
        return sc_ab_first_variant( $test_key );
    }
    if ( is_user_logged_in() && current_user_can( 'edit_posts' ) ) {
        return sc_ab_first_variant( $test_key );
    }

    $cookie_name = 'sc_ab_' . $test_key;
    $cookie      = isset( $_COOKIE[ $cookie_name ] ) ? sanitize_text_field( wp_unslash( $_COOKIE[ $cookie_name ] ) ) : '';

    return array_key_exists( $cookie, $test['variants'] ) ? $cookie : sc_ab_first_variant( $test_key );
}

/**
 * Si el test está activo y el visitante todavía no tiene cookie para
 * él, sortea una variante (según los pesos efectivos), la guarda 30
 * días y suma la vista al contador correspondiente. Cada test decide
 * desde qué hook llamar a esto (dónde se dispara la asignación).
 */
function sc_ab_maybe_assign_variant( string $test_key ): void {
    $test = sc_ab_get_test( $test_key );
    if ( ! $test ) {
        return;
    }
    if ( ! sc_ab_is_active( $test_key ) ) {
        return;
    }
    if ( is_user_logged_in() && current_user_can( 'edit_posts' ) ) {
        return;
    }

    $cookie_name = 'sc_ab_' . $test_key;
    if ( isset( $_COOKIE[ $cookie_name ] ) ) {
        return;
    }

    $variant = sc_ab_pick_weighted_variant( $test_key );
    $expires = time() + SC_AB_COOKIE_DAYS * DAY_IN_SECONDS;

    setcookie( $cookie_name, $variant, $expires, COOKIEPATH ? COOKIEPATH : '/', COOKIE_DOMAIN );
    $_COOKIE[ $cookie_name ] = $variant; // disponible ya en este mismo request

    $option_key = 'sc_ab_views_' . $test_key . '_' . $variant;
    update_option( $option_key, (int) get_option( $option_key, 0 ) + 1, false );
}

/**
 * Suma una conversión para la variante del visitante en un test, una
 * sola vez por visitante — usa una cookie de "ya convertido" propia de
 * cada test para no contar de más. Cada test decide desde qué hook
 * llamar a esto (qué cuenta como conversión).
 */
function sc_ab_track_conversion( string $test_key ): void {
    $test = sc_ab_get_test( $test_key );
    if ( ! $test ) {
        return;
    }
    if ( ! sc_ab_is_active( $test_key ) ) {
        return;
    }
    if ( is_user_logged_in() && current_user_can( 'edit_posts' ) ) {
        return;
    }

    $converted_cookie = 'sc_ab_conv_' . $test_key;
    if ( isset( $_COOKIE[ $converted_cookie ] ) ) {
        return;
    }

    $cookie_name = 'sc_ab_' . $test_key;
    $variant     = isset( $_COOKIE[ $cookie_name ] ) ? sanitize_text_field( wp_unslash( $_COOKIE[ $cookie_name ] ) ) : '';
    if ( ! array_key_exists( $variant, $test['variants'] ) ) {
        return; // este visitante no está en el test
    }

    $expires = time() + SC_AB_COOKIE_DAYS * DAY_IN_SECONDS;
    setcookie( $converted_cookie, '1', $expires, COOKIEPATH ? COOKIEPATH : '/', COOKIE_DOMAIN );
    $_COOKIE[ $converted_cookie ] = '1'; // por si el hook dispara más de una vez en este mismo request

    $option_key = 'sc_ab_conv_' . $test_key . '_' . $variant;
    update_option( $option_key, (int) get_option( $option_key, 0 ) + 1, false );
}

// ============================================================
// 4. Panel de resultados: WooCommerce > Tests A/B
// ============================================================

add_action( 'admin_menu', function (): void {
    add_submenu_page(
        'woocommerce',
        'Tests A/B',
        'Tests A/B',
        'manage_woocommerce',
        'sc-ab-tests',
        'sc_ab_admin_page'
    );
} );

function sc_ab_admin_page(): void {
    if ( ! current_user_can( 'manage_woocommerce' ) ) {
        return;
    }

    $tests   = sc_ab_get_registered_tests();
    $notices = [];

    foreach ( $tests as $test_key => $test ) {
        if ( isset( $_POST[ 'sc_ab_save_' . $test_key ] )
            && check_admin_referer( 'sc_ab_save_action_' . $test_key, 'sc_ab_save_nonce_' . $test_key ) ) {

            $new_weights = [];
            $sum         = 0;
            foreach ( $test['variants'] as $variant_key => $data ) {
                $field = 'sc_ab_weight_' . $test_key . '_' . $variant_key;
                $val   = isset( $_POST[ $field ] ) ? max( 0, min( 100, (int) $_POST[ $field ] ) ) : 0;
                $new_weights[ $variant_key ] = $val;
                $sum += $val;
            }

            if ( 100 !== $sum ) {
                $notices[] = [
                    'type' => 'error',
                    'text' => 'Los porcentajes de "' . $test['label'] . '" deben sumar 100 (sumaron ' . $sum . '). No se guardó ningún cambio.',
                ];
            } else {
                update_option( 'sc_ab_weights_' . $test_key, $new_weights, false );
                update_option( 'sc_ab_active_' . $test_key, isset( $_POST[ 'sc_ab_active_' . $test_key ] ) ? '1' : '0', false );
                $notices[] = [
                    'type' => 'success',
                    'text' => 'Cambios guardados para "' . $test['label'] . '".',
                ];
            }
        }

        if ( isset( $_POST[ 'sc_ab_reset_' . $test_key ] )
            && check_admin_referer( 'sc_ab_reset_action_' . $test_key, 'sc_ab_reset_nonce_' . $test_key ) ) {

            foreach ( array_keys( $test['variants'] ) as $variant_key ) {
                delete_option( 'sc_ab_views_' . $test_key . '_' . $variant_key );
                delete_option( 'sc_ab_conv_' . $test_key . '_' . $variant_key );
            }
            $notices[] = [
                'type' => 'success',
                'text' => 'Contadores de "' . $test['label'] . '" reiniciados.',
            ];
        }
    }
    ?>
    <div class="wrap">
        <h1>Tests A/B</h1>

        <?php foreach ( $notices as $notice ) : ?>
        <div class="notice notice-<?php echo esc_attr( $notice['type'] ); ?>">
            <p><?php echo esc_html( $notice['text'] ); ?></p>
        </div>
        <?php endforeach; ?>

        <?php if ( empty( $tests ) ) : ?>
        <p>Todavía no hay tests A/B configurados.</p>
        <?php else : ?>

        <?php foreach ( $tests as $test_key => $test ) :
            $active  = sc_ab_is_active( $test_key );
            $weights = sc_ab_get_weights( $test_key );
        ?>
        <div class="card" style="max-width:640px;margin-top:16px;padding:16px 20px;">
            <h2 style="margin-top:0;"><?php echo esc_html( $test['label'] ); ?></h2>

            <form method="post">
                <?php wp_nonce_field( 'sc_ab_save_action_' . $test_key, 'sc_ab_save_nonce_' . $test_key ); ?>

                <p>
                    <label>
                        <input type="checkbox" name="sc_ab_active_<?php echo esc_attr( $test_key ); ?>" value="1" <?php checked( $active ); ?>>
                        Activo
                    </label>
                </p>

                <table class="widefat striped">
                    <thead>
                        <tr>
                            <th></th>
                            <th>Vistas</th>
                            <th>Agregaron al carrito</th>
                            <th>Conversión</th>
                            <th>%</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ( $test['variants'] as $variant_key => $variant ) :
                            $views = (int) get_option( 'sc_ab_views_' . $test_key . '_' . $variant_key, 0 );
                            $conv  = (int) get_option( 'sc_ab_conv_' . $test_key . '_' . $variant_key, 0 );
                            $rate  = $views > 0 ? round( $conv / $views * 100, 1 ) : 0;
                        ?>
                        <tr>
                            <td><strong><?php echo esc_html( $variant['label'] ); ?></strong></td>
                            <td><?php echo esc_html( $views ); ?></td>
                            <td><?php echo esc_html( $conv ); ?></td>
                            <td><?php echo esc_html( $rate ); ?>%</td>
                            <td>
                                <input type="number" min="0" max="100"
                                       name="sc_ab_weight_<?php echo esc_attr( $test_key . '_' . $variant_key ); ?>"
                                       value="<?php echo esc_attr( $weights[ $variant_key ] ?? 0 ); ?>"
                                       style="width:60px;">
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>

                <p style="margin-top:12px;">
                    <button type="submit" name="sc_ab_save_<?php echo esc_attr( $test_key ); ?>" value="1" class="button button-primary">
                        Guardar cambios
                    </button>
                </p>
            </form>

            <form method="post">
                <?php wp_nonce_field( 'sc_ab_reset_action_' . $test_key, 'sc_ab_reset_nonce_' . $test_key ); ?>
                <button type="submit" name="sc_ab_reset_<?php echo esc_attr( $test_key ); ?>" value="1" class="button"
                        onclick="return confirm('¿Reiniciar los contadores de <?php echo esc_js( $test['label'] ); ?>?');">
                    Reiniciar contadores
                </button>
            </form>
        </div>
        <?php endforeach; ?>

        <?php endif; ?>
    </div>
    <?php
}

// ============================================================
// 5. Push a dataLayer (GTM) — uno por cada test activo en el que el
//    visitante esté enrolado. Tracking adicional, opcional; el panel
//    de wp-admin es la forma principal de ver resultados.
// ============================================================

add_action( 'wp_head', function (): void {
    if ( is_admin() ) {
        return;
    }
    if ( defined( 'SC_DISABLE_ANALYTICS' ) && SC_DISABLE_ANALYTICS ) {
        return;
    }
    if ( is_user_logged_in() && current_user_can( 'edit_posts' ) ) {
        return;
    }

    $events = [];
    foreach ( sc_ab_get_registered_tests() as $test_key => $test ) {
        if ( ! sc_ab_is_active( $test_key ) ) {
            continue;
        }
        $cookie_name = 'sc_ab_' . $test_key;
        $variant     = isset( $_COOKIE[ $cookie_name ] ) ? sanitize_text_field( wp_unslash( $_COOKIE[ $cookie_name ] ) ) : '';
        if ( ! array_key_exists( $variant, $test['variants'] ) ) {
            continue;
        }
        $events[] = [ 'test' => $test_key, 'variant' => $variant ];
    }

    if ( empty( $events ) ) {
        return;
    }
    ?>
<script>
window.dataLayer = window.dataLayer || [];
<?php foreach ( $events as $event ) : ?>
dataLayer.push({ event: 'sc_ab_ready', ab_test: '<?php echo esc_js( $event['test'] ); ?>', ab_variant: '<?php echo esc_js( $event['variant'] ); ?>' });
<?php endforeach; ?>
</script>
    <?php
}, 2 );

// ============================================================
// 6. Tests registrados
// ============================================================

sc_ab_register_test( 'catalog_card', 'Tarjeta de Catálogo', [
    'control' => [ 'label' => 'Tarjeta actual', 'weight' => 50 ],
    'compact' => [ 'label' => 'Tarjeta chica',  'weight' => 50 ],
] );

add_action( 'template_redirect', function (): void {
    if ( ! is_front_page() ) {
        return;
    }
    sc_ab_maybe_assign_variant( 'catalog_card' );
} );

add_action( 'woocommerce_add_to_cart', function (): void {
    sc_ab_track_conversion( 'catalog_card' );
} );
```

- [ ] **Step 2: Verificar sintaxis**

```bash
"/Users/fede/Library/Application Support/Local/lightning-services/php-8.2.29+0/bin/darwin-arm64/bin/php" -l "/Users/fede/Documents/SantoCafe/apps/web/app/public/wp-content/themes/santocafe/inc/ab-testing.php"
```
Expected: `No syntax errors detected`.

- [ ] **Step 3: Probar el registro y la selección ponderada con un script que bootea WordPress**

Crear `/private/tmp/claude-501/-Users-fede-Documents-SantoCafe/84ca3a79-655e-4964-97f1-4cd93c722c70/scratchpad/test_ab_framework.php`:

```php
<?php
define( 'WP_USE_THEMES', false );
require '/Users/fede/Documents/SantoCafe/apps/web/app/public/wp-load.php';

// El test catalog_card ya está registrado por ab-testing.php al cargar el tema.
$test = sc_ab_get_test( 'catalog_card' );
echo "Test registrado: " . ( $test ? 'sí' : 'NO' ) . " (esperado: sí)\n";
echo "Primera variante: " . sc_ab_first_variant( 'catalog_card' ) . " (esperado: control)\n";

// Pesos por defecto (sin override en wp_options todavía).
delete_option( 'sc_ab_weights_catalog_card' );
$weights = sc_ab_get_weights( 'catalog_card' );
echo "Pesos default: control=" . $weights['control'] . " compact=" . $weights['compact'] . " (esperado: 50 / 50)\n";

// Override de pesos: 90/10 — sortear muchas veces y confirmar que la proporción se acerca a eso.
update_option( 'sc_ab_weights_catalog_card', [ 'control' => 90, 'compact' => 10 ], false );
$counts = [ 'control' => 0, 'compact' => 0 ];
for ( $i = 0; $i < 2000; $i++ ) {
    $counts[ sc_ab_pick_weighted_variant( 'catalog_card' ) ]++;
}
$pct_control = round( $counts['control'] / 2000 * 100, 1 );
echo "Con pesos 90/10, sorteos control=" . $pct_control . "% (esperado: cerca de 90%, no 50%)\n";

delete_option( 'sc_ab_weights_catalog_card' );

// Test inexistente: no debe romper nada.
echo "Variante de test inexistente: '" . sc_ab_get_variant( 'test_que_no_existe' ) . "' (esperado: '' vacío)\n";
```

- [ ] **Step 4: Correr el script**

```bash
"/Users/fede/Library/Application Support/Local/lightning-services/php-8.2.29+0/bin/darwin-arm64/bin/php" -c "/Users/fede/Library/Application Support/Local/run/X9GxT_pm-/conf/php/php.ini" "/private/tmp/claude-501/-Users-fede-Documents-SantoCafe/84ca3a79-655e-4964-97f1-4cd93c722c70/scratchpad/test_ab_framework.php" 2>&1 | grep -v "Xdebug\|Zend Engine\|imagick\|module API\|Warning: PHP Startup\|These options"
```
Expected:
```
Test registrado: sí (esperado: sí)
Primera variante: control (esperado: control)
Pesos default: control=50 compact=50 (esperado: 50 / 50)
Con pesos 90/10, sorteos control=XX.X% (esperado: cerca de 90%, no 50%)
Variante de test inexistente: '' (esperado: '' vacío)
```
(El `XX.X%` de la línea de 90/10 debe estar claramente arriba de 80%, no cerca de 50%.)

- [ ] **Step 5: Probar activo/inactivo y el guardado desde el panel (simulado)**

Crear `/private/tmp/claude-501/-Users-fede-Documents-SantoCafe/84ca3a79-655e-4964-97f1-4cd93c722c70/scratchpad/test_ab_active.php`:

```php
<?php
define( 'WP_USE_THEMES', false );
require '/Users/fede/Documents/SantoCafe/apps/web/app/public/wp-load.php';

delete_option( 'sc_ab_active_catalog_card' );
echo "Activo por defecto: " . ( sc_ab_is_active( 'catalog_card' ) ? 'sí' : 'no' ) . " (esperado: sí)\n";

update_option( 'sc_ab_active_catalog_card', '0', false );
$_COOKIE['sc_ab_catalog_card'] = 'compact'; // visitante que ya tenía la variante chica asignada
echo "Inactivo, con cookie 'compact': " . sc_ab_get_variant( 'catalog_card' ) . " (esperado: control)\n";

unset( $_COOKIE['sc_ab_catalog_card'] );
sc_ab_maybe_assign_variant( 'catalog_card' );
echo "Cookie tras maybe_assign con test inactivo: " . ( isset( $_COOKIE['sc_ab_catalog_card'] ) ? 'se asignó (MAL)' : 'no se asignó (bien)' ) . "\n";

// Con el test todavía inactivo, un visitante que YA tenía una variante
// asignada de antes (cookie de una visita previa) tampoco debe sumar
// conversión.
delete_option( 'sc_ab_conv_catalog_card_compact' );
$_COOKIE['sc_ab_catalog_card'] = 'compact';
unset( $_COOKIE['sc_ab_conv_catalog_card'] );
sc_ab_track_conversion( 'catalog_card' );
echo "Conversión sumada con test inactivo: " . ( (int) get_option( 'sc_ab_conv_catalog_card_compact', 0 ) ) . " (esperado: 0)\n";
delete_option( 'sc_ab_conv_catalog_card_compact' );

update_option( 'sc_ab_active_catalog_card', '1', false );
echo "Reactivado: " . ( sc_ab_is_active( 'catalog_card' ) ? 'sí' : 'no' ) . " (esperado: sí)\n";

delete_option( 'sc_ab_active_catalog_card' );
```

Correr:
```bash
"/Users/fede/Library/Application Support/Local/lightning-services/php-8.2.29+0/bin/darwin-arm64/bin/php" -c "/Users/fede/Library/Application Support/Local/run/X9GxT_pm-/conf/php/php.ini" "/private/tmp/claude-501/-Users-fede-Documents-SantoCafe/84ca3a79-655e-4964-97f1-4cd93c722c70/scratchpad/test_ab_active.php" 2>&1 | grep -v "Xdebug\|Zend Engine\|imagick\|module API\|Warning: PHP Startup\|These options"
```
Expected:
```
Activo por defecto: sí (esperado: sí)
Inactivo, con cookie 'compact': control (esperado: control)
Cookie tras maybe_assign con test inactivo: no se asignó (bien)
Conversión sumada con test inactivo: 0 (esperado: 0)
Reactivado: sí (esperado: sí)
```

- [ ] **Step 6: Borrar los scripts de prueba**

```bash
rm -f "/private/tmp/claude-501/-Users-fede-Documents-SantoCafe/84ca3a79-655e-4964-97f1-4cd93c722c70/scratchpad/test_ab_framework.php" "/private/tmp/claude-501/-Users-fede-Documents-SantoCafe/84ca3a79-655e-4964-97f1-4cd93c722c70/scratchpad/test_ab_active.php"
```

- [ ] **Step 7: Commit**

```bash
cd /Users/fede/Documents/SantoCafe
git add apps/web/app/public/wp-content/themes/santocafe/inc/ab-testing.php
git commit -m "feat(web): generalizar ab-testing.php a framework multi-test con % y activo/inactivo editables"
```

---

### Task 2: Actualizar el call site en `section-catalog.php`

**Files:**
- Modify: `apps/web/app/public/wp-content/themes/santocafe/template-parts/home/section-catalog.php`

- [ ] **Step 1: Cambiar la llamada a `sc_ab_get_variant()` para pasar la clave del test**

En el archivo, la línea:
```php
$sc_ab_variant = function_exists( 'sc_ab_get_variant' ) ? sc_ab_get_variant() : 'control';
```
cambia a:
```php
$sc_ab_variant = function_exists( 'sc_ab_get_variant' ) ? sc_ab_get_variant( 'catalog_card' ) : 'control';
```
Nada más en el archivo cambia — el resto de la lógica (grid class, elección de template) sigue igual, ya que sigue comparando contra los mismos strings `'control'`/`'compact'`.

- [ ] **Step 2: Verificar sintaxis**

```bash
"/Users/fede/Library/Application Support/Local/lightning-services/php-8.2.29+0/bin/darwin-arm64/bin/php" -l "/Users/fede/Documents/SantoCafe/apps/web/app/public/wp-content/themes/santocafe/template-parts/home/section-catalog.php"
```
Expected: `No syntax errors detected`.

- [ ] **Step 3: Verificar ambas variantes contra el sitio local**

```bash
curl -s -b "sc_ab_catalog_card=control" "http://santocafe.local/" | grep -c "product-card__weights"
curl -s -b "sc_ab_catalog_card=compact" "http://santocafe.local/" | grep -c "product-card-compact__view"
```
Expected: ambos números > 0. Notar que el nombre de la cookie cambió de `sc_ab_card` a `sc_ab_catalog_card` (ahora tiene el prefijo genérico `sc_ab_` + la clave del test).

- [ ] **Step 4: Commit**

```bash
cd /Users/fede/Documents/SantoCafe
git add apps/web/app/public/wp-content/themes/santocafe/template-parts/home/section-catalog.php
git commit -m "feat(web): usar la clave 'catalog_card' al leer la variante en la home"
```

---

### Task 3: Limpieza de opciones viejas, verificación final y bump de versión

**Files:**
- Modify: `apps/web/app/public/wp-content/themes/santocafe/style.css`

- [ ] **Step 1: Borrar las opciones huérfanas del esquema anterior**

Los `wp_options` con los nombres viejos (`sc_ab_views_control`, `sc_ab_views_compact`,
`sc_ab_conv_control`, `sc_ab_conv_compact`, `sc_ab_active_catalog_card` de las
pruebas del Task 1) quedaron sin uso — nadie los lee ni los escribe con el código
nuevo. Se confirmó antes de escribir este plan que no hay datos reales de
producción bajo estos nombres (test recién construido, todavía no desplegado).

```bash
MYSQL_BIN="/Users/fede/Library/Application Support/Local/lightning-services/mysql-8.4.0/bin/darwin-arm64/bin/mysql"
SOCK="/Users/fede/Library/Application Support/Local/run/X9GxT_pm-/mysql/mysqld.sock"
"$MYSQL_BIN" --socket="$SOCK" -u root -proot local -e "DELETE FROM wp_options WHERE option_name IN ('sc_ab_views_control','sc_ab_views_compact','sc_ab_conv_control','sc_ab_conv_compact');" 2>/dev/null
"$MYSQL_BIN" --socket="$SOCK" -u root -proot local -e "SELECT option_name FROM wp_options WHERE option_name LIKE 'sc_ab_%';" 2>/dev/null
```
Expected: la segunda consulta ya no debe listar `sc_ab_views_control` ni las otras
tres opciones viejas — solo debe verse lo que queda del test `catalog_card`
(`sc_ab_active_catalog_card` si el Task 1 lo dejó en `0`, `sc_ab_views_catalog_card_*`,
etc., si las pruebas anteriores generaron alguna). Si `sc_ab_active_catalog_card`
quedó en `0` por el Task 1 Step 5, restaurarlo:
```bash
"$MYSQL_BIN" --socket="$SOCK" -u root -proot local -e "DELETE FROM wp_options WHERE option_name = 'sc_ab_active_catalog_card';" 2>/dev/null
```
(borrarlo alcanza — el default de `sc_ab_is_active()` es `true` cuando la opción no existe).

- [ ] **Step 2: Verificar el panel de wp-admin en el navegador**

Entrar a `http://santocafe.local/wp-admin/admin.php?page=sc-ab-tests` logueado como
admin (el slug viejo `sc-ab-catalog` ya no existe — debe dar 404/pantalla de acceso
denegado si se visita). Confirmar:
- El título de la página dice "Tests A/B" (no "Test A/B Catálogo").
- Aparece una caja para "Tarjeta de Catálogo" con las dos variantes, el checkbox
  "Activo" tildado, y los campos de % mostrando 50 y 50.
- Cambiar los % a 70 y 30, click en "Guardar cambios" → aviso de éxito, los campos
  siguen mostrando 70/30 al recargar la página.
- Cambiar los % a 70 y 40 (que no suman 100), click en "Guardar cambios" → aviso de
  error mencionando que deben sumar 100, y los valores vuelven a mostrar 70/30 (el
  intento inválido no se guardó).
- Destildar "Activo", guardar → aviso de éxito. Confirmar en el sitio (`curl -s -b
  "sc_ab_catalog_card=compact" "http://santocafe.local/" | grep -c
  "product-card-compact__view"` debe dar `0` ahora, porque el test inactivo siempre
  muestra la tarjeta actual sin importar la cookie).
- Volver a tildar "Activo" y guardar.
- Click en "Reiniciar contadores" → aviso de éxito, la tabla vuelve a mostrar 0 en
  vistas/conversión para ambas variantes, **y los campos de % siguen mostrando 70/30
  y el checkbox "Activo" sigue tildado** (el reset solo debe tocar los contadores,
  no el estado activo/inactivo ni los pesos guardados).
- Con la pestaña de wp-admin abierta, abrir además `http://santocafe.local/` en otra
  pestaña, forzar la cookie `sc_ab_catalog_card` a `control` y después a `compact`
  (`document.cookie = "sc_ab_catalog_card=compact; path=/"` en la consola +
  recargar) y confirmar visualmente que ambas tarjetas se ven exactamente igual que
  antes de este proyecto — ningún cambio visible para el visitante, solo cambió el
  nombre de la cookie (de `sc_ab_card` a `sc_ab_catalog_card`).

- [ ] **Step 3: Restaurar los pesos a 50/50 tras la prueba manual del Step 2**

```bash
MYSQL_BIN="/Users/fede/Library/Application Support/Local/lightning-services/mysql-8.4.0/bin/darwin-arm64/bin/mysql"
SOCK="/Users/fede/Library/Application Support/Local/run/X9GxT_pm-/mysql/mysqld.sock"
"$MYSQL_BIN" --socket="$SOCK" -u root -proot local -e "DELETE FROM wp_options WHERE option_name IN ('sc_ab_weights_catalog_card','sc_ab_active_catalog_card');" 2>/dev/null
```
Esto hace que el test vuelva a usar los defaults del código (50/50, activo) — no
hace falta dejar un override guardado en la base para que el test siga funcionando
igual que antes de este proyecto.

- [ ] **Step 4: Bump de versión**

En `style.css`, incrementar el número de versión (revisar el valor actual — al
escribir este plan era `0.1.257` — antes de editar, por si hubo cambios en el medio).

- [ ] **Step 5: Commit**

```bash
cd /Users/fede/Documents/SantoCafe
git add apps/web/app/public/wp-content/themes/santocafe/style.css
git commit -m "chore(web): bump de versión — framework multi-test A/B"
```

---

## Fuera de alcance de este plan (documentado, no implementar todavía)

- Constructor de tests sin código (crear un test nuevo desde wp-admin, sin que
  Claude escriba código) — explícitamente rechazado en el brainstorming.
- Agregar/quitar variantes de un test existente desde el panel — la cantidad y
  definición de variantes sigue siendo código; el panel solo edita el % de las que
  ya existen.
- Un segundo test A/B real — este plan deja el framework listo para que el próximo
  test se registre con una llamada más a `sc_ab_register_test()` + su propio wiring
  de hooks, pero no crea ningún test nuevo todavía.
