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
