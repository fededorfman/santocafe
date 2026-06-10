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
