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
?>

<p class="sc-address-intro">
	Estas direcciones se usan por defecto al finalizar tu compra.
</p>

<div class="sc-address-cards">
	<?php foreach ( $get_addresses as $name => $address_title ) : ?>
		<?php
		$address  = wc_get_account_formatted_address( $name );
		$edit_url = esc_url( wc_get_endpoint_url( 'edit-address', $name ) );
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

			<a href="<?php echo $edit_url; ?>" class="sc-address-card__edit">
				<?php echo $address ? 'Editar dirección' : 'Agregar dirección'; ?>
			</a>
		</div>
	<?php endforeach; ?>
</div>
