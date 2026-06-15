<?php
/**
 * My Addresses — Santo Café override.
 *
 * Cards in the site style (crema + dorado), each with the address broken into
 * icon rows (nombre, calle, localidad, teléfono) or a calm empty state, plus an
 * "Editar / Agregar" action and the "Usar dirección de envío" shortcut.
 *
 * @see https://woocommerce.com/document/template-structure/
 * @package santocafe
 * @version 9.3.0
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

// Íconos (Lucide, stroke 1.6) reutilizados en las filas de cada tarjeta.
$sc_icon = static function ( $paths ) {
	return '<svg class="sc-address-line__icon" viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' . $paths . '</svg>';
};
$sc_icon_person   = '<circle cx="12" cy="8" r="4"/><path d="M4 20c0-4 3.6-7 8-7s8 3 8 7"/>';
$sc_icon_company  = '<path d="M3 21h18"/><path d="M5 21V5a2 2 0 0 1 2-2h6a2 2 0 0 1 2 2v16"/><path d="M19 21V11a2 2 0 0 0-2-2h-2"/><path d="M9 7h2M9 11h2M9 15h2"/>';
$sc_icon_street   = '<path d="M3 10.5 12 3l9 7.5"/><path d="M5 9.5V21h14V9.5"/><path d="M9 21v-6h6v6"/>';
$sc_icon_locality = '<circle cx="12" cy="12" r="10"/><path d="M2 12h20"/><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10Z"/>';
$sc_icon_phone    = '<path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72c.13.96.36 1.9.7 2.81a2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45c.91.34 1.85.57 2.81.7A2 2 0 0 1 22 16.92Z"/>';
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

		// Campos individuales (user meta con prefijo billing_/shipping_) para
		// armar las filas con ícono.
		$f = static function ( $key ) use ( $customer_id, $name ) {
			return trim( (string) get_user_meta( $customer_id, "{$name}_{$key}", true ) );
		};
		$sc_full_name = trim( $f( 'first_name' ) . ' ' . $f( 'last_name' ) );
		$sc_company   = $f( 'company' );
		$sc_street    = trim( $f( 'address_1' ) . ( $f( 'address_2' ) ? ', ' . $f( 'address_2' ) : '' ) );

		// Región y país: convertir códigos a nombre legible.
		$sc_country_code = $f( 'country' );
		$sc_state_code   = $f( 'state' );
		$sc_country_name = isset( WC()->countries->countries[ $sc_country_code ] )
			? WC()->countries->countries[ $sc_country_code ]
			: $sc_country_code;
		$sc_states       = WC()->countries->get_states( $sc_country_code );
		$sc_state_name   = ( is_array( $sc_states ) && isset( $sc_states[ $sc_state_code ] ) )
			? $sc_states[ $sc_state_code ]
			: $sc_state_code;

		// Localidad en una sola línea: ciudad, región, país (sin vacíos).
		$sc_locality = implode( ', ', array_filter( array( $f( 'city' ), $sc_state_name, $sc_country_name ) ) );
		$sc_postcode = $f( 'postcode' );
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
					<div class="sc-address-card__lines">
						<?php if ( $sc_full_name ) : ?>
							<p class="sc-address-line">
								<?php echo $sc_icon( $sc_icon_person ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
								<span><?php echo esc_html( $sc_full_name ); ?></span>
							</p>
						<?php endif; ?>
						<?php if ( $sc_company ) : ?>
							<p class="sc-address-line">
								<?php echo $sc_icon( $sc_icon_company ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
								<span><?php echo esc_html( $sc_company ); ?></span>
							</p>
						<?php endif; ?>
						<?php if ( $sc_street ) : ?>
							<p class="sc-address-line">
								<?php echo $sc_icon( $sc_icon_street ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
								<span><?php echo esc_html( $sc_street ); ?></span>
							</p>
						<?php endif; ?>
						<?php if ( $sc_locality ) : ?>
							<p class="sc-address-line">
								<?php echo $sc_icon( $sc_icon_locality ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
								<span>
									<?php echo esc_html( $sc_locality ); ?><?php echo $sc_postcode ? ' <span class="sc-address-line__muted">(' . esc_html( $sc_postcode ) . ')</span>' : ''; // phpcs:ignore WordPress.Security.EscapeOutput ?>
								</span>
							</p>
						<?php endif; ?>
						<?php if ( $phone ) : ?>
							<p class="sc-address-line">
								<?php echo $sc_icon( $sc_icon_phone ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
								<span><?php echo esc_html( $phone ); ?></span>
							</p>
						<?php endif; ?>
					</div>
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
