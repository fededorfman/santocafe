<?php
defined('ABSPATH') || exit;

/**
 * Santo Café — Campo "Productos recomendados" en posts (guías).
 *
 * Ver docs/superpowers/specs/2026-07-23-productos-recomendados-guias-design.md
 */

add_action( 'add_meta_boxes', function (): void {
    add_meta_box(
        'sc_guide_related_products',
        'Productos recomendados',
        'sc_guide_related_products_meta_box',
        'post',
        'normal',
        'default'
    );
} );

function sc_guide_related_products_meta_box( WP_Post $post ): void {
    wp_nonce_field( 'sc_guide_products_save', 'sc_guide_products_nonce' );

    $selected = get_post_meta( $post->ID, '_sc_related_products', true );
    $selected = is_array( $selected ) ? array_map( 'absint', $selected ) : [];

    $products = function_exists( 'wc_get_products' ) ? wc_get_products( [
        'status'  => 'publish',
        'limit'   => -1,
        'orderby' => 'title',
        'order'   => 'ASC',
    ] ) : [];

    if ( empty( $products ) ) {
        echo '<p>No hay productos publicados.</p>';
        return;
    }
    ?>
    <p class="description">
        Elegí los cafés que se muestran como "Recomendados para esta preparación"
        al final de esta guía. Si no marcás ninguno, esa sección no aparece.
    </p>
    <?php foreach ( $products as $sc_product ) :
        $sc_pid   = $sc_product->get_id();
        $sc_pais  = sc_get_product_meta( $sc_pid, 'pais' );
        $sc_label = $sc_product->get_name() . ( $sc_pais ? ' — ' . $sc_pais : '' );
    ?>
        <label style="display:block;margin-bottom:6px;">
            <input type="checkbox" name="sc_related_products[]"
                   value="<?php echo esc_attr( $sc_pid ); ?>"
                   <?php checked( in_array( $sc_pid, $selected, true ) ); ?>>
            <?php echo esc_html( $sc_label ); ?>
        </label>
    <?php endforeach; ?>
    <?php
}

add_action( 'save_post_post', function ( int $post_id ): void {
    if ( ! isset( $_POST['sc_guide_products_nonce'] )
        || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['sc_guide_products_nonce'] ) ), 'sc_guide_products_save' ) ) {
        return;
    }
    if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
        return;
    }
    if ( ! current_user_can( 'edit_post', $post_id ) ) {
        return;
    }

    $ids = ( isset( $_POST['sc_related_products'] ) && is_array( $_POST['sc_related_products'] ) )
        ? array_map( 'absint', wp_unslash( $_POST['sc_related_products'] ) )
        : [];

    update_post_meta( $post_id, '_sc_related_products', $ids );
} );
