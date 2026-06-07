<?php
/**
 * Edit account form — Santo Café override (Detalles de la cuenta).
 *
 * Same WooCommerce hooks/nonce/save action as core, with the shared
 * account-form look: white card (.sc-form-card), single-column layout
 * (Nombres/Apellidos share a row), and reusable per-field validation
 * (.js-validate, see main.js FormValidate).
 *
 * @see https://woocommerce.com/document/template-structure/
 * @package santocafe
 */

defined( 'ABSPATH' ) || exit;

do_action( 'woocommerce_before_edit_account_form' );
?>

<form class="woocommerce-EditAccountForm edit-account sc-form js-validate" action="" method="post" novalidate <?php do_action( 'woocommerce_edit_account_form_tag' ); ?> >

	<div class="sc-form-card">
		<h2 class="sc-form-card__title">Detalles de la cuenta</h2>

		<?php do_action( 'woocommerce_edit_account_form_start' ); ?>

		<div class="sc-form-grid">
			<p class="woocommerce-form-row form-row sc-name-col validate-required" id="account_first_name_field">
				<label for="account_first_name">Nombres&nbsp;<span class="required" aria-hidden="true">*</span></label>
				<input type="text" class="woocommerce-Input woocommerce-Input--text input-text" name="account_first_name" id="account_first_name" autocomplete="given-name" value="<?php echo esc_attr( $user->first_name ); ?>" required aria-required="true" />
			</p>
			<p class="woocommerce-form-row form-row sc-name-col validate-required" id="account_last_name_field">
				<label for="account_last_name">Apellidos&nbsp;<span class="required" aria-hidden="true">*</span></label>
				<input type="text" class="woocommerce-Input woocommerce-Input--text input-text" name="account_last_name" id="account_last_name" autocomplete="family-name" value="<?php echo esc_attr( $user->last_name ); ?>" required aria-required="true" />
			</p>

			<p class="woocommerce-form-row form-row validate-required" id="account_display_name_field">
				<label for="account_display_name">Nombre público&nbsp;<span class="required" aria-hidden="true">*</span></label>
				<input type="text" class="woocommerce-Input woocommerce-Input--text input-text" name="account_display_name" id="account_display_name" aria-describedby="account_display_name_description" value="<?php echo esc_attr( $user->display_name ); ?>" required aria-required="true" />
				<span id="account_display_name_description"><em>Así se mostrará tu nombre en la cuenta y en las reseñas.</em></span>
			</p>

			<p class="woocommerce-form-row form-row validate-required validate-email" id="account_email_field">
				<label for="account_email">Correo electrónico&nbsp;<span class="required" aria-hidden="true">*</span></label>
				<input type="email" class="woocommerce-Input woocommerce-Input--email input-text" name="account_email" id="account_email" autocomplete="email" value="<?php echo esc_attr( $user->user_email ); ?>" required aria-required="true" />
			</p>
		</div>

		<?php
			/**
			 * Hook where additional fields should be rendered.
			 *
			 * @since 8.7.0
			 */
			do_action( 'woocommerce_edit_account_form_fields' );
		?>

		<fieldset>
			<legend>Cambiar contraseña</legend>

			<p class="woocommerce-form-row form-row form-row-wide" id="password_current_field">
				<label for="password_current">Contraseña actual (dejar en blanco para no cambiarla)</label>
				<input type="password" class="woocommerce-Input woocommerce-Input--password input-text" name="password_current" id="password_current" autocomplete="current-password" />
			</p>
			<p class="woocommerce-form-row form-row form-row-wide" id="password_1_field">
				<label for="password_1">Nueva contraseña (dejar en blanco para no cambiarla)</label>
				<input type="password" class="woocommerce-Input woocommerce-Input--password input-text" name="password_1" id="password_1" autocomplete="new-password" />
			</p>
			<p class="woocommerce-form-row form-row form-row-wide" id="password_2_field">
				<label for="password_2">Confirmar nueva contraseña</label>
				<input type="password" class="woocommerce-Input woocommerce-Input--password input-text" name="password_2" id="password_2" autocomplete="new-password" />
			</p>
		</fieldset>

		<?php
			/**
			 * My Account edit account form.
			 *
			 * @since 2.6.0
			 */
			do_action( 'woocommerce_edit_account_form' );
		?>

		<div class="sc-form-actions sc-form-actions--end">
			<?php wp_nonce_field( 'save_account_details', 'save-account-details-nonce' ); ?>
			<button type="submit" class="button" name="save_account_details" value="Guardar cambios">Guardar cambios</button>
			<input type="hidden" name="action" value="save_account_details" />
		</div>

		<?php do_action( 'woocommerce_edit_account_form_end' ); ?>
	</div>

</form>

<?php do_action( 'woocommerce_after_edit_account_form' ); ?>
