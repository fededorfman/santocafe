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
