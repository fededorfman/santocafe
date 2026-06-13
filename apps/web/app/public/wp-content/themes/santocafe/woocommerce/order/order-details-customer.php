<?php
/**
 * Order Customer Details — Santo Café override.
 * Delivery (shipping) address is shown first as "Dirección de entrega";
 * the billing address is shown only when it differs. Misma estética de filas
 * con ícono que "Mis direcciones".
 *
 * @see woocommerce/templates/order/order-details-customer.php
 * @package santocafe
 */

defined( 'ABSPATH' ) || exit;

$sc_shipping = $order->get_formatted_shipping_address();
$sc_billing  = $order->get_formatted_billing_address();

// La dirección de entrega es la de envío si existe; si no, la de facturación.
$sc_delivery_prefix = $sc_shipping ? 'shipping' : 'billing';
$sc_delivery_tel    = $order->get_shipping_phone() ? $order->get_shipping_phone() : $order->get_billing_phone();

// Solo mostramos una tarjeta de facturación aparte si difiere de la entrega.
$sc_show_billing = $sc_billing && $sc_shipping && ( $sc_billing !== $sc_shipping );

/** Pin del encabezado de la tarjeta. */
$sc_pin = '<svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M21 10c0 7-9 12-9 12s-9-5-9-12a9 9 0 0 1 18 0Z"/><circle cx="12" cy="10" r="3"/></svg>';

// Íconos de las filas (Lucide, stroke 1.6).
$sc_ic    = static function ( $paths ) {
	return '<svg class="sc-address-line__icon" viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' . $paths . '</svg>';
};
$sc_icons = array(
	'person'  => $sc_ic( '<circle cx="12" cy="8" r="4"/><path d="M4 20c0-4 3.6-7 8-7s8 3 8 7"/>' ),
	'company' => $sc_ic( '<path d="M3 21h18"/><path d="M5 21V5a2 2 0 0 1 2-2h6a2 2 0 0 1 2 2v16"/><path d="M19 21V11a2 2 0 0 0-2-2h-2"/><path d="M9 7h2M9 11h2M9 15h2"/>' ),
	'home'    => $sc_ic( '<path d="M3 10.5 12 3l9 7.5"/><path d="M5 9.5V21h14V9.5"/><path d="M9 21v-6h6v6"/>' ),
	'globe'   => $sc_ic( '<circle cx="12" cy="12" r="10"/><path d="M2 12h20"/><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10Z"/>' ),
	'phone'   => $sc_ic( '<path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72c.13.96.36 1.9.7 2.81a2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45c.91.34 1.85.57 2.81.7A2 2 0 0 1 22 16.92Z"/>' ),
	'mail'    => $sc_ic( '<rect x="2" y="4" width="20" height="16" rx="2"/><path d="m22 7-10 5L2 7"/>' ),
);

// Arma las filas [icono, texto] de una dirección del pedido (envío o facturación).
$sc_address_rows = static function ( $prefix ) use ( $order, $sc_icons ) {
	$g = static function ( $field ) use ( $order, $prefix ) {
		$method = "get_{$prefix}_{$field}";
		return is_callable( array( $order, $method ) ) ? trim( (string) $order->$method() ) : '';
	};

	$name   = trim( $g( 'first_name' ) . ' ' . $g( 'last_name' ) );
	$street = trim( $g( 'address_1' ) . ( $g( 'address_2' ) ? ', ' . $g( 'address_2' ) : '' ) );

	// Región y país a nombre legible.
	$country_code = $g( 'country' );
	$state_code   = $g( 'state' );
	$country_name = isset( WC()->countries->countries[ $country_code ] ) ? WC()->countries->countries[ $country_code ] : $country_code;
	$states       = WC()->countries->get_states( $country_code );
	$state_name   = ( is_array( $states ) && isset( $states[ $state_code ] ) ) ? $states[ $state_code ] : $state_code;
	$locality     = implode( ', ', array_filter( array( $g( 'city' ), $state_name, $country_name ) ) );

	$rows = array();
	if ( $name ) {
		$rows[] = array( $sc_icons['person'], $name );
	}
	if ( $g( 'company' ) ) {
		$rows[] = array( $sc_icons['company'], $g( 'company' ) );
	}
	if ( $street ) {
		$rows[] = array( $sc_icons['home'], $street );
	}
	if ( $locality ) {
		$rows[] = array( $sc_icons['globe'], $locality );
	}
	return $rows;
};

// Filas de la entrega + contacto (teléfono y email).
$sc_delivery_rows = $sc_address_rows( $sc_delivery_prefix );
if ( $sc_delivery_tel ) {
	$sc_delivery_rows[] = array( $sc_icons['phone'], $sc_delivery_tel );
}
if ( $order->get_billing_email() ) {
	$sc_delivery_rows[] = array( $sc_icons['mail'], $order->get_billing_email() );
}
?>
<section class="woocommerce-customer-details">
    <div class="sc-address-cards">

        <div class="sc-address-card">
            <div class="sc-address-card__head">
                <span class="sc-address-card__icon" aria-hidden="true"><?php echo $sc_pin; // phpcs:ignore WordPress.Security.EscapeOutput ?></span>
                <h2 class="sc-address-card__title">Dirección de entrega</h2>
            </div>
            <div class="sc-address-card__body">
                <?php if ( $sc_delivery_rows ) : ?>
                    <div class="sc-address-card__lines">
                        <?php foreach ( $sc_delivery_rows as $sc_row ) : ?>
                            <p class="sc-address-line">
                                <?php echo $sc_row[0]; // phpcs:ignore WordPress.Security.EscapeOutput ?>
                                <span><?php echo esc_html( $sc_row[1] ); ?></span>
                            </p>
                        <?php endforeach; ?>
                    </div>
                <?php else : ?>
                    <p class="sc-address-card__empty-text">No disponible</p>
                <?php endif; ?>
                <?php do_action( 'woocommerce_order_details_after_customer_address', 'shipping', $order ); ?>
            </div>
        </div>

        <?php if ( $sc_show_billing ) : ?>
        <?php $sc_billing_rows = $sc_address_rows( 'billing' ); ?>
        <div class="sc-address-card">
            <div class="sc-address-card__head">
                <span class="sc-address-card__icon" aria-hidden="true"><?php echo $sc_pin; // phpcs:ignore WordPress.Security.EscapeOutput ?></span>
                <h2 class="sc-address-card__title">Dirección de facturación</h2>
            </div>
            <div class="sc-address-card__body">
                <div class="sc-address-card__lines">
                    <?php foreach ( $sc_billing_rows as $sc_row ) : ?>
                        <p class="sc-address-line">
                            <?php echo $sc_row[0]; // phpcs:ignore WordPress.Security.EscapeOutput ?>
                            <span><?php echo esc_html( $sc_row[1] ); ?></span>
                        </p>
                    <?php endforeach; ?>
                </div>
                <?php do_action( 'woocommerce_order_details_after_customer_address', 'billing', $order ); ?>
            </div>
        </div>
        <?php endif; ?>

    </div>

    <?php do_action( 'woocommerce_order_details_after_customer_details', $order ); ?>
</section>
