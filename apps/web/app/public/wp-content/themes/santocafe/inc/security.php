<?php
defined('ABSPATH') || exit;

/**
 * Hardening de seguridad para Santo Café.
 * Endurecimiento portable que viaja con el tema (no depende del servidor):
 * XML-RPC, pingback, enumeración de usuarios vía REST, versión de WP y
 * cabeceras de seguridad básicas.
 */

// ============================================================
// XML-RPC — desactivado (vector de fuerza bruta y amplificación)
// ============================================================
add_filter( 'xmlrpc_enabled', '__return_false' );

// Quitar el método pingback.ping del XML-RPC (DDoS por amplificación)
add_filter( 'xmlrpc_methods', function ( array $methods ): array {
    unset( $methods['pingback.ping'], $methods['pingback.extensions.getPingbacks'] );
    return $methods;
} );

// Quitar la cabecera X-Pingback que anuncia el endpoint
add_filter( 'wp_headers', function ( array $headers ): array {
    unset( $headers['X-Pingback'] );
    return $headers;
} );

// ============================================================
// Ocultar la versión de WordPress (huella para exploits dirigidos)
// ============================================================
remove_action( 'wp_head', 'wp_generator' );
add_filter( 'the_generator', '__return_empty_string' );

// ============================================================
// REST API — bloquear enumeración de usuarios para anónimos.
// Mantiene /wp/v2/users disponible para usuarios logueados (admin).
// ============================================================
add_filter( 'rest_endpoints', function ( array $endpoints ): array {
    if ( is_user_logged_in() ) {
        return $endpoints;
    }
    unset( $endpoints['/wp/v2/users'] );
    unset( $endpoints['/wp/v2/users/(?P<id>[\d]+)'] );
    return $endpoints;
} );

// ============================================================
// Cabeceras de seguridad básicas (sin CSP para no romper
// estilos inline / Google Fonts / Flow).
// ============================================================
add_action( 'send_headers', function (): void {
    if ( is_admin() ) {
        return;
    }
    header( 'X-Content-Type-Options: nosniff' );
    header( 'X-Frame-Options: SAMEORIGIN' );
    header( 'Referrer-Policy: strict-origin-when-cross-origin' );
} );

// ============================================================
// Cache: nunca servir una respuesta cacheada (LiteSpeed) al bot de
// Microsoft Clarity, para que no capture una versión vieja de la
// página (CSS desactualizado) al generar sus grabaciones/heatmaps.
// Usa el hook oficial de LiteSpeed Cache — si el plugin no está
// activo, do_action() no hace nada (no-op seguro).
// ============================================================
add_action( 'init', function (): void {
    $user_agent = isset( $_SERVER['HTTP_USER_AGENT'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) ) : '';
    if ( '' !== $user_agent && false !== stripos( $user_agent, 'Clarity-Bot' ) ) {
        do_action( 'litespeed_control_set_nocache', 'clarity-bot' );
    }
} );

// ============================================================
// Enumeración de usuarios por ?author=N y archivas de autor.
// Complementa el bloqueo REST: /?author=1 normalmente redirige a
// /author/<login>/ y filtra el nombre de usuario. Para anónimos lo
// cortamos mandando al home (prioridad 0 → corre antes que
// redirect_canonical, que haría la redirección reveladora).
// ============================================================
add_action( 'template_redirect', function (): void {
    if ( is_user_logged_in() ) {
        return;
    }
    if ( isset( $_GET['author'] ) || is_author() ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        wp_safe_redirect( home_url( '/' ), 301 );
        exit;
    }
}, 0 );
