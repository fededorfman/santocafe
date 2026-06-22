<?php
/**
 * Edit account form — Santo Café override (Detalles de la cuenta).
 *
 * Two independent panels that both post to WooCommerce's native
 * save_account_details handler:
 *   1. Perfil — Nombres, Apellidos y el email (solo lectura, informativo).
 *   2. Cambiar contraseña.
 *
 * Each form carries the *other* panel's values as hidden inputs (unchanged),
 * so guardar un panel nunca pisa los datos del otro. The handler requires
 * first_name / last_name / display_name / email on every submit and only
 * changes the password when password_1 is filled.
 *
 * Shared look via .sc-form / .sc-form-card and reusable .js-validate.
 *
 * @see https://woocommerce.com/document/template-structure/
 * @package santocafe
 * @version 10.5.0
 */

defined( 'ABSPATH' ) || exit;

do_action( 'woocommerce_before_edit_account_form' );
?>

<form class="woocommerce-EditAccountForm edit-account sc-form js-validate" action="" method="post" novalidate <?php do_action( 'woocommerce_edit_account_form_tag' ); ?> >

	<div class="sc-form-card">
		<h2 class="sc-form-card__title">Detalles de la cuenta</h2>

		<?php do_action( 'woocommerce_edit_account_form_start' ); ?>

		<div class="sc-form-grid">
			<div class="form-row sc-form-readonly">
				<span class="sc-form-readonly__label">Correo electrónico</span>
				<span class="sc-form-readonly__value"><?php echo esc_html( $user->user_email ); ?></span>
			</div>

			<p class="woocommerce-form-row form-row sc-name-col validate-required" id="account_first_name_field">
				<label for="account_first_name">Nombres&nbsp;<span class="required" aria-hidden="true">*</span></label>
				<input type="text" class="woocommerce-Input woocommerce-Input--text input-text" name="account_first_name" id="account_first_name" autocomplete="given-name" value="<?php echo esc_attr( $user->first_name ); ?>" required aria-required="true" />
			</p>
			<p class="woocommerce-form-row form-row sc-name-col validate-required" id="account_last_name_field">
				<label for="account_last_name">Apellidos&nbsp;<span class="required" aria-hidden="true">*</span></label>
				<input type="text" class="woocommerce-Input woocommerce-Input--text input-text" name="account_last_name" id="account_last_name" autocomplete="family-name" value="<?php echo esc_attr( $user->last_name ); ?>" required aria-required="true" />
			</p>

			<?php
				// Fecha de nacimiento: editable solo una vez; al estar cargada pasa
				// a solo lectura (para evitar abusos con el regalo de cumpleaños).
				$sc_birthday = get_user_meta( $user->ID, 'sc_birthday', true );
			?>
			<?php if ( $sc_birthday ) : ?>
				<div class="form-row sc-form-readonly" id="account_birthday_field">
					<span class="sc-form-readonly__label">Fecha de nacimiento</span>
					<span class="sc-form-readonly__value"><?php echo esc_html( date_i18n( 'j \d\e F \d\e Y', strtotime( $sc_birthday ) ) ); ?></span>
				</div>
			<?php else : ?>
				<p class="woocommerce-form-row form-row form-row-wide" id="account_birthday_field">
					<label for="sc_birthday">Fecha de nacimiento</label>
					<input type="date" class="woocommerce-Input woocommerce-Input--text input-text" name="sc_birthday" id="sc_birthday"
						max="<?php echo esc_attr( date( 'Y-m-d' ) ); ?>"
						value="<?php echo esc_attr( isset( $_POST['sc_birthday'] ) ? wp_unslash( $_POST['sc_birthday'] ) : '' ); ?>" />
					<span class="sc-form-hint">Queremos saber tu fecha de nacimiento, para poder hacerte un regalo el día de tu cumple :)</span>
				</p>
			<?php endif; ?>

			<?php // Correos promocionales: el toggle refleja lo contrario del opt-out. ?>
			<?php $sc_optout = get_user_meta( $user->ID, 'sc_email_optout', true ); ?>
			<p class="woocommerce-form-row form-row form-row-wide" id="account_promos_field">
				<label class="sc-checkbox" for="sc_email_promos">
					<input type="checkbox" name="sc_email_promos" id="sc_email_promos" value="1" <?php checked( ! $sc_optout ); ?> />
					<span>Recibir correos promocionales</span>
				</label>
				<span class="sc-form-hint">Descuentos, promociones y novedades de Santo Café. Los correos sobre tus pedidos los recibirás igual.</span>
				<input type="hidden" name="sc_email_promos_present" value="1" />
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

		<?php // Carry the values this panel doesn't edit, unchanged, so the handler keeps them. ?>
		<input type="hidden" name="account_email" value="<?php echo esc_attr( $user->user_email ); ?>" />
		<input type="hidden" name="account_display_name" value="<?php echo esc_attr( $user->display_name ); ?>" />

		<div class="sc-form-actions sc-form-actions--end">
			<?php wp_nonce_field( 'save_account_details', 'save-account-details-nonce' ); ?>
			<button type="submit" class="button" name="save_account_details" value="Guardar cambios">Guardar cambios</button>
			<input type="hidden" name="action" value="save_account_details" />
		</div>

		<?php do_action( 'woocommerce_edit_account_form_end' ); ?>
	</div>

</form>

<?php // Decorative separator between the two stacked panels. ?>
<?php get_template_part( 'template-parts/divider' ); ?>

<?php // Password panel: validated + saved inline via AJAX (sc_change_password),
	// so wrong/empty inputs show errors on the fields without reloading.
	// Keeps native WC fields as a no-JS fallback (posts save_account_details). ?>
<form class="woocommerce-EditAccountForm edit-account-password sc-form js-password-form" action="" method="post" novalidate>

	<div class="sc-form-card">
		<h2 class="sc-form-card__title">Cambiar contraseña</h2>

		<div class="sc-form-feedback js-password-feedback" role="status" hidden></div>

		<p class="woocommerce-form-row form-row form-row-wide" id="password_current_field">
			<label for="password_current">Contraseña actual</label>
			<input type="password" class="woocommerce-Input woocommerce-Input--password input-text" name="password_current" id="password_current" autocomplete="current-password" />
		</p>
		<p class="woocommerce-form-row form-row form-row-wide" id="password_1_field">
			<label for="password_1">Nueva contraseña</label>
			<input type="password" class="woocommerce-Input woocommerce-Input--password input-text" name="password_1" id="password_1" autocomplete="new-password" />
			<span class="sc-form-hint">Mínimo 8 caracteres, con al menos una letra y un número.</span>
		</p>
		<p class="woocommerce-form-row form-row form-row-wide" id="password_2_field">
			<label for="password_2">Confirmar nueva contraseña</label>
			<input type="password" class="woocommerce-Input woocommerce-Input--password input-text" name="password_2" id="password_2" autocomplete="new-password" />
		</p>

		<?php // Carry the profile values, unchanged, so the no-JS fallback keeps them. ?>
		<input type="hidden" name="account_first_name" value="<?php echo esc_attr( $user->first_name ); ?>" />
		<input type="hidden" name="account_last_name" value="<?php echo esc_attr( $user->last_name ); ?>" />
		<input type="hidden" name="account_display_name" value="<?php echo esc_attr( $user->display_name ); ?>" />
		<input type="hidden" name="account_email" value="<?php echo esc_attr( $user->user_email ); ?>" />

		<div class="sc-form-actions sc-form-actions--end">
			<?php wp_nonce_field( 'save_account_details', 'save-account-details-nonce' ); ?>
			<button type="submit" class="button" name="save_account_details" value="Cambiar contraseña">Cambiar contraseña</button>
			<input type="hidden" name="action" value="save_account_details" />
		</div>
	</div>

</form>

<div class="sc-account-logout">
	<a href="<?php echo esc_url( wc_logout_url( home_url() ) ); ?>" class="sc-account-logout__btn js-logout-link">Cerrar sesión</a>
</div>

<?php do_action( 'woocommerce_after_edit_account_form' ); ?>
