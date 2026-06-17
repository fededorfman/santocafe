<?php
/**
 * Ajustes de los emails transaccionales de WooCommerce.
 *
 * - No repetir el número de pedido en el resumen del aviso al admin (ya está en
 *   el título "Nuevo pedido: #X").
 * - Reemplazar el "contenido adicional" por copy de marca (sin la promo de la
 *   app ni el email de Local), con los datos correctos de Santo Café.
 *
 * @package santocafe
 */

defined( 'ABSPATH' ) || exit;

/**
 * Oculta el número de pedido (y fecha) en el encabezado del resumen del email de
 * "Nuevo pedido" del admin: ya aparece en el título, no hace falta repetirlo.
 *
 * @param bool          $display Mostrar o no el número.
 * @param WC_Order|null $order   Pedido.
 * @param WC_Email|null $email   Email.
 * @return bool
 */
add_filter(
	'woocommerce_email_display_order_number',
	static function ( $display, $order = null, $email = null ) {
		if ( $email && isset( $email->id ) && 'new_order' === $email->id ) {
			return false;
		}
		return $display;
	},
	10,
	3
);

/* Aviso al admin: sin "¡Felicitaciones por la venta!" ni promo de la app. */
add_filter( 'woocommerce_email_additional_content_new_order', '__return_empty_string', 20 );

/* Confirmación al cliente (procesando): firma de marca con el contacto correcto. */
add_filter(
	'woocommerce_email_additional_content_customer_processing_order',
	static function () {
		// El salto de línea simple se convierte en <br> vía wpautop.
		return "¡Gracias de nuevo!\nPonte en contacto con nosotros si necesitas ayuda en hola@santocafe.cl";
	},
	20
);
