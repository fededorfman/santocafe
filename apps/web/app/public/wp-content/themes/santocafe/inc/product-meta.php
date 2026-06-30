<?php
defined('ABSPATH') || exit;

/**
 * Santo Café — Custom product meta fields.
 *
 * Registers global WC attributes (peso, molienda) and adds
 * a custom "☕ Café" tab in the WooCommerce product data panel
 * with all origin, technical and tasting-profile fields.
 */

// ============================================================
// 1. Register global WooCommerce product attributes
//    Runs on init so WC is fully loaded.
// ============================================================
add_action( 'init', function (): void {
    if ( ! function_exists( 'wc_create_attribute' ) ) {
        return;
    }

    $attributes = [
        [ 'name' => 'Peso',     'slug' => 'peso' ],
        [ 'name' => 'Molienda', 'slug' => 'molienda' ],
    ];

    foreach ( $attributes as $attr ) {
        if ( taxonomy_exists( 'pa_' . $attr['slug'] ) ) {
            continue; // Already registered, nothing to do.
        }

        $result = wc_create_attribute( [
            'name'         => $attr['name'],
            'slug'         => $attr['slug'],
            'type'         => 'select',
            'order_by'     => 'menu_order',
            'has_archives' => false,
        ] );

        if ( ! is_wp_error( $result ) ) {
            // Make the taxonomy available immediately in this request.
            register_taxonomy( 'pa_' . $attr['slug'], [ 'product' ], [
                'hierarchical' => false,
                'show_ui'      => false,
                'query_var'    => true,
                'rewrite'      => false,
            ] );
        }
    }
}, 20 );

// ============================================================
// 2. Add "☕ Café" tab in the product data panel
// ============================================================
add_filter( 'woocommerce_product_data_tabs', function ( array $tabs ): array {
    $tabs['sc_cafe'] = [
        'label'  => '☕ Café',
        'target' => 'sc_cafe_product_data',
        'class'  => [ 'show_if_simple', 'show_if_variable' ],
    ];
    return $tabs;
} );

// ============================================================
// 3. Render meta fields inside the panel
// ============================================================
add_action( 'woocommerce_product_data_panels', function (): void {
    global $post;
    $id = $post->ID;
    ?>
    <div id="sc_cafe_product_data" class="panel woocommerce_options_panel">

        <h4 style="padding:12px 12px 4px; font-size:13px; color:#555;">Origen</h4>

        <?php
        woocommerce_wp_text_input( [
            'id'    => '_sc_pais',
            'label' => 'País de origen',
            'value' => get_post_meta( $id, '_sc_pais', true ),
        ] );
        woocommerce_wp_text_input( [
            'id'          => '_sc_region',
            'label'       => 'Región / Ciudad',
            'description' => 'Ej: Caranavi, Yungas',
            'desc_tip'    => true,
            'value'       => get_post_meta( $id, '_sc_region', true ),
        ] );
        woocommerce_wp_text_input( [
            'id'    => '_sc_productor',
            'label' => 'Productor / Finca',
            'value' => get_post_meta( $id, '_sc_productor', true ),
        ] );
        woocommerce_wp_text_input( [
            'id'    => '_sc_altitud',
            'label' => 'Altitud (msnm)',
            'type'  => 'text',
            'value' => get_post_meta( $id, '_sc_altitud', true ),
        ] );
        ?>

        <h4 style="padding:12px 12px 4px; font-size:13px; color:#555;">Técnico</h4>

        <?php
        woocommerce_wp_text_input( [
            'id'    => '_sc_variedad',
            'label' => 'Variedad',
            'value' => get_post_meta( $id, '_sc_variedad', true ),
        ] );
        woocommerce_wp_text_input( [
            'id'    => '_sc_proceso',
            'label' => 'Proceso',
            'value' => get_post_meta( $id, '_sc_proceso', true ),
        ] );
        woocommerce_wp_text_input( [
            'id'    => '_sc_sca_score',
            'label' => 'Puntaje SCA',
            'type'  => 'number',
            'value' => get_post_meta( $id, '_sc_sca_score', true ),
            'custom_attributes' => [ 'min' => '0', 'max' => '100', 'step' => '0.01' ],
        ] );
        ?>

        <h4 style="padding:12px 12px 4px; font-size:13px; color:#555;">Cata</h4>

        <?php
        woocommerce_wp_textarea_input( [
            'id'    => '_sc_notas_cata',
            'label' => 'Notas de cata',
            'value' => get_post_meta( $id, '_sc_notas_cata', true ),
        ] );
        ?>

        <h4 style="padding:12px 12px 4px; font-size:13px; color:#555;">Perfil (1 – 5)</h4>

        <?php
        $profile_fields = [
            '_sc_intensidad' => 'Intensidad',
            '_sc_acidez'     => 'Acidez',
            '_sc_cuerpo'     => 'Cuerpo',
        ];

        foreach ( $profile_fields as $key => $label ) {
            woocommerce_wp_text_input( [
                'id'    => $key,
                'label' => $label,
                'type'  => 'number',
                'value' => get_post_meta( $id, $key, true ),
                'custom_attributes' => [ 'min' => '1', 'max' => '5', 'step' => '1' ],
            ] );
        }
        ?>

    </div>
    <?php
} );

// ============================================================
// 4. Save meta fields on product save
// ============================================================
add_action( 'woocommerce_process_product_meta', function ( int $post_id ): void {
    if ( ! current_user_can( 'edit_post', $post_id ) ) {
        return;
    }

    // Defensa en profundidad: verificar el nonce que WC emite en la pantalla
    // de producto (el core ya lo valida antes de disparar este hook).
    if ( ! isset( $_POST['woocommerce_meta_nonce'] )
        || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['woocommerce_meta_nonce'] ) ), 'woocommerce_save_data' ) ) {
        return;
    }

    $text_fields = [
        '_sc_pais', '_sc_region', '_sc_productor',
        '_sc_variedad', '_sc_proceso',
    ];

    $num_fields = [
        '_sc_altitud', '_sc_sca_score',
        '_sc_intensidad', '_sc_acidez', '_sc_cuerpo',
    ];

    $textarea_fields = [ '_sc_notas_cata' ];

    foreach ( $text_fields as $key ) {
        if ( isset( $_POST[ $key ] ) ) {
            update_post_meta( $post_id, $key, sanitize_text_field( $_POST[ $key ] ) );
        }
    }

    foreach ( $num_fields as $key ) {
        if ( isset( $_POST[ $key ] ) ) {
            // sanitize_text_field is safe for numeric strings
            update_post_meta( $post_id, $key, sanitize_text_field( $_POST[ $key ] ) );
        }
    }

    foreach ( $textarea_fields as $key ) {
        if ( isset( $_POST[ $key ] ) ) {
            update_post_meta( $post_id, $key, sanitize_textarea_field( $_POST[ $key ] ) );
        }
    }
} );
