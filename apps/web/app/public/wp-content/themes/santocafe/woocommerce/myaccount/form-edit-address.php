<?php
/**
 * Edit address form — Santo Café override.
 *
 * Same WooCommerce hooks/nonce/save action as core, plus:
 *  - .js-validate → reusable per-field validation (see main.js FormValidate),
 *    which marks empty-required / invalid fields after pressing "Guardar".
 *  - White card panel + single-column layout (Nombres/Apellidos share a row).
 *  - "Volver a mis direcciones" link on top, "Cancelar" link at the bottom.
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

// Nombres + Apellidos share a row; everything else is full width.
$sc_name_keys = array( 'billing_first_name', 'billing_last_name', 'shipping_first_name', 'shipping_last_name' );

// Back / cancel target: the addresses overview.
$sc_back_url = wc_get_account_endpoint_url( 'edit-address' );

do_action( 'woocommerce_before_edit_account_address_form' ); ?>

<?php if ( ! $load_address ) : ?>
	<?php wc_get_template( 'myaccount/my-address.php' ); ?>
<?php else : ?>

	<a href="<?php echo esc_url( $sc_back_url ); ?>" class="sc-form-back">
		<span aria-hidden="true">&larr;</span> Volver a mis direcciones
	</a>

	<form method="post" class="sc-address-form sc-form js-validate" novalidate>

		<div class="sc-form-card">
			<h2 class="sc-form-card__title"><?php echo esc_html( apply_filters( 'woocommerce_my_account_edit_address_title', $page_title, $load_address ) ); ?></h2>

			<div class="woocommerce-address-fields">
				<?php do_action( "woocommerce_before_edit_address_form_{$load_address}" ); ?>

				<div class="woocommerce-address-fields__field-wrapper sc-form-grid">
					<?php
					foreach ( $address as $key => $field ) {
						if ( in_array( $key, $sc_name_keys, true ) ) {
							$field['class']   = isset( $field['class'] ) ? (array) $field['class'] : array();
							$field['class'][] = 'sc-name-col';
						}
						woocommerce_form_field( $key, $field, wc_get_post_data_by_key( $key, $field['value'] ) );
					}
					?>
				</div>

				<?php do_action( "woocommerce_after_edit_address_form_{$load_address}" ); ?>

				<div class="sc-form-actions">
					<a href="<?php echo esc_url( $sc_back_url ); ?>" class="sc-form-cancel">Cancelar</a>
					<button type="submit" class="button" name="save_address" value="Guardar dirección">Guardar dirección</button>
					<?php wp_nonce_field( 'woocommerce-edit_address', 'woocommerce-edit-address-nonce' ); ?>
					<input type="hidden" name="action" value="edit_address" />
				</div>
			</div>
		</div>

	</form>

<?php endif; ?>

<?php do_action( 'woocommerce_after_edit_account_address_form' ); ?>
