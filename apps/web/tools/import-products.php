<?php
/**
 * Santo Café — Product Import Script
 *
 * Reads docs/productos/catalogo.csv and creates WooCommerce variable products
 * with 2 variations (250g / 1kg), all custom meta fields, and both attributes
 * (pa_peso for variations, pa_molienda as informational).
 *
 * Usage (from apps/web/app/public/):
 *   wp eval-file ../../tools/import-products.php
 *
 * Or from apps/web/:
 *   wp --path=app/public eval-file tools/import-products.php
 *
 * The script is IDEMPOTENT: products that already exist (by title) are skipped.
 */

if ( ! defined( 'ABSPATH' ) ) {
    // Allow running directly (finds WP root relative to this file)
    $wp_root = __DIR__ . '/../app/public/';
    if ( file_exists( $wp_root . 'wp-load.php' ) ) {
        require_once $wp_root . 'wp-load.php';
    } else {
        die( "Error: wp-load.php not found at {$wp_root}\n" );
    }
}

if ( ! class_exists( 'WooCommerce' ) ) {
    sc_import_log( '❌ WooCommerce is not active. Install and activate it first.' );
    exit( 1 );
}

// ============================================================
// CSV Path
// ============================================================
$csv_path = realpath( __DIR__ . '/../../docs/productos/catalogo.csv' );

if ( ! $csv_path || ! file_exists( $csv_path ) ) {
    sc_import_log( "❌ CSV not found. Expected: " . __DIR__ . '/../../docs/productos/catalogo.csv' );
    exit( 1 );
}

sc_import_log( "📂 Reading: {$csv_path}" );

// ============================================================
// Ensure global attributes and their terms exist
// ============================================================
sc_ensure_wc_attribute( 'Peso',     'peso',     [ '250g', '1kg' ] );
sc_ensure_wc_attribute( 'Molienda', 'molienda', [ 'Grano', 'Espresso', 'Italiana', 'Filtro' ] );

// ============================================================
// Parse CSV and create products
// ============================================================
$handle  = fopen( $csv_path, 'r' );
$headers = fgetcsv( $handle ); // skip header row

$created = 0;
$skipped = 0;
$errors  = 0;

while ( ( $row = fgetcsv( $handle ) ) !== false ) {
    // Pad row to avoid undefined index warnings
    $row = array_pad( $row, 22, '' );

    $title = trim( $row[3] );
    if ( empty( $title ) ) continue;

    // --- Idempotency check ---
    $existing = get_page_by_title( $title, OBJECT, 'product' );
    if ( $existing ) {
        $skipped++;
        sc_import_log( "⏭  Skipped (exists): {$title}" );
        continue;
    }

    // --- Parse prices ---
    // Format in CSV: "15500 / 49600"
    $price_raw  = trim( $row[7] );
    $price_parts = array_map( 'trim', explode( '/', $price_raw ) );
    $price_250g  = isset( $price_parts[0] ) ? (float) $price_parts[0] : 0;
    $price_1kg   = isset( $price_parts[1] ) ? (float) $price_parts[1] : 0;

    // --- Create Variable Product ---
    $product = new WC_Product_Variable();
    $product->set_name( $title );
    $product->set_status( 'publish' );
    $product->set_catalog_visibility( 'visible' );
    $product->set_sku( 'SC-' . str_pad( trim( $row[0] ), 2, '0', STR_PAD_LEFT ) );
    $product->set_short_description( wp_kses_post( trim( $row[6] ) ) ); // notas de cata

    // Descripción larga = "Descripción del productor" (col 13)
    $description = trim( $row[13] );
    if ( $description ) {
        $product->set_description( wp_kses_post( $description ) );
    }

    // --- Attributes ---
    $attrs = [];

    // pa_peso → used for variations (price differs)
    $attr_peso = sc_build_product_attribute( 'peso', [ '250g', '1kg' ], true );
    if ( $attr_peso ) $attrs[] = $attr_peso;

    // pa_molienda → informational only (price does NOT differ by molienda)
    $attr_molienda = sc_build_product_attribute( 'molienda', [ 'Grano', 'Espresso', 'Italiana', 'Filtro' ], false );
    if ( $attr_molienda ) $attrs[] = $attr_molienda;

    $product->set_attributes( $attrs );
    $product_id = $product->save();

    if ( ! $product_id || is_wp_error( $product_id ) ) {
        $errors++;
        sc_import_log( "❌ Error saving: {$title}" );
        continue;
    }

    // --- Variations ---
    sc_create_product_variation( $product_id, 'peso', '250g', $price_250g );
    sc_create_product_variation( $product_id, 'peso', '1kg',  $price_1kg );

    // --- Custom meta ---
    $meta_map = [
        '_sc_sca_score'  => trim( $row[8] ),
        '_sc_pais'       => trim( $row[10] ),
        '_sc_region'     => trim( $row[11] ), // Ciudad de Origen
        '_sc_productor'  => trim( $row[12] ), // Nombre de Productor
        '_sc_altitud'    => trim( $row[14] ),
        '_sc_proceso'    => trim( $row[15] ),
        '_sc_intensidad' => trim( $row[16] ),
        '_sc_acidez'     => trim( $row[17] ),
        '_sc_cuerpo'     => trim( $row[18] ),
        '_sc_notas_cata' => trim( $row[6] ),
        '_sc_variedad'   => trim( $row[4] ),
    ];

    foreach ( $meta_map as $key => $value ) {
        if ( $value !== '' ) {
            update_post_meta( $product_id, $key, sanitize_text_field( $value ) );
        }
    }

    // Sync variable product min/max prices
    WC_Product_Variable::sync( $product_id );

    $created++;
    sc_import_log( "✅ Created [{$product_id}]: {$title}" );
}

fclose( $handle );

sc_import_log( '' );
sc_import_log( "=== Done! Created: {$created} | Skipped: {$skipped} | Errors: {$errors} ===" );

// ============================================================
// Helper functions
// ============================================================

/**
 * Ensure a global WooCommerce attribute exists with the given terms.
 * Creates the attribute taxonomy and its terms if missing.
 * Safe to call multiple times (idempotent).
 */
function sc_ensure_wc_attribute( string $name, string $slug, array $terms ): void {
    $taxonomy = 'pa_' . $slug;

    if ( ! taxonomy_exists( $taxonomy ) ) {
        if ( ! function_exists( 'wc_create_attribute' ) ) {
            sc_import_log( "⚠️  wc_create_attribute() not found. Is WooCommerce active?" );
            return;
        }

        $result = wc_create_attribute( [
            'name'         => $name,
            'slug'         => $slug,
            'type'         => 'select',
            'order_by'     => 'menu_order',
            'has_archives' => false,
        ] );

        if ( is_wp_error( $result ) ) {
            sc_import_log( "⚠️  Could not create attribute '{$slug}': " . $result->get_error_message() );
            return;
        }

        // Register the taxonomy for the current request so terms can be inserted now.
        register_taxonomy( $taxonomy, [ 'product' ], [
            'hierarchical' => false,
            'show_ui'      => false,
            'query_var'    => true,
            'rewrite'      => false,
        ] );

        sc_import_log( "🏷️  Created attribute: {$name} (pa_{$slug})" );
    }

    foreach ( $terms as $term_name ) {
        if ( ! term_exists( $term_name, $taxonomy ) ) {
            $result = wp_insert_term( $term_name, $taxonomy );
            if ( ! is_wp_error( $result ) ) {
                sc_import_log( "   + Term: {$term_name}" );
            }
        }
    }
}

/**
 * Build a WC_Product_Attribute object ready to be assigned to a product.
 *
 * @param string   $slug         Attribute slug (without "pa_")
 * @param string[] $term_names   Term names to include
 * @param bool     $is_variation Whether this attribute is used for variations
 */
function sc_build_product_attribute( string $slug, array $term_names, bool $is_variation ): ?WC_Product_Attribute {
    $taxonomy = 'pa_' . $slug;

    if ( ! taxonomy_exists( $taxonomy ) ) {
        return null;
    }

    $term_ids = [];
    foreach ( $term_names as $term_name ) {
        $term = get_term_by( 'name', $term_name, $taxonomy );
        if ( $term ) {
            $term_ids[] = $term->term_id;
        }
    }

    if ( empty( $term_ids ) ) {
        return null;
    }

    $attr = new WC_Product_Attribute();
    $attr->set_id( wc_attribute_taxonomy_id_by_name( $slug ) );
    $attr->set_name( $taxonomy );
    $attr->set_options( $term_ids );
    $attr->set_position( $is_variation ? 0 : 1 );
    $attr->set_visible( true );
    $attr->set_variation( $is_variation );

    return $attr;
}

/**
 * Create a single product variation for a given attribute value.
 *
 * @param int    $product_id  Parent product ID
 * @param string $attr_slug   Attribute slug (without "pa_")
 * @param string $term_name   Term name (e.g. "250g")
 * @param float  $price       Regular price
 */
function sc_create_product_variation( int $product_id, string $attr_slug, string $term_name, float $price ): void {
    $taxonomy = 'pa_' . $attr_slug;
    $term     = get_term_by( 'name', $term_name, $taxonomy );

    if ( ! $term ) {
        sc_import_log( "   ⚠️  Term '{$term_name}' not found in {$taxonomy}" );
        return;
    }

    $variation = new WC_Product_Variation();
    $variation->set_parent_id( $product_id );
    $variation->set_status( 'publish' );
    $variation->set_regular_price( (string) $price );
    $variation->set_attributes( [ $taxonomy => $term->slug ] );
    $variation->set_manage_stock( false );
    $variation->set_stock_status( 'instock' );
    $variation->save();
}

/**
 * Log a message to WP-CLI output or stdout.
 */
function sc_import_log( string $msg ): void {
    if ( defined( 'WP_CLI' ) && WP_CLI ) {
        WP_CLI::log( $msg );
    } else {
        echo $msg . "\n";
    }
}
