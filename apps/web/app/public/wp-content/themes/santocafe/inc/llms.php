<?php
/**
 * llms.txt — guía del sitio para LLMs (https://llmstxt.org/)
 *
 * Genera /llms.txt dinámicamente desde los datos reales del sitio:
 * resumen del negocio, cafés (con país, SCA, proceso, notas, precio),
 * guías y páginas legales. Se mantiene solo cuando se agregan productos.
 *
 * Reemplaza al llms.txt del plugin de Hostinger (desactivar el de Hostinger
 * para que esta versión, más rica y sin páginas de carrito/cuenta, sea la única).
 *
 * Loaded by functions.php.
 */
defined( 'ABSPATH' ) || exit;

// ------------------------------------------------------------
// Ruta /llms.txt
// ------------------------------------------------------------
add_action( 'init', static function (): void {
	add_rewrite_rule( '^llms\.txt$', 'index.php?sc_llms=1', 'top' );
} );

add_filter( 'query_vars', static function ( array $vars ): array {
	$vars[] = 'sc_llms';
	return $vars;
} );

add_action( 'template_redirect', static function (): void {
	if ( ! get_query_var( 'sc_llms' ) ) {
		return;
	}
	nocache_headers();
	header( 'Content-Type: text/plain; charset=utf-8' );
	header( 'X-Robots-Tag: noindex' );
	echo sc_llms_build(); // phpcs:ignore — texto plano, ya escapado/limpio
	exit;
} );

// Flush de reglas al activar el tema (para que /llms.txt resuelva sin re-guardar permalinks).
add_action( 'after_switch_theme', static function (): void {
	add_rewrite_rule( '^llms\.txt$', 'index.php?sc_llms=1', 'top' );
	flush_rewrite_rules();
} );

// ------------------------------------------------------------
// Constructor del documento
// ------------------------------------------------------------
function sc_llms_build(): string {
	$nl  = "\n";
	$out = '# Santo Café' . $nl . $nl;

	// Resumen del negocio (blockquote)
	$out .= '> Tienda online de café de especialidad en Chile. Cafés single origin de '
		. 'Latinoamérica (Colombia, Perú, Bolivia, Brasil, Guatemala y Costa Rica), con '
		. 'puntaje SCA 83–85, tueste reciente, en grano o molido (espresso, italiana, filtro). '
		. 'Envío a domicilio en la Región Metropolitana de Santiago.' . $nl . $nl;

	$out .= '- Moneda: CLP · País: Chile · Envío gratis desde $50.000' . $nl;
	$out .= '- Contacto: hola@santocafe.cl · WhatsApp +56 9 5141 4791 · Instagram @santocafespecialtycoffee' . $nl . $nl;

	// --- Cafés (con datos de especialidad) ---
	if ( function_exists( 'wc_get_products' ) ) {
		$products = wc_get_products( [
			'status'  => 'publish',
			'limit'   => -1,
			'orderby' => 'title',
			'order'   => 'ASC',
		] );

		if ( $products ) {
			$out .= '## Cafés' . $nl . $nl;
			foreach ( $products as $p ) {
				$id   = $p->get_id();
				$bits = [];

				$map = [
					'pais'       => '',
					'sca_score'  => 'SCA ',
					'proceso'    => 'proceso ',
					'altitud'    => '',
					'notas_cata' => 'notas: ',
				];
				foreach ( $map as $key => $prefix ) {
					$val = function_exists( 'sc_get_product_meta' ) ? sc_get_product_meta( $id, $key ) : '';
					if ( '' !== (string) $val && null !== $val ) {
						if ( 'altitud' === $key ) {
							$val .= ' msnm';
						}
						$bits[] = $prefix . $val;
					}
				}

				$desc = $bits
					? implode( ', ', $bits )
					: wp_trim_words( wp_strip_all_tags( $p->get_short_description() ), 25, '' );

				$price = '';
				$pr    = $p->get_price();
				if ( is_numeric( $pr ) && (float) $pr > 0 ) {
					$price = ' · desde $' . number_format( (float) $pr, 0, ',', '.' );
				}

				$out .= sprintf(
					'- [%s](%s): %s%s' . $nl,
					$p->get_name(),
					get_permalink( $id ),
					sc_llms_clean( $desc ),
					$price
				);
			}
			$out .= $nl;
		}
	}

	// --- Guías de café (posts) ---
	$posts = get_posts( [
		'numberposts' => -1,
		'orderby'     => 'title',
		'order'       => 'ASC',
	] );
	if ( $posts ) {
		$out .= '## Guías de café' . $nl . $nl;
		foreach ( $posts as $post ) {
			$d    = sc_llms_clean( get_the_excerpt( $post ) );
			$out .= sprintf( '- [%s](%s): %s' . $nl, get_the_title( $post ), get_permalink( $post ), $d );
		}
		$out .= $nl;
	}

	// --- Tienda ---
	if ( function_exists( 'wc_get_page_id' ) ) {
		$shop = wc_get_page_id( 'shop' );
		if ( $shop > 0 ) {
			$out .= '## Tienda' . $nl . $nl;
			$out .= sprintf( '- [Ver todos los cafés](%s)' . $nl . $nl, get_permalink( $shop ) );
		}
	}

	// --- Legal (limpio, sin entidades) ---
	$legal = [
		'aviso-legal'            => 'Aviso legal',
		'politica-de-privacidad' => 'Política de privacidad',
		'politica-de-cookies'    => 'Política de cookies',
		'condiciones-de-venta'   => 'Condiciones de venta',
	];
	$legal_lines = '';
	foreach ( $legal as $slug => $label ) {
		$pg = get_page_by_path( $slug );
		if ( $pg ) {
			$legal_lines .= sprintf( '- [%s](%s)' . $nl, $label, get_permalink( $pg ) );
		}
	}
	if ( $legal_lines ) {
		$out .= '## Legal' . $nl . $nl . $legal_lines;
	}

	return $out;
}

/**
 * Normaliza texto para una línea Markdown: decodifica entidades, quita HTML,
 * colapsa espacios y saltos de línea.
 */
function sc_llms_clean( string $text ): string {
	$text = wp_strip_all_tags( $text );
	$text = html_entity_decode( $text, ENT_QUOTES | ENT_HTML5, 'UTF-8' );
	$text = preg_replace( '/\s+/u', ' ', $text );
	return trim( (string) $text );
}
