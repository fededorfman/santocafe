<?php
defined('ABSPATH') || exit;

/**
 * Render a SCA score badge.
 *
 * @param float  $score      SCA score, e.g. 84.75
 * @param string $flag_emoji Country flag emoji (optional)
 * @return string            HTML string (escaped)
 */
function sc_render_sca_badge( float $score, string $flag_emoji = '' ): string {
    $label = $flag_emoji
        ? esc_html( "{$flag_emoji} SCA {$score}" )
        : esc_html( "SCA {$score}" );

    return '<span class="sca-badge">' . $label . '</span>';
}

/**
 * Get a product's custom Santo Café meta value.
 *
 * @param int    $product_id
 * @param string $key  Meta key without the "_sc_" prefix (e.g. 'pais', 'sca_score')
 * @return mixed
 */
function sc_get_product_meta( int $product_id, string $key ): mixed {
    return get_post_meta( $product_id, '_sc_' . $key, true );
}

/**
 * Format an integer as Chilean Peso: 15500 → "$15.500"
 *
 * @param int $amount
 * @return string
 */
function sc_format_clp( int $amount ): string {
    return '$' . number_format( $amount, 0, ',', '.' );
}

/**
 * Return a country flag emoji for a given country name.
 *
 * @param string $country
 * @return string Flag emoji, or ☕ if unknown
 */
function sc_country_flag( string $country ): string {
    $flags = [
        'colombia'    => '🇨🇴',
        'perú'        => '🇵🇪',
        'peru'        => '🇵🇪',
        'bolivia'     => '🇧🇴',
        'brasil'      => '🇧🇷',
        'brazil'      => '🇧🇷',
        'guatemala'   => '🇬🇹',
        'costa rica'  => '🇨🇷',
        'etiopía'     => '🇪🇹',
        'etiopia'     => '🇪🇹',
        'kenia'       => '🇰🇪',
        'kenya'       => '🇰🇪',
        'rwanda'      => '🇷🇼',
        'el salvador' => '🇸🇻',
        'sumatra'     => '🇮🇩',
    ];

    return $flags[ strtolower( trim( $country ) ) ] ?? '☕';
}

/**
 * Calculate how much (in CLP) is left to reach free shipping.
 * Returns 0 if the threshold is already reached or WooCommerce is not active.
 *
 * @return int
 */
function sc_get_shipping_gap(): int {
    if ( ! function_exists( 'WC' ) || ! WC()->cart ) {
        return 0;
    }

    $min      = (int) get_option( 'sc_shipping_free_min', 50000 );
    $subtotal = (int) WC()->cart->get_subtotal();

    return max( 0, $min - $subtotal );
}

/**
 * Render a profile bar (Intensidad / Acidez / Cuerpo).
 *
 * @param string $label  Display label, e.g. 'Intensidad'
 * @param int    $value  Value 1–5
 * @param int    $max    Max segments (default 5)
 */
function sc_render_profile_bar( string $label, int $value, int $max = 5 ): void {
    $value = max( 0, min( $max, $value ) );
    echo '<div class="profile-bar">';
    echo '<span class="profile-bar__label">' . esc_html( $label ) . '</span>';
    echo '<div class="profile-bar__segments">';
    for ( $i = 1; $i <= $max; $i++ ) {
        $active = $i <= $value ? ' profile-bar__segment--active' : '';
        echo '<span class="profile-bar__segment' . $active . '"></span>';
    }
    echo '</div>';
    echo '</div>';
}
