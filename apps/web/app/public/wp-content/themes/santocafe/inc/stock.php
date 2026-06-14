<?php
defined( 'ABSPATH' ) || exit;

/**
 * Stock por gramos para los cafés.
 *
 * Las variaciones de peso (250g / 1kg) del mismo café comparten un único pool de
 * stock, expresado en GRAMOS, administrado como stock nativo del PRODUCTO PADRE
 * (pestaña Inventario → "Gestionar stock" en el padre, cantidad = gramos totales;
 * las variaciones NO gestionan su propio stock, heredan del padre).
 *
 * 250g consume 250, 1kg consume 1000. El descuento/reposición real lo hace
 * WooCommerce vía el filtro woocommerce_order_item_quantity (unidades × gramos),
 * y la validación de "no vender lo que no hay" la hacen los filtros de carrito.
 *
 * Requiere: WooCommerce → Ajustes → Productos → Inventario → "Gestionar stock" = ON.
 */

/** Gramos que consume un slug de peso. '250g'→250, '1kg'→1000, '500g'→500. */
function sc_peso_to_grams( string $peso_slug ): int {
    $s = strtolower( trim( $peso_slug ) );
    if ( ! preg_match( '/^([\d.,]+)\s*(kg|g)$/', $s, $m ) ) {
        return 0;
    }
    $n = (float) str_replace( ',', '.', $m[1] );
    return (int) round( 'kg' === $m[2] ? $n * 1000 : $n );
}

/** Gramos por unidad de un producto (solo variaciones con atributo peso). 0 si no aplica. */
function sc_product_unit_grams( $product ): int {
    if ( ! $product instanceof WC_Product_Variation ) {
        return 0;
    }
    $attrs = $product->get_variation_attributes();
    $peso  = $attrs['attribute_pa_peso'] ?? '';
    return $peso ? sc_peso_to_grams( (string) $peso ) : 0;
}

/** Pool de gramos disponible para un producto. null = sin gestión de stock (ilimitado). */
function sc_available_grams( $product ): ?int {
    if ( ! $product instanceof WC_Product ) {
        return null;
    }
    $managed = wc_get_product( $product->get_stock_managed_by_id() );
    if ( ! $managed || ! $managed->managing_stock() ) {
        return null;
    }
    $qty = $managed->get_stock_quantity();
    return null === $qty ? null : (int) $qty;
}

/** Gramos ya comprometidos en el carrito para un café (producto que gestiona stock). */
function sc_grams_in_cart_for( int $managed_id, ?string $exclude_key = null ): int {
    if ( ! function_exists( 'WC' ) || ! WC()->cart ) {
        return 0;
    }
    $total = 0;
    foreach ( WC()->cart->get_cart() as $key => $item ) {
        if ( $exclude_key && $key === $exclude_key ) {
            continue;
        }
        $p = $item['data'] ?? null;
        if ( $p instanceof WC_Product && (int) $p->get_stock_managed_by_id() === $managed_id ) {
            $total += sc_product_unit_grams( $p ) * (int) $item['quantity'];
        }
    }
    return $total;
}

/** ¿Se pueden agregar $qty unidades de $product sin pasar el stock? (cuenta el carrito). */
function sc_can_add_grams( $product, int $qty, ?string $exclude_key = null ): bool {
    $avail = sc_available_grams( $product );
    if ( null === $avail ) {
        return true; // sin gestión de stock → ilimitado
    }
    $unit = sc_product_unit_grams( $product );
    if ( $unit <= 0 ) {
        return true; // producto sin peso → lo maneja WC nativamente
    }
    $in_cart = sc_grams_in_cart_for( (int) $product->get_stock_managed_by_id(), $exclude_key );
    return ( $in_cart + $qty * $unit ) <= $avail;
}

/**
 * Estado de stock por peso de un café, para los botones: [ '250g' => bool, '1kg' => bool ].
 * Comprable si el pool es ilimitado o alcanza para al menos 1 unidad de ese peso.
 */
function sc_weight_stock_states( int $parent_id ): array {
    $states  = array();
    $product = wc_get_product( $parent_id );
    if ( ! $product || ! $product->is_type( 'variable' ) ) {
        return $states;
    }
    foreach ( $product->get_available_variations() as $var ) {
        $peso = $var['attributes']['attribute_pa_peso'] ?? '';
        if ( ! $peso ) {
            continue;
        }
        $vp    = wc_get_product( $var['variation_id'] );
        $avail = $vp ? sc_available_grams( $vp ) : null;
        $unit  = sc_peso_to_grams( (string) $peso );
        $states[ $peso ] = ( null === $avail ) || ( $unit > 0 && $avail >= $unit );
    }
    return $states;
}

// --- Validación: no agregar al carrito más gramos de los que hay ---
add_filter( 'woocommerce_add_to_cart_validation', function ( $passed, $product_id, $qty, $variation_id = 0, $variations = array() ) {
    $product = wc_get_product( $variation_id ? $variation_id : $product_id );
    if ( $product && ! sc_can_add_grams( $product, (int) $qty ) ) {
        wc_add_notice( sprintf( 'No nos queda stock suficiente de %s para esa cantidad.', $product->get_name() ), 'error' );
        return false;
    }
    return $passed;
}, 10, 5 );

// --- Validación al cambiar cantidades en el carrito ---
add_filter( 'woocommerce_update_cart_validation', function ( $passed, $cart_item_key, $values, $quantity ) {
    $product = $values['data'] ?? null;
    if ( $product instanceof WC_Product && ! sc_can_add_grams( $product, (int) $quantity, $cart_item_key ) ) {
        wc_add_notice( sprintf( 'No nos queda stock suficiente de %s para esa cantidad.', $product->get_name() ), 'error' );
        return false;
    }
    return $passed;
}, 10, 4 );

// --- Revalidación final en carrito/checkout (suma gramos por café) ---
add_action( 'woocommerce_check_cart_items', function () {
    if ( ! function_exists( 'WC' ) || ! WC()->cart ) {
        return;
    }
    $by_managed = array();
    foreach ( WC()->cart->get_cart() as $item ) {
        $p = $item['data'] ?? null;
        if ( ! $p instanceof WC_Product ) {
            continue;
        }
        $avail = sc_available_grams( $p );
        if ( null === $avail ) {
            continue;
        }
        $mid = (int) $p->get_stock_managed_by_id();
        $by_managed[ $mid ]['grams'] = ( $by_managed[ $mid ]['grams'] ?? 0 ) + sc_product_unit_grams( $p ) * (int) $item['quantity'];
        $by_managed[ $mid ]['avail'] = $avail;
        $by_managed[ $mid ]['name']  = $p->get_name();
    }
    foreach ( $by_managed as $data ) {
        if ( $data['grams'] > $data['avail'] ) {
            wc_add_notice( sprintf( 'No nos queda stock suficiente de %s. Ajustá la cantidad.', $data['name'] ), 'error' );
        }
    }
} );

// --- Descuento/reposición de stock = unidades × gramos (el padre lleva el pool en gramos) ---
add_filter( 'woocommerce_order_item_quantity', function ( $qty, $order, $item ) {
    $product = $item->get_product();
    $unit    = $product ? sc_product_unit_grams( $product ) : 0;
    return $unit > 0 ? $qty * $unit : $qty;
}, 10, 3 );
