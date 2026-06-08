<?php
/**
 * Breadcrumbs — Santo Café
 *
 * Outputs semantic breadcrumb navigation.
 * The matching BreadcrumbList JSON-LD is emitted in inc/seo.php.
 *
 * Usage: get_template_part( 'template-parts/breadcrumbs' );
 */
defined( 'ABSPATH' ) || exit;

$sc_bc_items = [];

// Home is always first
$sc_bc_items[] = [
	'label' => 'Inicio',
	'url'   => home_url( '/' ),
	'current' => false,
];

// WooCommerce pages
if ( function_exists( 'is_woocommerce' ) && is_woocommerce() ) {
	$shop_url = get_permalink( wc_get_page_id( 'shop' ) );

	if ( ! is_shop() ) {
		$sc_bc_items[] = [
			'label'   => 'Cafés',
			'url'     => $shop_url,
			'current' => false,
		];
	}

	if ( is_tax( 'product_cat' ) ) {
		$sc_bc_term    = get_queried_object();
		$sc_bc_items[] = [
			'label'   => $sc_bc_term->name,
			'url'     => get_term_link( $sc_bc_term ),
			'current' => true,
		];
	}

	if ( is_singular( 'product' ) ) {
		global $post;
		$sc_bc_cats = wc_get_product_terms( $post->ID, 'product_cat', [ 'orderby' => 'parent', 'order' => 'ASC' ] );
		if ( ! empty( $sc_bc_cats ) ) {
			$sc_bc_cat     = end( $sc_bc_cats );
			$sc_bc_items[] = [
				'label'   => $sc_bc_cat->name,
				'url'     => get_term_link( $sc_bc_cat ),
				'current' => false,
			];
		}
		$sc_bc_items[] = [
			'label'   => get_the_title( $post->ID ),
			'url'     => get_permalink( $post->ID ),
			'current' => true,
		];
	}

	if ( is_shop() ) {
		// Mark "Cafés" as current (already added as first item after Inicio)
		$sc_bc_items[] = [
			'label'   => 'Cafés',
			'url'     => $shop_url,
			'current' => true,
		];
	}
}

// Guide / post
if ( is_singular( 'post' ) ) {
	global $post;
	$sc_bc_items[] = [
		'label'   => 'Guías',
		'url'     => sc_guias_url(),
		'current' => false,
	];
	$sc_bc_items[] = [
		'label'   => get_the_title( $post->ID ),
		'url'     => get_permalink( $post->ID ),
		'current' => true,
	];
}

// Blog/archive (guías index)
if ( is_home() && ! is_front_page() ) {
	$sc_bc_items[] = [
		'label'   => 'Guías',
		'url'     => sc_guias_url(),
		'current' => true,
	];
}

// Static page
if ( is_page() ) {
	global $post;
	foreach ( array_reverse( get_post_ancestors( $post->ID ) ) as $sc_ancestor_id ) {
		$sc_bc_items[] = [
			'label'   => get_the_title( $sc_ancestor_id ),
			'url'     => get_permalink( $sc_ancestor_id ),
			'current' => false,
		];
	}
	$sc_bc_items[] = [
		'label'   => get_the_title( $post->ID ),
		'url'     => get_permalink( $post->ID ),
		'current' => true,
	];
}

// Don't render on home or if only 1 item
if ( is_front_page() || count( $sc_bc_items ) <= 1 ) {
	return;
}

// Remove duplicate shop entry that happens when is_shop() builds items naively
$sc_bc_seen  = [];
$sc_bc_clean = [];
foreach ( $sc_bc_items as $sc_bc_item ) {
	if ( ! in_array( $sc_bc_item['url'], $sc_bc_seen, true ) ) {
		$sc_bc_seen[]  = $sc_bc_item['url'];
		$sc_bc_clean[] = $sc_bc_item;
	}
}
$sc_bc_items = $sc_bc_clean;
?>

<nav class="sc-breadcrumbs" aria-label="Ruta de navegación">
    <ol class="sc-breadcrumbs__list" itemscope itemtype="https://schema.org/BreadcrumbList">
        <?php foreach ( $sc_bc_items as $sc_bc_pos => $sc_bc_item ) : ?>
            <li class="sc-breadcrumbs__item<?php echo $sc_bc_item['current'] ? ' sc-breadcrumbs__item--current' : ''; ?>"
                itemprop="itemListElement" itemscope itemtype="https://schema.org/ListItem">

                <?php if ( $sc_bc_item['current'] ) : ?>
                    <span class="sc-breadcrumbs__label" itemprop="name" aria-current="page">
                        <?php echo esc_html( $sc_bc_item['label'] ); ?>
                    </span>
                <?php else : ?>
                    <a class="sc-breadcrumbs__link" href="<?php echo esc_url( $sc_bc_item['url'] ); ?>"
                       itemprop="item">
                        <span itemprop="name"><?php echo esc_html( $sc_bc_item['label'] ); ?></span>
                    </a>
                    <span class="sc-breadcrumbs__sep" aria-hidden="true">›</span>
                <?php endif; ?>

                <meta itemprop="position" content="<?php echo esc_attr( (string) ( $sc_bc_pos + 1 ) ); ?>">
            </li>
        <?php endforeach; ?>
    </ol>
</nav>
