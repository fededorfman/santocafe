<?php
/**
 * Order Customer Details — Santo Café override.
 * Delivery (shipping) address is shown first as "Dirección de entrega";
 * the billing address is shown only when it differs. Same .sc-address-card
 * look as "Mis direcciones".
 *
 * @see woocommerce/templates/order/order-details-customer.php
 * @package santocafe
 */

defined( 'ABSPATH' ) || exit;

$sc_shipping = $order->get_formatted_shipping_address();
$sc_billing  = $order->get_formatted_billing_address();

// The delivery address is the shipping one if present, otherwise billing.
$sc_delivery     = $sc_shipping ? $sc_shipping : $sc_billing;
$sc_delivery_tel = $order->get_shipping_phone() ? $order->get_shipping_phone() : $order->get_billing_phone();

// Only show a separate billing card when it actually differs from delivery.
$sc_show_billing = $sc_billing && $sc_shipping && ( $sc_billing !== $sc_shipping );

/** Location-pin icon used by the address cards. */
$sc_pin = '<svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M21 10c0 7-9 12-9 12s-9-5-9-12a9 9 0 0 1 18 0Z"/><circle cx="12" cy="10" r="3"/></svg>';
?>
<section class="woocommerce-customer-details">
    <div class="sc-address-cards">

        <div class="sc-address-card">
            <div class="sc-address-card__head">
                <span class="sc-address-card__icon" aria-hidden="true"><?php echo $sc_pin; // phpcs:ignore ?></span>
                <h2 class="sc-address-card__title">Dirección de entrega</h2>
            </div>
            <div class="sc-address-card__body">
                <address>
                    <?php echo wp_kses_post( $sc_delivery ? $sc_delivery : 'No disponible' ); ?>
                    <?php if ( $sc_delivery_tel ) : ?>
                        <span class="sc-address-card__line"><?php echo esc_html( $sc_delivery_tel ); ?></span>
                    <?php endif; ?>
                    <?php if ( $order->get_billing_email() ) : ?>
                        <span class="sc-address-card__line"><?php echo esc_html( $order->get_billing_email() ); ?></span>
                    <?php endif; ?>
                    <?php do_action( 'woocommerce_order_details_after_customer_address', 'shipping', $order ); ?>
                </address>
            </div>
        </div>

        <?php if ( $sc_show_billing ) : ?>
        <div class="sc-address-card">
            <div class="sc-address-card__head">
                <span class="sc-address-card__icon" aria-hidden="true"><?php echo $sc_pin; // phpcs:ignore ?></span>
                <h2 class="sc-address-card__title">Dirección de facturación</h2>
            </div>
            <div class="sc-address-card__body">
                <address>
                    <?php echo wp_kses_post( $sc_billing ); ?>
                    <?php do_action( 'woocommerce_order_details_after_customer_address', 'billing', $order ); ?>
                </address>
            </div>
        </div>
        <?php endif; ?>

    </div>

    <?php do_action( 'woocommerce_order_details_after_customer_details', $order ); ?>
</section>
