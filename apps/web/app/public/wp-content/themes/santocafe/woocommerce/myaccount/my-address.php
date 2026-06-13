<?php
/**
 * My Addresses — Santo Café override.
 *
 * Cards in the site style (crema + dorado), each with a formatted address
 * or a calm empty state and an "Editar / Agregar" action.
 *
 * @see https://woocommerce.com/document/template-structure/
 * @package santocafe
 */

defined( 'ABSPATH' ) || exit;

$customer_id = get_current_user_id();

if ( ! wc_ship_to_billing_address_only() && wc_shipping_enabled() ) {
	$get_addresses = apply_filters(
		'woocommerce_my_account_get_addresses',
		array(
			'shipping' => __( 'Dirección de envío', 'santocafe' ),
			'billing'  => __( 'Dirección de facturación', 'santocafe' ),
		),
		$customer_id
	);
} else {
	$get_addresses = apply_filters(
		'woocommerce_my_account_get_addresses',
		array(
			'billing' => __( 'Dirección de facturación', 'santocafe' ),
		),
		$customer_id
	);
}

// Datos para el botón "Usar dirección de envío" (solo en la tarjeta de
// facturación cuando hay tarjeta de envío) y para mostrar el teléfono.
$sc_customer        = new WC_Customer( $customer_id );
$sc_has_shipping    = array_key_exists( 'shipping', $get_addresses );
$sc_shipping_filled = $sc_has_shipping && (bool) wc_get_account_formatted_address( 'shipping' );
?>

<p class="sc-address-intro">
	Estas direcciones se usan por defecto al finalizar tu compra.
</p>

<div class="sc-address-cards">
	<?php foreach ( $get_addresses as $name => $address_title ) : ?>
		<?php
		$address  = wc_get_account_formatted_address( $name );
		$edit_url = esc_url( wc_get_endpoint_url( 'edit-address', $name ) );
		$phone    = ( 'billing' === $name )
			? $sc_customer->get_billing_phone()
			: ( is_callable( array( $sc_customer, 'get_shipping_phone' ) ) ? $sc_customer->get_shipping_phone() : '' );
		?>
		<div class="sc-address-card<?php echo $address ? '' : ' sc-address-card--empty'; ?>">
			<div class="sc-address-card__head">
				<span class="sc-address-card__icon" aria-hidden="true">
					<svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">
						<path d="M21 10c0 7-9 12-9 12s-9-5-9-12a9 9 0 0 1 18 0Z" />
						<circle cx="12" cy="10" r="3" />
					</svg>
				</span>
				<h2 class="sc-address-card__title"><?php echo esc_html( $address_title ); ?></h2>
			</div>

			<div class="sc-address-card__body">
				<?php if ( $address ) : ?>
					<address><?php echo wp_kses_post( $address ); ?></address>
					<?php if ( $phone ) : ?>
						<p class="sc-address-card__phone">
							<svg viewBox="0 0 24 24" width="15" height="15" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
								<path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72c.13.96.36 1.9.7 2.81a2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45c.91.34 1.85.57 2.81.7A2 2 0 0 1 22 16.92Z" />
							</svg>
							<?php echo esc_html( $phone ); ?>
						</p>
					<?php endif; ?>
				<?php else : ?>
					<p class="sc-address-card__empty-text">Todavía no cargaste esta dirección.</p>
				<?php endif; ?>
				<?php
				/**
				 * Hook: woocommerce_my_account_after_my_address.
				 *
				 * @since 8.7.0
				 */
				do_action( 'woocommerce_my_account_after_my_address', $name );
				?>
			</div>

			<div class="sc-address-card__actions">
				<a href="<?php echo $edit_url; ?>" class="sc-address-card__edit">
					<?php echo $address ? 'Editar dirección' : 'Agregar dirección'; ?>
				</a>

				<?php if ( 'billing' === $name && $sc_has_shipping ) : ?>
					<button type="button" class="sc-address-card__copy js-copy-shipping"
						data-has-billing="<?php echo $address ? '1' : '0'; ?>"
						<?php disabled( ! $sc_shipping_filled ); ?>
						<?php echo $sc_shipping_filled ? '' : 'title="Primero cargá tu dirección de envío"'; ?>>
						Usar dirección de envío
					</button>
				<?php endif; ?>
			</div>
		</div>
	<?php endforeach; ?>
</div>
