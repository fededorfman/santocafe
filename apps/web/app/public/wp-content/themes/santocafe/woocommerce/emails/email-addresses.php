<?php
/**
 * Email Addresses — Santo Café override.
 *
 * Muestra SOLO la dirección de envío (sin facturación), prolija y por líneas
 * (nombre, empresa, calle, localidad, teléfono), al estilo de los paneles de
 * "Mi cuenta". Si el pedido no tiene envío, cae a la de facturación.
 *
 * @see woocommerce/templates/emails/email-addresses.php
 * @package santocafe
 * @version 10.6.0
 */

defined( 'ABSPATH' ) || exit;

/* Elegimos envío; si no hay, usamos facturación como respaldo. */
$sc_type = ( $order->needs_shipping_address() && $order->get_formatted_shipping_address() ) ? 'shipping' : 'billing';

$sc_get = static function ( $key ) use ( $order, $sc_type ) {
	$method = "get_{$sc_type}_{$key}";
	return is_callable( array( $order, $method ) ) ? trim( (string) $order->$method() ) : '';
};

$sc_name   = trim( $sc_get( 'first_name' ) . ' ' . $sc_get( 'last_name' ) );
$sc_company = $sc_get( 'company' );
$sc_street  = trim( $sc_get( 'address_1' ) . ( $sc_get( 'address_2' ) ? ', ' . $sc_get( 'address_2' ) : '' ) );

$sc_country_code = $sc_get( 'country' );
$sc_state_code   = $sc_get( 'state' );
$sc_country_name = isset( WC()->countries->countries[ $sc_country_code ] ) ? WC()->countries->countries[ $sc_country_code ] : $sc_country_code;
$sc_states       = WC()->countries->get_states( $sc_country_code );
$sc_state_name   = ( is_array( $sc_states ) && isset( $sc_states[ $sc_state_code ] ) ) ? $sc_states[ $sc_state_code ] : $sc_state_code;
$sc_locality     = implode( ', ', array_filter( array( $sc_get( 'city' ), $sc_state_name, $sc_country_name ) ) );
$sc_postcode     = $sc_get( 'postcode' );
$sc_phone        = $sc_get( 'phone' );
if ( '' === $sc_phone ) {
	$sc_phone = $order->get_billing_phone();
}

$sc_title = ( 'shipping' === $sc_type ) ? 'Dirección de envío' : 'Dirección de facturación';
?>
<hr style="border: 0; border-top: 1px solid #1E1E1E; border-top-color: rgba(30, 30, 30, 0.2); margin: 20px 0;">
<table id="addresses" cellspacing="0" cellpadding="0" style="width:100%; margin-bottom:0; padding:0;" border="0" role="presentation">
	<tr>
		<td class="font-family text-align-left" style="border:0; padding:0;" valign="top">
			<b class="address-title"><?php echo esc_html( $sc_title ); ?></b>
			<address class="address">
				<?php if ( $sc_name ) : ?>
					<strong style="color:#1a1310;"><?php echo esc_html( $sc_name ); ?></strong><br>
				<?php endif; ?>
				<?php if ( $sc_company ) : ?>
					<?php echo esc_html( $sc_company ); ?><br>
				<?php endif; ?>
				<?php if ( $sc_street ) : ?>
					<?php echo esc_html( $sc_street ); ?><br>
				<?php endif; ?>
				<?php if ( $sc_locality ) : ?>
					<?php echo esc_html( $sc_locality ); ?><?php echo $sc_postcode ? ' (' . esc_html( $sc_postcode ) . ')' : ''; ?><br>
				<?php endif; ?>
				<?php if ( $sc_phone ) : ?>
					<?php echo wc_make_phone_clickable( $sc_phone ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				<?php endif; ?>
			</address>
		</td>
	</tr>
</table>
<br>
