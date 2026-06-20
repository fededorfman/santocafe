<?php
/**
 * Lost password confirmation — Santo Café override.
 * Pantalla de "correo enviado" dentro de la tarjeta centrada del flujo de cuenta
 * (misma estética que el form de recuperar / reset). Conserva el notice y los
 * hooks de WC; adapta el copy a la voz de marca.
 *
 * @see woocommerce/templates/myaccount/lost-password-confirmation.php
 * @version 3.9.0
 */
defined( 'ABSPATH' ) || exit;
?>

<div class="sc-account">
	<div class="sc-account__card">
		<h2 class="sc-account__title">Revisa tu correo</h2>

		<?php wc_print_notice( esc_html__( 'Correo de restablecimiento enviado.', 'santocafe' ) ); ?>

		<?php do_action( 'woocommerce_before_lost_password_confirmation_message' ); ?>

		<p class="sc-account__sub">
			<?php echo esc_html( apply_filters( 'woocommerce_lost_password_confirmation_message', esc_html__( 'Te enviamos un enlace para crear una nueva contraseña a la dirección de tu cuenta. Puede tardar unos minutos en llegar; revisa también la carpeta de spam. Espera al menos 10 minutos antes de pedir otro.', 'santocafe' ) ) ); ?>
		</p>

		<?php do_action( 'woocommerce_after_lost_password_confirmation_message' ); ?>

		<a class="sc-account__link" href="<?php echo esc_url( wc_get_page_permalink( 'myaccount' ) ); ?>">
			← Volver a iniciar sesión
		</a>
	</div>
</div>
