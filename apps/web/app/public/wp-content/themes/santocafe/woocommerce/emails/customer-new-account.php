<?php
/**
 * Customer new account email — Santo Café override.
 *
 * Bienvenida al crear la cuenta. Conserva las variables de WooCommerce
 * (`$user_login`, `$blogname`, `$password_generated`, `$set_password_url`) y
 * adapta el copy a la voz de marca (español de Chile).
 *
 * @see woocommerce/templates/emails/customer-new-account.php
 * @package santocafe
 * @version 10.4.0
 */

defined( 'ABSPATH' ) || exit;

do_action( 'woocommerce_email_header', $email_heading, $email );

$sc_account_url = wc_get_page_permalink( 'myaccount' );
$sc_cta_url     = ( $password_generated && $set_password_url ) ? $set_password_url : $sc_account_url;
$sc_cta_label   = ( $password_generated && $set_password_url ) ? 'Crear mi contraseña' : 'Ir a mi cuenta';
?>

<p>Hola <?php echo esc_html( $user_login ); ?>,</p>
<p>¡Gracias por crear tu cuenta en <?php echo esc_html( $blogname ); ?>! Desde tu cuenta vas a poder seguir tus pedidos, guardar tus direcciones y comprar más rápido la próxima vez.</p>
<p>Tu usuario es <strong><?php echo esc_html( $user_login ); ?></strong>.</p>

<?php if ( $password_generated && $set_password_url ) : ?>
	<p>Para terminar de activar tu cuenta, crea tu contraseña con el botón de abajo:</p>
<?php endif; ?>

<table role="presentation" border="0" cellpadding="0" cellspacing="0" style="margin:24px 0 8px;"><tr><td align="left">
	<!--[if mso]>
	<v:roundrect xmlns:v="urn:schemas-microsoft-com:vml" xmlns:w="urn:schemas-microsoft-com:office:word" href="<?php echo esc_url( $sc_cta_url ); ?>" style="height:50px;v-text-anchor:middle;width:230px;" arcsize="60%" stroke="f" fillcolor="#dfb33e">
		<w:anchorlock/><center style="color:#1a1310;font-family:Arial,sans-serif;font-size:16px;font-weight:bold;"><?php echo esc_html( $sc_cta_label ); ?></center>
	</v:roundrect>
	<![endif]-->
	<a href="<?php echo esc_url( $sc_cta_url ); ?>" class="sc-btn" style="mso-hide:all;"><?php echo esc_html( $sc_cta_label ); ?></a>
</td></tr></table>

<p style="font-size:14px;color:#8a7d6b;">¿No reconoces esta cuenta? Puedes ignorar este correo con tranquilidad.</p>

<?php
if ( $additional_content ) {
	echo wp_kses_post( wpautop( wptexturize( $additional_content ) ) );
}

do_action( 'woocommerce_email_footer', $email );
