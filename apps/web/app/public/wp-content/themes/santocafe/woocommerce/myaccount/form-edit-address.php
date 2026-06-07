<?php
/**
 * Edit address form — Santo Café override.
 *
 * Same WooCommerce hooks/nonce/save action as core, plus:
 *  - .js-validate → reusable per-field validation (see main.js FormValidate),
 *    which marks empty-required / invalid fields after pressing "Guardar".
 *  - País / Región / Ciudad grouped together (.sc-geo-col) in one row.
 *
 * @see https://woocommerce.com/document/template-structure/
 * @package santocafe
 */

defined( 'ABSPATH' ) || exit;

$sc_titles  = array(
	'billing'  => 'Dirección de facturación',
	'shipping' => 'Dirección de envío',
);
$page_title = $sc_titles[ $load_address ] ?? 'Dirección';

// Fields grouped together in one row.
$sc_geo_keys = array( 'billing_country', 'billing_state', 'billing_city', 'shipping_country', 'shipping_state', 'shipping_city' );

do_action( 'woocommerce_before_edit_account_address_form' ); ?>

<?php if ( ! $load_address ) : ?>
	<?php wc_get_template( 'myaccount/my-address.php' ); ?>
<?php else : ?>

	<form method="post" class="sc-address-form js-validate" novalidate>

		<h2><?php echo esc_html( apply_filters( 'woocommerce_my_account_edit_address_title', $page_title, $load_address ) ); ?></h2>

		<div class="woocommerce-address-fields">
			<?php do_action( "woocommerce_before_edit_address_form_{$load_address}" ); ?>

			<div class="woocommerce-address-fields__field-wrapper">
				<?php
				foreach ( $address as $key => $field ) {
					if ( in_array( $key, $sc_geo_keys, true ) ) {
						$field['class']   = isset( $field['class'] ) ? (array) $field['class'] : array();
						$field['class'][] = 'sc-geo-col';
					}
					woocommerce_form_field( $key, $field, wc_get_post_data_by_key( $key, $field['value'] ) );
				}
				?>
			</div>

			<?php do_action( "woocommerce_after_edit_address_form_{$load_address}" ); ?>

			<p>
				<button type="submit" class="button" name="save_address" value="Guardar dirección">Guardar dirección</button>
				<?php wp_nonce_field( 'woocommerce-edit_address', 'woocommerce-edit-address-nonce' ); ?>
				<input type="hidden" name="action" value="edit_address" />
			</p>
		</div>

	</form>

<?php endif; ?>

<?php do_action( 'woocommerce_after_edit_account_address_form' ); ?>
