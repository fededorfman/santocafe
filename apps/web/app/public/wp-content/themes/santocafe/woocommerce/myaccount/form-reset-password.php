<?php
/**
 * Reset password form — Santo Café override.
 * Centered narrow card con los estilos del tema, igual que el resto del flujo
 * de cuenta. Muestra los requisitos mínimos de contraseña (como en el registro).
 * Conserva los campos de WC, los hidden (key/login), el nonce y los hooks.
 *
 * @see woocommerce/templates/myaccount/form-reset-password.php
 * @version 9.2.0
 */
defined( 'ABSPATH' ) || exit;

do_action( 'woocommerce_before_reset_password_form' );
?>

<div class="sc-account">
	<div class="sc-account__card">
		<h2 class="sc-account__title">Crear nueva contraseña</h2>

		<form method="post" class="woocommerce-ResetPassword lost_reset_password js-validate" novalidate>

			<p class="sc-account__sub">
				<?php echo apply_filters( 'woocommerce_reset_password_message', esc_html__( 'Elige una nueva contraseña para tu cuenta. Vas a usarla para iniciar sesión la próxima vez.', 'santocafe' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			</p>

			<p class="woocommerce-form-row form-row validate-password">
				<label for="password_1">Nueva contraseña</label>
				<span class="sc-pw-field">
					<input type="password" name="password_1" id="password_1"
					       autocomplete="new-password" spellcheck="false"
					       minlength="8" required aria-required="true" />
					<button type="button" class="sc-pw-toggle" aria-label="Ver contraseña" tabindex="-1">
						<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/><line class="sc-pw-slash" x1="2" y1="2" x2="22" y2="22"/></svg>
					</button>
					<button type="button" class="sc-pw-generate" data-fill-match="#password_2" tabindex="-1">Generar</button>
				</span>
				<small class="sc-account__hint">Mínimo 8 caracteres, con al menos una letra y un número.</small>
			</p>

			<p class="woocommerce-form-row form-row validate-password-match" data-pw-match="#password_1">
				<label for="password_2">Repite la nueva contraseña</label>
				<span class="sc-pw-field">
					<input type="password" name="password_2" id="password_2"
					       autocomplete="new-password" spellcheck="false"
					       minlength="8" required aria-required="true" />
					<button type="button" class="sc-pw-toggle" aria-label="Ver contraseña" tabindex="-1">
						<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/><line class="sc-pw-slash" x1="2" y1="2" x2="22" y2="22"/></svg>
					</button>
				</span>
			</p>

			<input type="hidden" name="reset_key" value="<?php echo esc_attr( $args['key'] ); ?>" />
			<input type="hidden" name="reset_login" value="<?php echo esc_attr( $args['login'] ); ?>" />

			<?php do_action( 'woocommerce_resetpassword_form' ); ?>

			<p class="woocommerce-form-row form-row">
				<input type="hidden" name="wc_reset_password" value="true" />
				<button type="submit" class="btn btn--primary btn--full" value="Guardar contraseña">Guardar contraseña</button>
			</p>

			<?php wp_nonce_field( 'reset_password', 'woocommerce-reset-password-nonce' ); ?>

			<a class="sc-account__link" href="<?php echo esc_url( wc_get_page_permalink( 'myaccount' ) ); ?>">
				← Volver a iniciar sesión
			</a>

		</form>
	</div>
</div>

<?php
do_action( 'woocommerce_after_reset_password_form' );
