<?php
/**
 * Email Header — Santo Café override.
 *
 * Estructura de tarjeta de marca: barra oscura con el logo, encabezado con
 * "eyebrow" dorado + título dinámico (`$email_heading`), y la apertura del
 * contenedor del cuerpo. Cierra en `email-footer.php`.
 *
 * Variables que WooCommerce inyecta:
 * @var string   $email_heading Título del correo (definido por cada email de WC).
 * @var WC_Email $email         Instancia del email (para variar el copy por tipo).
 *
 * @see woocommerce/templates/emails/email-header.php
 * @package santocafe
 * @version 10.7.0
 */

defined( 'ABSPATH' ) || exit;

$email      = $email ?? null;
$store_name = get_bloginfo( 'name', 'display' );
$email_id   = ( $email && isset( $email->id ) ) ? $email->id : '';

/* Logo para la barra oscura: respeta el configurado en WooCommerce; si no, usa el del tema. */
$sc_logo = get_option( 'woocommerce_email_header_image' );
if ( empty( $sc_logo ) ) {
	$sc_logo = get_stylesheet_directory_uri() . '/assets/images/email/logo-email-oscuro.png';
}

/* "Eyebrow" dorado según el tipo de correo. */
$sc_eyebrows = array(
	'new_order'                  => 'Nuevo pedido',
	'cancelled_order'            => 'Pedido cancelado',
	'failed_order'               => 'Pago fallido',
	'customer_processing_order'  => '¡Gracias por tu compra!',
	'customer_completed_order'   => 'Tu pedido está listo',
	'customer_on_hold_order'     => 'Pedido recibido',
	'customer_refunded_order'    => 'Reembolso procesado',
	'customer_cancelled_order'   => 'Pedido cancelado',
	'customer_invoice'           => 'Detalle de tu pedido',
	'customer_note'              => 'Novedades de tu pedido',
	'customer_new_account'       => '¡Te damos la bienvenida!',
	'customer_reset_password'    => 'Recuperar contraseña',
);
$sc_eyebrow = $sc_eyebrows[ $email_id ] ?? 'Santo Café';
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?> xmlns:v="urn:schemas-microsoft-com:vml" xmlns:o="urn:schemas-microsoft-com:office:office">
	<head>
		<meta http-equiv="Content-Type" content="text/html; charset=<?php bloginfo( 'charset' ); ?>" />
		<meta content="width=device-width, initial-scale=1.0" name="viewport">
		<meta http-equiv="X-UA-Compatible" content="IE=edge">
		<meta name="x-apple-disable-message-reformatting">
		<meta name="format-detection" content="telephone=no, date=no, address=no, email=no">
		<meta name="color-scheme" content="light only">
		<meta name="supported-color-schemes" content="light">
		<title><?php echo esc_html( $store_name ); ?></title>
		<!--[if mso]>
		<noscript><xml><o:OfficeDocumentSettings><o:PixelsPerInch>96</o:PixelsPerInch></o:OfficeDocumentSettings></xml></noscript>
		<![endif]-->
		<style>
			@import url('https://fonts.googleapis.com/css2?family=Bricolage+Grotesque:wght@600;700&family=Hanken+Grotesk:wght@400;500;600;700&display=swap');
		</style>
	</head>
	<body <?php echo is_rtl() ? 'rightmargin' : 'leftmargin'; ?>="0" marginwidth="0" topmargin="0" marginheight="0" offset="0">
		<table width="100%" id="outer_wrapper" border="0" cellpadding="0" cellspacing="0" role="presentation">
			<tr>
				<td align="center" valign="top" style="padding: 28px 12px;">
					<div id="wrapper" dir="<?php echo is_rtl() ? 'rtl' : 'ltr'; ?>">
						<table border="0" cellpadding="0" cellspacing="0" width="600" align="center" id="template_container" role="presentation" style="width:100%; max-width:600px;">
							<!-- Barra de logo -->
							<tr>
								<td id="template_header_image" align="center" valign="middle">
									<?php
									$image_html = '<img src="' . esc_url( $sc_logo ) . '" alt="' . esc_attr( $store_name ) . '" />';
									// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- construido con esc_url()/esc_attr().
									echo '<a href="' . esc_url( home_url() ) . '" style="display:inline-block;text-decoration:none;" target="_blank">' . $image_html . '</a>';
									?>
								</td>
							</tr>
							<!-- Encabezado -->
							<tr>
								<td align="left" valign="top">
									<table border="0" cellpadding="0" cellspacing="0" width="100%" id="template_header" role="presentation">
										<tr>
											<td id="header_wrapper">
												<p class="sc-eyebrow"><?php echo esc_html( $sc_eyebrow ); ?></p>
												<h1><?php echo esc_html( $email_heading ); ?></h1>
											</td>
										</tr>
									</table>
								</td>
							</tr>
							<!-- Cuerpo -->
							<tr>
								<td align="center" valign="top">
									<table border="0" cellpadding="0" cellspacing="0" width="100%" id="template_body" role="presentation">
										<tr>
											<td valign="top" id="body_content">
												<table border="0" cellpadding="0" cellspacing="0" width="100%" role="presentation">
													<tr>
														<td valign="top" id="body_content_inner_cell">
															<div id="body_content_inner">
