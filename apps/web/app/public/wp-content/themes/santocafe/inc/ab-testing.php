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
    update_option( $option_key, (int) get_option( $option_key, 0 ) + 1, false );
}
add_action( 'template_redirect', 'sc_ab_maybe_assign_variant' );

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
    $_COOKIE[ SC_AB_CONVERTED_COOKIE ] = '1'; // por si el hook dispara más de una vez en este mismo request

    $option_key = ( 'control' === $variant ) ? 'sc_ab_conv_control' : 'sc_ab_conv_compact';
    update_option( $option_key, (int) get_option( $option_key, 0 ) + 1, false );
}
add_action( 'woocommerce_add_to_cart', 'sc_ab_track_conversion' );

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

    $sc_ab_reset_done = false;
    if ( isset( $_POST['sc_ab_reset'] ) && check_admin_referer( 'sc_ab_reset_action', 'sc_ab_reset_nonce' ) ) {
        foreach ( [ 'sc_ab_views_control', 'sc_ab_views_compact', 'sc_ab_conv_control', 'sc_ab_conv_compact' ] as $option_key ) {
            delete_option( $option_key );
        }
        $sc_ab_reset_done = true;
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
        <?php if ( $sc_ab_reset_done ) : ?>
        <div class="notice notice-success"><p>Contadores reiniciados.</p></div>
        <?php endif; ?>
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
