<?php
/**
 * SEO & GEO — Santo Café
 *
 * Meta description, Open Graph, Twitter cards, canonical,
 * keyword-optimised titles, JSON-LD schemas (Organization, WebSite,
 * LocalBusiness, Product enriched, BreadcrumbList, FAQPage),
 * noindex rules, sitemap pointer and robots.txt improvement.
 *
 * Loaded by functions.php. No SEO plugin required.
 */
defined( 'ABSPATH' ) || exit;

// ============================================================
// Config (static — computed once per request)
// ============================================================
function sc_seo_cfg(): array {
	static $cfg = null;
	if ( null !== $cfg ) {
		return $cfg;
	}
	$uri = get_template_directory_uri();
	$cfg = [
		'site_name'  => 'Santo Café',
		'tagline'    => 'Café de especialidad online en Chile · Envío en Santiago',
		'email'      => 'hola@santocafe.cl',
		'phone'      => '+56951414791',
		'instagram'  => 'https://www.instagram.com/santocafespecialtycoffee/',
		'whatsapp'   => 'https://wa.me/56951414791',
		// Default OG image — replace assets/images/og-default.jpg with a
		// branded 1200×630 PNG/JPG when available. Fallback: hero photo.
		'og_image'   => file_exists( get_template_directory() . '/assets/images/og-default.jpg' )
			? $uri . '/assets/images/og-default.jpg'
			: $uri . '/assets/images/hero.jpg',
		'og_w'       => 1200,
		'og_h'       => 630,
		'logo'       => $uri . '/assets/images/logo.png',
		'logo_w'     => 200,
		'logo_h'     => 70,
	];
	return $cfg;
}

// ============================================================
// 1. Keyword-optimised <title> tags
// ============================================================
add_filter( 'document_title_parts', function ( array $title ): array {
	$site = 'Santo Café';

	if ( is_front_page() ) {
		return [
			'title'   => 'Comprar Café de Especialidad Online en Chile',
			'tagline' => 'Santo Café · Envío en Región Metropolitana',
		];
	}

	if ( function_exists( 'is_shop' ) && is_shop() ) {
		unset( $title['tagline'] );
		$title['title'] = 'Cafés de Especialidad en Chile';
		$title['site']  = $site;
		return $title;
	}

	if ( is_singular( 'product' ) ) {
		global $post;
		$pais = sc_get_product_meta( $post->ID, 'pais' );
		unset( $title['tagline'] );
		if ( $pais ) {
			$title['title'] = get_the_title( $post->ID ) . ' · Café de ' . $pais;
		}
		$title['site'] = $site;
		return $title;
	}

	if ( is_tax( 'product_cat' ) ) {
		$term           = get_queried_object();
		$title['title'] = $term->name . ' · Café de Especialidad en Chile';
		$title['site']  = $site;
		unset( $title['tagline'] );
		return $title;
	}

	if ( is_singular( 'post' ) ) {
		// Guide article
		$title['site'] = $site . ' · Guías de Café';
		unset( $title['tagline'] );
		return $title;
	}

	if ( is_home() || is_archive() ) {
		$title['site'] = $site;
		unset( $title['tagline'] );
		return $title;
	}

	unset( $title['tagline'] );
	return $title;
}, 10 );

// ============================================================
// 2. Meta description + Open Graph + Twitter cards + canonical
// ============================================================
add_action( 'wp_head', 'sc_seo_head', 2 );

function sc_seo_head(): void {
	$cfg                       = sc_seo_cfg();
	[ $desc, $og_title, $type, $url, $image ] = sc_seo_resolve_context();

	// --- Meta description ---
	if ( $desc ) {
		echo '<meta name="description" content="' . esc_attr( $desc ) . '">' . "\n";
	}

	// --- Open Graph ---
	echo '<meta property="og:site_name" content="' . esc_attr( $cfg['site_name'] ) . '">' . "\n";
	echo '<meta property="og:locale" content="es_CL">' . "\n";
	echo '<meta property="og:type" content="' . esc_attr( $type ) . '">' . "\n";
	echo '<meta property="og:url" content="' . esc_url( $url ) . '">' . "\n";
	echo '<meta property="og:title" content="' . esc_attr( $og_title ) . '">' . "\n";
	if ( $desc ) {
		echo '<meta property="og:description" content="' . esc_attr( $desc ) . '">' . "\n";
	}
	echo '<meta property="og:image" content="' . esc_url( $image ) . '">' . "\n";
	echo '<meta property="og:image:width" content="' . esc_attr( (string) $cfg['og_w'] ) . '">' . "\n";
	echo '<meta property="og:image:height" content="' . esc_attr( (string) $cfg['og_h'] ) . '">' . "\n";

	// --- Twitter / X cards ---
	echo '<meta name="twitter:card" content="summary_large_image">' . "\n";
	echo '<meta name="twitter:site" content="@santocafespecialtycoffee">' . "\n";
	echo '<meta name="twitter:title" content="' . esc_attr( $og_title ) . '">' . "\n";
	if ( $desc ) {
		echo '<meta name="twitter:description" content="' . esc_attr( $desc ) . '">' . "\n";
	}
	echo '<meta name="twitter:image" content="' . esc_url( $image ) . '">' . "\n";

	// --- rel="me" (identity) ---
	echo '<link rel="me" href="' . esc_url( $cfg['instagram'] ) . '">' . "\n";

	// --- Canonical (home + shop; singular already handled by WP core) ---
	if ( is_front_page() ) {
		echo '<link rel="canonical" href="' . esc_url( home_url( '/' ) ) . '">' . "\n";
	} elseif ( function_exists( 'is_shop' ) && is_shop() ) {
		echo '<link rel="canonical" href="' . esc_url( get_permalink( wc_get_page_id( 'shop' ) ) ) . '">' . "\n";
	}

	// --- JSON-LD ---
	sc_seo_json_ld();
}

// ============================================================
// 3. Context resolver → [ desc, og_title, og_type, url, image ]
// ============================================================
function sc_seo_resolve_context(): array {
	$cfg      = sc_seo_cfg();
	$fallback = $cfg['og_image'];
	$site     = $cfg['site_name'];

	// --- Home ---
	if ( is_front_page() ) {
		return [
			'Compra café de especialidad en granos y molido de 8 orígenes. Envío a domicilio en Región Metropolitana.',
			$site . ' // Café de Especialidad en Chile',
			'website',
			home_url( '/' ),
			$fallback,
		];
	}

	// --- WooCommerce shop ---
	if ( function_exists( 'is_shop' ) && is_shop() ) {
		return [
			'Nuestros cafés de especialidad: 8 single origins de Colombia, Perú, Bolivia, Brasil, Guatemala y Costa Rica. Puntaje SCA ≥ 83. Envío en Región Metropolitana.',
			'Cafés de Especialidad · ' . $site,
			'website',
			get_permalink( wc_get_page_id( 'shop' ) ),
			$fallback,
		];
	}

	// --- Single product ---
	if ( is_singular( 'product' ) ) {
		global $post;
		$product = wc_get_product( $post->ID );
		if ( $product ) {
			$pais    = sc_get_product_meta( $post->ID, 'pais' );
			$proceso = sc_get_product_meta( $post->ID, 'proceso' );
			$sca     = sc_get_product_meta( $post->ID, 'sca_score' );
			$notas   = sc_get_product_meta( $post->ID, 'notas_cata' );
			$altitud = sc_get_product_meta( $post->ID, 'altitud' );

			$parts = [];
			if ( $pais )    $parts[] = 'Café de especialidad de ' . $pais;
			if ( $proceso ) $parts[] = 'proceso ' . strtolower( $proceso );
			if ( $sca )     $parts[] = 'SCA ' . $sca . ' pts';
			if ( $altitud ) $parts[] = $altitud . ' msnm';
			if ( $notas )   $parts[] = 'notas: ' . strtolower( $notas );

			$desc = implode( ', ', $parts );
			if ( empty( $desc ) ) {
				$raw  = $product->get_short_description() ?: $product->get_description();
				$desc = wp_trim_words( wp_strip_all_tags( $raw ), 30, '.' );
			}

			$image = $fallback;
			if ( $product->get_image_id() ) {
				$src = wp_get_attachment_image_url( $product->get_image_id(), 'large' );
				if ( $src ) $image = $src;
			}

			return [
				$desc,
				$product->get_name() . ' · ' . $site,
				'product',
				get_permalink( $post->ID ),
				$image,
			];
		}
	}

	// --- Product category ---
	if ( is_tax( 'product_cat' ) ) {
		$term = get_queried_object();
		$desc = $term->description
			? wp_trim_words( wp_strip_all_tags( $term->description ), 30, '.' )
			: 'Explora nuestra selección de cafés de especialidad en Chile. Single origins con puntaje SCA. Envío en Región Metropolitana.';
		return [
			$desc,
			$term->name . ' · Cafés de Especialidad · ' . $site,
			'website',
			get_term_link( $term ),
			$fallback,
		];
	}

	// --- Guide / post ---
	if ( is_singular( 'post' ) ) {
		global $post;
		$desc = get_the_excerpt( $post )
			?: wp_trim_words( wp_strip_all_tags( $post->post_content ), 30, '.' );
		$image = $fallback;
		if ( has_post_thumbnail( $post->ID ) ) {
			$src = get_the_post_thumbnail_url( $post->ID, 'large' );
			if ( $src ) $image = $src;
		}
		return [
			$desc,
			get_the_title( $post->ID ) . ' · ' . $site,
			'article',
			get_permalink( $post->ID ),
			$image,
		];
	}

	// --- Static page ---
	if ( is_page() ) {
		global $post;
		$desc  = get_the_excerpt( $post )
			?: wp_trim_words( wp_strip_all_tags( $post->post_content ), 30, '.' );
		$image = $fallback;
		if ( has_post_thumbnail( $post->ID ) ) {
			$src = get_the_post_thumbnail_url( $post->ID, 'large' );
			if ( $src ) $image = $src;
		}
		return [
			$desc ?: 'Santo Café — Café de especialidad en Chile.',
			get_the_title( $post->ID ) . ' · ' . $site,
			'website',
			get_permalink( $post->ID ),
			$image,
		];
	}

	// --- Fallback ---
	return [
		'Santo Café — Café de especialidad en Chile. 8 single origins latinoamericanos. Envío en Región Metropolitana.',
		$site,
		'website',
		home_url( '/' ),
		$fallback,
	];
}

// ============================================================
// 4. JSON-LD output dispatcher
// ============================================================
function sc_seo_json_ld(): void {
	$schemas = [];

	// Home: Organization + WebSite + LocalBusiness
	if ( is_front_page() ) {
		$schemas[] = sc_seo_schema_organization();
		$schemas[] = sc_seo_schema_website();
		$schemas[] = sc_seo_schema_local_business();
	}

	// BreadcrumbList (all pages except home)
	if ( ! is_front_page() ) {
		$bc = sc_seo_schema_breadcrumb();
		if ( $bc ) $schemas[] = $bc;
	}

	// FAQPage (guide posts that contain Q&A formatted as h3+p)
	if ( is_singular( 'post' ) ) {
		$faq = sc_seo_schema_faqpage();
		if ( $faq ) $schemas[] = $faq;
	}

	foreach ( $schemas as $schema ) {
		echo '<script type="application/ld+json">' . "\n"
			. wp_json_encode( $schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT )
			. "\n</script>\n";
	}
}

// ============================================================
// 5. Schema builders
// ============================================================
function sc_seo_schema_organization(): array {
	$cfg = sc_seo_cfg();
	return [
		'@context'     => 'https://schema.org',
		'@type'        => 'Organization',
		'name'         => $cfg['site_name'],
		'url'          => home_url( '/' ),
		'logo'         => [
			'@type'  => 'ImageObject',
			'url'    => $cfg['logo'],
			'width'  => $cfg['logo_w'],
			'height' => $cfg['logo_h'],
		],
		'email'        => $cfg['email'],
		'telephone'    => $cfg['phone'],
		'sameAs'       => [ $cfg['instagram'] ],
		'contactPoint' => [
			'@type'             => 'ContactPoint',
			'telephone'         => $cfg['phone'],
			'contactType'       => 'customer service',
			'availableLanguage' => 'Spanish',
		],
		'areaServed'   => [
			'@type' => 'AdministrativeArea',
			'name'  => 'Región Metropolitana de Santiago, Chile',
		],
	];
}

function sc_seo_schema_website(): array {
	return [
		'@context'        => 'https://schema.org',
		'@type'           => 'WebSite',
		'name'            => sc_seo_cfg()['site_name'],
		'url'             => home_url( '/' ),
		'inLanguage'      => 'es-CL',
		'potentialAction' => [
			'@type'       => 'SearchAction',
			'target'      => [
				'@type'       => 'EntryPoint',
				'urlTemplate' => home_url( '/' ) . '?s={search_term_string}',
			],
			'query-input' => 'required name=search_term_string',
		],
	];
}

function sc_seo_schema_local_business(): array {
	$cfg = sc_seo_cfg();
	return [
		'@context'            => 'https://schema.org',
		'@type'               => [ 'LocalBusiness', 'Store' ],
		'name'                => $cfg['site_name'],
		'url'                 => home_url( '/' ),
		'image'               => $cfg['og_image'],
		'logo'                => $cfg['logo'],
		'email'               => $cfg['email'],
		'telephone'           => $cfg['phone'],
		'priceRange'          => '$$',
		'currenciesAccepted'  => 'CLP',
		'paymentAccepted'     => 'Credit Card, Debit Card, Bank Transfer',
		'sameAs'              => [ $cfg['instagram'] ],
		'address'             => [
			'@type'          => 'PostalAddress',
			'addressCountry' => 'CL',
			'addressRegion'  => 'Región Metropolitana de Santiago',
		],
		'areaServed'          => [
			'@type' => 'AdministrativeArea',
			'name'  => 'Región Metropolitana de Santiago, Chile',
		],
		'hasOfferCatalog'     => [
			'@type' => 'OfferCatalog',
			'name'  => 'Cafés de especialidad single origin',
		],
	];
}

function sc_seo_schema_breadcrumb(): ?array {
	$items = [];
	$pos   = 1;

	$items[] = [
		'@type'    => 'ListItem',
		'position' => $pos++,
		'name'     => 'Inicio',
		'item'     => home_url( '/' ),
	];

	// WooCommerce pages
	if ( function_exists( 'is_woocommerce' ) && is_woocommerce() ) {
		$shop_url = get_permalink( wc_get_page_id( 'shop' ) );

		if ( ! is_shop() ) {
			$items[] = [
				'@type'    => 'ListItem',
				'position' => $pos++,
				'name'     => 'Cafés',
				'item'     => $shop_url,
			];
		}

		if ( is_tax( 'product_cat' ) ) {
			$term    = get_queried_object();
			$items[] = [
				'@type'    => 'ListItem',
				'position' => $pos++,
				'name'     => $term->name,
				'item'     => get_term_link( $term ),
			];
		}

		if ( is_singular( 'product' ) ) {
			global $post;
			$cats = wc_get_product_terms( $post->ID, 'product_cat', [ 'orderby' => 'parent', 'order' => 'ASC' ] );
			if ( ! empty( $cats ) ) {
				$cat     = end( $cats );
				$items[] = [
					'@type'    => 'ListItem',
					'position' => $pos++,
					'name'     => $cat->name,
					'item'     => get_term_link( $cat ),
				];
			}
			$items[] = [
				'@type'    => 'ListItem',
				'position' => $pos++,
				'name'     => get_the_title( $post->ID ),
				'item'     => get_permalink( $post->ID ),
			];
		}
	}

	// Guide posts
	if ( is_singular( 'post' ) ) {
		global $post;
		$items[] = [
			'@type'    => 'ListItem',
			'position' => $pos++,
			'name'     => 'Guías',
			'item'     => sc_guias_url(),
		];
		$items[] = [
			'@type'    => 'ListItem',
			'position' => $pos++,
			'name'     => get_the_title( $post->ID ),
			'item'     => get_permalink( $post->ID ),
		];
	}

	// Static pages (non-home)
	if ( is_page() ) {
		global $post;
		foreach ( array_reverse( get_post_ancestors( $post->ID ) ) as $ancestor_id ) {
			$items[] = [
				'@type'    => 'ListItem',
				'position' => $pos++,
				'name'     => get_the_title( $ancestor_id ),
				'item'     => get_permalink( $ancestor_id ),
			];
		}
		$items[] = [
			'@type'    => 'ListItem',
			'position' => $pos++,
			'name'     => get_the_title( $post->ID ),
			'item'     => get_permalink( $post->ID ),
		];
	}

	// Blog archive
	if ( is_home() && ! is_front_page() ) {
		$items[] = [
			'@type'    => 'ListItem',
			'position' => $pos++,
			'name'     => 'Guías',
			'item'     => sc_guias_url(),
		];
	}

	if ( count( $items ) <= 1 ) {
		return null;
	}

	return [
		'@context'        => 'https://schema.org',
		'@type'           => 'BreadcrumbList',
		'itemListElement' => $items,
	];
}

/**
 * Build FAQPage schema from guide posts.
 * Detects H3 questions (with ¿ or ?) followed by a <p> answer.
 */
function sc_seo_schema_faqpage(): ?array {
	global $post;
	if ( ! $post ) {
		return null;
	}

	$content = apply_filters( 'the_content', $post->post_content );
	$qas     = [];

	if ( preg_match_all( '/<h3[^>]*>(.*?)<\/h3>\s*<p[^>]*>(.*?)<\/p>/is', $content, $matches, PREG_SET_ORDER ) ) {
		foreach ( $matches as $match ) {
			$question = wp_strip_all_tags( $match[1] );
			$answer   = wp_strip_all_tags( $match[2] );
			if ( str_contains( $question, '?' ) || str_contains( $question, '¿' ) ) {
				$qas[] = [
					'@type'          => 'Question',
					'name'           => $question,
					'acceptedAnswer' => [
						'@type' => 'Answer',
						'text'  => $answer,
					],
				];
			}
		}
	}

	if ( empty( $qas ) ) {
		return null;
	}

	return [
		'@context'   => 'https://schema.org',
		'@type'      => 'FAQPage',
		'mainEntity' => $qas,
	];
}

// ============================================================
// 6. Enhance WooCommerce's Product schema with our rich data
//    (brand + additionalProperty from _sc_* meta fields)
// ============================================================
add_filter( 'woocommerce_structured_data_product', function ( array $markup, $product ): array {
	if ( ! $product instanceof WC_Product ) {
		return $markup;
	}

	$id = $product->get_id();

	// Brand
	$markup['brand'] = [
		'@type' => 'Brand',
		'name'  => 'Santo Café',
	];

	// Additional properties from specialty meta
	$props = [];

	$map = [
		'sca_score' => 'Puntaje SCA',
		'pais'      => 'País de origen',
		'region'    => 'Región',
		'productor' => 'Productor',
		'altitud'   => 'Altitud (msnm)',
		'variedad'  => 'Variedad',
		'proceso'   => 'Proceso de beneficio',
		'notas_cata' => 'Notas de cata',
		'intensidad' => 'Intensidad',
		'acidez'    => 'Acidez',
		'cuerpo'    => 'Cuerpo',
	];

	foreach ( $map as $key => $label ) {
		$val = sc_get_product_meta( $id, $key );
		if ( '' !== (string) $val ) {
			$formatted = (string) $val;
			if ( 'sca_score' === $key )  $formatted .= ' puntos';
			if ( 'altitud' === $key )    $formatted .= ' msnm';
			if ( in_array( $key, [ 'intensidad', 'acidez', 'cuerpo' ], true ) ) {
				$formatted .= '/5';
			}
			$props[] = [
				'@type' => 'PropertyValue',
				'name'  => $label,
				'value' => $formatted,
			];
		}
	}

	if ( ! empty( $props ) ) {
		$markup['additionalProperty'] = $props;
	}

	return $markup;
}, 10, 2 );

// Suppress WooCommerce's own WebSite schema — we output a richer one in wp_head.
add_filter( 'woocommerce_structured_data_website', '__return_empty_array' );

// ============================================================
// 7. noindex — cart, checkout, and all My Account sub-pages
// ============================================================
add_filter( 'wp_robots', function ( array $robots ): array {
	$noindex = false;

	if ( function_exists( 'is_cart' ) && is_cart() ) {
		$noindex = true;
	}
	if ( function_exists( 'is_checkout' ) && is_checkout() ) {
		$noindex = true;
	}
	if ( function_exists( 'is_wc_endpoint_url' ) && is_wc_endpoint_url() ) {
		$noindex = true;
	}
	if ( function_exists( 'is_account_page' ) && is_account_page() ) {
		$noindex = true;
	}

	if ( $noindex ) {
		$robots['noindex']  = true;
		$robots['nofollow'] = true;
		unset( $robots['index'], $robots['follow'] );
	}

	return $robots;
} );

// ============================================================
// 8. robots.txt — add Sitemap pointer if not already present
// ============================================================
add_filter( 'robots_txt', function ( string $output, bool $public ): string {
	if ( ! $public ) {
		return $output;
	}
	if ( ! str_contains( $output, 'Sitemap:' ) ) {
		$output .= "\nSitemap: " . home_url( '/wp-sitemap.xml' ) . "\n";
	}
	return $output;
}, 10, 2 );

// ============================================================
// 9. Core sitemap — remove users (privacy); ensure product + post
// ============================================================
add_filter( 'wp_sitemaps_add_provider', function ( $provider, string $name ) {
	return 'users' === $name ? false : $provider;
}, 10, 2 );

add_filter( 'wp_sitemaps_post_types', function ( array $post_types ): array {
	foreach ( [ 'product', 'post', 'page' ] as $pt ) {
		if ( post_type_exists( $pt ) && ! isset( $post_types[ $pt ] ) ) {
			$post_types[ $pt ] = get_post_type_object( $pt );
		}
	}
	return $post_types;
} );
