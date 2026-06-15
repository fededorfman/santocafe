<?php
/**
 * Customer reset password email — Santo Café override.
 *
 * Recuperación de contraseña. Conserva las variables de WooCommerce
 * (`$user_login`, `$blogname`, `$reset_key`, `$user_id`) y la construcción del
 * enlace de reseteo; adapta el copy a la voz de marca (español de Chile).
 *
 * @see woocommerce/templates/emails/customer-reset-password.php
 * @package santocafe
 * @version 10.4.0
 */

defined( 'ABSPATH' ) || exit;

do_action( 'woocommerce_email_header', $email_heading, $email );

$sc_reset_url = add_query_arg(
	array(
		'key'   => $reset_key,
		'id'    => $user_id,
		'login' => rawurlencode( $user_login ),
	),
	wc_get_endpoint_url( 'lost-password', '', wc_get_page_permalink( 'myaccount' ) )
);
?>

<p>Hola <?php echo esc_html( $user_login ); ?>,</p>
<p>Alguien solicitó una nueva contraseña para tu cuenta en <?php echo esc_html( $blogname ); ?>. Si fuiste tú, crea una nueva con el botón de abajo.</p>

<table role="presentation" border="0" cellpadding="0" cellspacing="0" style="margin:24px 0 8px;"><tr><td align="left">
	<!--[if mso]>
	<v:roundrect xmlns:v="urn:schemas-microsoft-com:vml" xmlns:w="urn:schemas-microsoft-com:office:word" href="<?php echo esc_url( $sc_reset_url ); ?>" style="height:50px;v-text-anchor:middle;width:240px;" arcsize="60%" stroke="f" fillcolor="#dfb33e">
		<w:anchorlock/><center style="color:#1a1310;font-family:Arial,sans-serif;font-size:16px;font-weight:bold;">Restablecer contraseña</center>
	</v:roundrect>
	<![endif]-->
	<a href="<?php echo esc_url( $sc_reset_url ); ?>" class="sc-btn" style="mso-hide:all;">Restablecer contraseña</a>
</td></tr></table>

<p style="font-size:14px;color:#8a7d6b;">Si no hiciste esta solicitud, puedes ignorar este correo: tu contraseña actual seguirá funcionando.</p>

<?php
if ( $additional_content ) {
	echo wp_kses_post( wpautop( wptexturize( $additional_content ) ) );
}

do_action( 'woocommerce_email_footer', $email );
