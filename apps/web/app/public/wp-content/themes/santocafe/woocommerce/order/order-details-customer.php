<?php
/**
 * Order Customer Details — Santo Café override.
 * Renders billing/shipping as the same .sc-address-card used in
 * "Mis direcciones", for a consistent look.
 *
 * @see woocommerce/templates/order/order-details-customer.php
 * @package santocafe
 */

defined( 'ABSPATH' ) || exit;

$show_shipping = ! wc_ship_to_billing_address_only() && $order->needs_shipping_address();

/** Small helper: the location-pin icon used by the address cards. */
$sc_pin = '<svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M21 10c0 7-9 12-9 12s-9-5-9-12a9 9 0 0 1 18 0Z"/><circle cx="12" cy="10" r="3"/></svg>';
?>
<section class="woocommerce-customer-details">
    <div class="sc-address-cards">

        <div class="sc-address-card">
            <div class="sc-address-card__head">
                <span class="sc-address-card__icon" aria-hidden="true"><?php echo $sc_pin; // phpcs:ignore ?></span>
                <h2 class="sc-address-card__title">Dirección de facturación</h2>
            </div>
            <div class="sc-address-card__body">
                <address>
                    <?php echo wp_kses_post( $order->get_formatted_billing_address( 'No disponible' ) ); ?>
                    <?php if ( $order->get_billing_phone() ) : ?>
                        <span class="sc-address-card__line"><?php echo esc_html( $order->get_billing_phone() ); ?></span>
                    <?php endif; ?>
                    <?php if ( $order->get_billing_email() ) : ?>
                        <span class="sc-address-card__line"><?php echo esc_html( $order->get_billing_email() ); ?></span>
                    <?php endif; ?>
                    <?php do_action( 'woocommerce_order_details_after_customer_address', 'billing', $order ); ?>
                </address>
            </div>
        </div>

        <?php if ( $show_shipping ) : ?>
        <div class="sc-address-card">
            <div class="sc-address-card__head">
                <span class="sc-address-card__icon" aria-hidden="true"><?php echo $sc_pin; // phpcs:ignore ?></span>
                <h2 class="sc-address-card__title">Dirección de envío</h2>
            </div>
            <div class="sc-address-card__body">
                <address>
                    <?php echo wp_kses_post( $order->get_formatted_shipping_address( 'No disponible' ) ); ?>
                    <?php if ( $order->get_shipping_phone() ) : ?>
                        <span class="sc-address-card__line"><?php echo esc_html( $order->get_shipping_phone() ); ?></span>
                    <?php endif; ?>
                    <?php do_action( 'woocommerce_order_details_after_customer_address', 'shipping', $order ); ?>
                </address>
            </div>
        </div>
        <?php endif; ?>

    </div>

    <?php do_action( 'woocommerce_order_details_after_customer_details', $order ); ?>
</section>
