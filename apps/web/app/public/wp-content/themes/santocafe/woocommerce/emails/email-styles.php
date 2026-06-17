<?php
/**
 * Email Styles — Santo Café override.
 *
 * WooCommerce inyecta este CSS (vía Emogrifier) en todos los correos
 * transaccionales. Lo reemplazamos por la paleta y tipografía de la marca para
 * que TODOS los emails (confirmación, envío, cuenta, etc.) compartan la misma
 * estética que los templates de `email-templates/`, sin tocar la lógica de WC.
 *
 * IMPORTANTE: este archivo emite CSS crudo (sin etiqueta <style>). WC lo
 * envuelve y lo inlinea sobre el HTML generado por los demás templates.
 *
 * @see woocommerce/templates/emails/email-styles.php
 * @package santocafe
 * @version 10.8.0
 */

defined( 'ABSPATH' ) || exit;

/* Paleta de marca (misma que los templates standalone). */
$sc_beige   = '#f3ece1'; // fondo exterior
$sc_card    = '#ffffff'; // tarjeta
$sc_dark    = '#1a1310'; // barra de logo / títulos fuertes
$sc_text    = '#3a2f27'; // texto cuerpo
$sc_muted   = '#8a7d6b'; // texto secundario
$sc_gold    = '#dfb33e'; // dorado de marca
$sc_link    = '#8a6d1f'; // dorado oscuro (links legibles)
$sc_cream   = '#fcfaf7'; // fondo footer
$sc_border  = '#efe6d6'; // bordes
$sc_border2 = '#f1eadd'; // bordes suaves

$sc_heading_font = "'Bricolage Grotesque', 'Helvetica Neue', Arial, sans-serif";
$sc_body_font    = "'Hanken Grotesk', 'Helvetica Neue', Arial, sans-serif";
?>
body {
	background-color: <?php echo esc_attr( $sc_beige ); ?>;
	padding: 0;
	margin: 0;
	text-align: center;
	-webkit-text-size-adjust: 100%;
	-ms-text-size-adjust: 100%;
}

#outer_wrapper {
	background-color: <?php echo esc_attr( $sc_beige ); ?>;
}

#wrapper {
	margin: 0 auto;
	-webkit-text-size-adjust: none !important;
	width: 100%;
	max-width: 600px;
}

#template_container {
	background-color: <?php echo esc_attr( $sc_card ); ?>;
	border: 0;
	border-radius: 14px !important;
	box-shadow: 0 1px 4px rgba(0, 0, 0, 0.06);
	overflow: hidden;
}

/* Barra superior con el logo. */
#template_header_image {
	background-color: <?php echo esc_attr( $sc_dark ); ?>;
	border-bottom: 3px solid <?php echo esc_attr( $sc_gold ); ?>;
	padding: 26px 24px;
	text-align: center;
}

#template_header_image img {
	width: 150px;
	max-width: 60%;
	height: auto;
	margin: 0 auto;
	display: inline-block;
}

.email-logo-text {
	color: <?php echo esc_attr( $sc_gold ); ?>;
	font-family: <?php echo $sc_heading_font; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>;
	font-size: 22px;
	font-weight: 700;
	margin: 0;
}

/* Bloque de encabezado (eyebrow dorado + h1). */
#template_header {
	background-color: <?php echo esc_attr( $sc_card ); ?>;
	color: <?php echo esc_attr( $sc_dark ); ?>;
	border-bottom: 0;
}

#header_wrapper {
	padding: 38px 44px 4px;
	display: block;
}

.sc-eyebrow {
	margin: 0 0 8px;
	font-family: <?php echo $sc_body_font; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>;
	font-size: 13px;
	letter-spacing: 0.14em;
	text-transform: uppercase;
	color: <?php echo esc_attr( $sc_gold ); ?>;
	font-weight: 700;
}

#template_header h1,
#template_header h1 a,
h1 {
	color: <?php echo esc_attr( $sc_dark ); ?>;
	font-family: <?php echo $sc_heading_font; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>;
	font-size: 28px;
	font-weight: 700;
	line-height: 120%;
	letter-spacing: -0.5px;
	margin: 0;
	text-align: left;
	background-color: inherit;
}

/* Cuerpo. */
#body_content {
	background-color: <?php echo esc_attr( $sc_card ); ?>;
}

#body_content table td {
	padding: 20px 44px 32px;
}

#body_content_inner {
	color: <?php echo esc_attr( $sc_text ); ?>;
	font-family: <?php echo $sc_body_font; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>;
	font-size: 16px;
	line-height: 165%;
	text-align: left;
	word-break: break-word;
	overflow-wrap: break-word;
}

/* Permite que cadenas largas (emails, URLs) se quiebren y no fuercen el ancho. */
#body_content td,
.email-order-details td,
.email-order-details th,
#template_footer #credit {
	word-break: break-word;
	overflow-wrap: break-word;
}

#body_content p {
	margin: 0 0 16px;
}

h2 {
	color: <?php echo esc_attr( $sc_dark ); ?>;
	display: block;
	font-family: <?php echo $sc_heading_font; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>;
	font-size: 19px;
	font-weight: 700;
	line-height: 140%;
	margin: 0 0 14px;
	text-align: left;
}

h2.email-order-detail-heading span {
	color: <?php echo esc_attr( $sc_muted ); ?>;
	display: block;
	font-size: 14px;
	font-weight: normal;
}

h3 {
	color: <?php echo esc_attr( $sc_dark ); ?>;
	display: block;
	font-family: <?php echo $sc_heading_font; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>;
	font-size: 16px;
	font-weight: 700;
	line-height: 140%;
	margin: 16px 0 8px;
	text-align: left;
}

a,
.link {
	color: <?php echo esc_attr( $sc_link ); ?>;
	font-weight: normal;
	text-decoration: underline;
}

/* Tabla de detalle del pedido. */
.td {
	color: <?php echo esc_attr( $sc_text ); ?>;
	border: 1px solid <?php echo esc_attr( $sc_border2 ); ?>;
	vertical-align: middle;
}

#body_content table.email-order-details {
	border: 1px solid <?php echo esc_attr( $sc_border ); ?>;
	border-radius: 12px;
	border-collapse: separate;
	overflow: hidden;
}

#body_content table .email-order-details td,
#body_content table .email-order-details th {
	padding: 12px 16px;
	color: <?php echo esc_attr( $sc_text ); ?>;
	font-family: <?php echo $sc_body_font; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>;
}

#body_content table .email-order-details th {
	color: <?php echo esc_attr( $sc_muted ); ?>;
	font-size: 13px;
	text-transform: uppercase;
	letter-spacing: 0.04em;
	border-bottom: 1px solid <?php echo esc_attr( $sc_border ); ?>;
}

#body_content .email-order-details tbody tr td {
	border-bottom: 1px solid <?php echo esc_attr( $sc_border2 ); ?>;
}

#body_content .email-order-details .order-totals td,
#body_content .email-order-details .order-totals th {
	font-weight: normal;
	padding-top: 6px;
	padding-bottom: 6px;
	color: <?php echo esc_attr( $sc_muted ); ?>;
}

#body_content .email-order-details .order-totals .includes_tax {
	display: block;
}

#body_content .email-order-details .order-totals-total th,
#body_content .email-order-details .order-totals-total td {
	font-weight: 700;
	color: <?php echo esc_attr( $sc_dark ); ?>;
}

#body_content .email-order-details .order-totals-total td {
	font-size: 20px;
}

#body_content td ul.wc-item-meta {
	font-size: 13px;
	color: <?php echo esc_attr( $sc_muted ); ?>;
	margin: 0.4em 0 0;
	padding: 0;
	list-style: none;
}

#body_content td ul.wc-item-meta li {
	margin: 0.25em 0 0;
	padding: 0;
}

#body_content td ul.wc-item-meta li p {
	margin: 0;
}

.wc-item-meta-label {
	font-weight: 600;
	margin-right: .25em;
}

/* Direcciones. */
.address {
	padding: 14px 16px;
	color: <?php echo esc_attr( $sc_text ); ?>;
	font-style: normal;
	line-height: 150%;
	border: 1px solid <?php echo esc_attr( $sc_border ); ?>;
	border-radius: 10px;
	background-color: <?php echo esc_attr( $sc_cream ); ?>;
}

.address-title,
.text {
	color: <?php echo esc_attr( $sc_dark ); ?>;
	font-family: <?php echo $sc_body_font; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>;
}

#addresses td + td {
	padding-left: 12px;
}

/* Botón dorado de marca (clase utilitaria para CTAs en los templates). */
.sc-btn {
	background-color: <?php echo esc_attr( $sc_gold ); ?>;
	border-radius: 30px;
	color: <?php echo esc_attr( $sc_dark ); ?> !important;
	display: inline-block;
	font-family: <?php echo $sc_body_font; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>;
	font-size: 16px;
	font-weight: 700;
	line-height: 50px;
	text-align: center;
	text-decoration: none !important;
	padding: 0 34px;
}

.sc-note {
	background-color: <?php echo esc_attr( $sc_cream ); ?>;
	border: 1px solid <?php echo esc_attr( $sc_border ); ?>;
	border-radius: 10px;
	padding: 16px 18px;
	color: <?php echo esc_attr( $sc_text ); ?>;
	font-size: 15px;
	line-height: 160%;
}

/* Footer oscuro de marca (los estilos finos van inline en email-footer.php). */
#template_footer td {
	padding: 0;
}

#template_footer #credit {
	border: 0;
	background-color: <?php echo esc_attr( $sc_dark ); ?>;
	color: <?php echo esc_attr( $sc_muted ); ?>;
	font-family: <?php echo $sc_body_font; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>;
	line-height: 160%;
	text-align: center;
}

#template_footer #credit a {
	color: <?php echo esc_attr( $sc_gold ); ?>;
}

img {
	border: none;
	display: inline-block;
	height: auto;
	outline: none;
	text-decoration: none;
	max-width: 100%;
}

.text-align-left {
	text-align: left;
}

.text-align-right {
	text-align: right;
}

/* Responsive — clientes mobile modernos (Gmail, Apple Mail). */
@media screen and (max-width: 600px) {
	#outer_wrapper > tbody > tr > td {
		padding: 16px 10px !important;
	}

	#template_header_image {
		padding: 22px 16px !important;
	}

	#header_wrapper {
		padding: 28px 24px 0 !important;
	}

	#template_header h1,
	h1 {
		font-size: 24px !important;
		line-height: 130% !important;
	}

	#body_content table td {
		padding: 16px 20px 24px !important;
	}

	#body_content table .email-order-details td,
	#body_content table .email-order-details th {
		padding: 10px 8px !important;
		font-size: 13px !important;
	}

	#body_content .email-order-details .order-totals-total td {
		font-size: 16px !important;
	}

	#body_content_inner {
		font-size: 15px !important;
	}

	#template_footer #credit {
		padding: 28px 24px 28px !important;
	}
}
<?php
