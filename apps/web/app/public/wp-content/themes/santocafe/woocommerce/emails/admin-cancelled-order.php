<?php
/**
 * Admin cancelled order email — Santo Café override.
 *
 * Aviso interno cuando un pedido se cancela. Conserva los hooks dinámicos;
 * copy operativo (español de Chile).
 *
 * @see woocommerce/templates/emails/admin-cancelled-order.php
 * @package santocafe
 * @version 9.8.0
 */

defined( 'ABSPATH' ) || exit;

do_action( 'woocommerce_email_header', $email_heading, $email ); ?>

<p>El pedido <strong>#<?php echo esc_html( $order->get_order_number() ); ?></strong> de <strong><?php echo esc_html( $order->get_formatted_billing_full_name() ); ?></strong> fue cancelado.</p>

<?php
do_action( 'woocommerce_email_order_details', $order, $sent_to_admin, $plain_text, $email );
do_action( 'woocommerce_email_order_meta', $order, $sent_to_admin, $plain_text, $email );
do_action( 'woocommerce_email_customer_details', $order, $sent_to_admin, $plain_text, $email );

if ( $additional_content ) {
	echo wp_kses_post( wpautop( wptexturize( $additional_content ) ) );
}

do_action( 'woocommerce_email_footer', $email );
