<?php
/**
 * Formulario de contacto de la home (AJAX).
 *
 * Envía dos correos con los templates de marca:
 *   - Aviso al admin (emails/contacto-admin.html) con Reply-To del usuario.
 *   - Confirmación al usuario (emails/contacto-confirmacion.html).
 *
 * Sale por el SMTP configurado del sitio (hola@santocafe.cl). Protección:
 * nonce (sc_nonce) + honeypot oculto. Sanitiza entradas y escapa salidas.
 *
 * @package santocafe
 */

defined( 'ABSPATH' ) || exit;

add_action( 'wp_ajax_sc_contact', 'sc_handle_contact' );
add_action( 'wp_ajax_nopriv_sc_contact', 'sc_handle_contact' );

/**
 * Procesa el envío del formulario de contacto.
 */
function sc_handle_contact() {
	check_ajax_referer( 'sc_nonce', 'nonce' );

	// Honeypot: si el campo oculto viene lleno, es un bot. Fingimos éxito.
	if ( ! empty( $_POST['sc_website'] ) ) {
		wp_send_json_success( array( 'message' => '¡Mensaje enviado! Te responderemos a la brevedad.' ) );
	}

	$name    = sanitize_text_field( wp_unslash( $_POST['nombre'] ?? '' ) );
	$email   = sanitize_email( wp_unslash( $_POST['email'] ?? '' ) );
	$message = sanitize_textarea_field( wp_unslash( $_POST['mensaje'] ?? '' ) );
	$phone   = sanitize_text_field( wp_unslash( $_POST['telefono'] ?? '' ) );
	$subject = sanitize_text_field( wp_unslash( $_POST['asunto'] ?? '' ) );

	// Validación de requeridos.
	$fields = array();
	if ( mb_strlen( $name ) < 2 ) {
		$fields[] = 'nombre';
	}
	if ( ! is_email( $email ) ) {
		$fields[] = 'email';
	}
	if ( mb_strlen( $message ) < 5 ) {
		$fields[] = 'mensaje';
	}
	if ( $fields ) {
		wp_send_json_error(
			array(
				'message' => 'Revisa los datos e intenta de nuevo.',
				'fields'  => $fields,
			),
			422
		);
	}

	// Valores derivados / por defecto para los templates.
	$phone        = '' !== $phone ? $phone : 'No indicado';
	$subject      = '' !== $subject ? $subject : 'Consulta desde el sitio web';
	$submitted_at = date_i18n( 'j \d\e F \d\e Y, H:i', current_time( 'timestamp' ) ) . ' hrs';
	$message_html = nl2br( esc_html( $message ) );

	$logo      = get_stylesheet_directory_uri() . '/assets/images/logo.png';
	$site      = home_url( '/' );
	$shop      = function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'shop' ) : home_url( '/' );
	$instagram = 'https://instagram.com/santocafespecialtycoffee';

	// Render del aviso al admin.
	$admin_html = sc_render_contact_email(
		'contacto-admin.html',
		array(
			'LOGO_URL'     => esc_url( $logo ),
			'name'         => esc_html( $name ),
			'email'        => esc_html( $email ),
			'phone'        => esc_html( $phone ),
			'subject'      => esc_html( $subject ),
			'message'      => $message_html,
			'submitted_at' => esc_html( $submitted_at ),
		)
	);

	// Render de la confirmación al usuario.
	$confirm_html = sc_render_contact_email(
		'contacto-confirmacion.html',
		array(
			'LOGO_URL'      => esc_url( $logo ),
			'SITE_URL'      => esc_url( $site ),
			'SHOP_URL'      => esc_url( $shop ),
			'INSTAGRAM_URL' => esc_url( $instagram ),
			'name'          => esc_html( $name ),
			'message'       => $message_html,
		)
	);

	/**
	 * Remitente de los correos automáticos de contacto. Usamos no-reply porque
	 * la confirmación no espera respuesta. Debe ser una dirección que el SMTP
	 * del sitio tenga permitido enviar (ver "Force From" del plugin SMTP).
	 *
	 * @param string $from_email Email remitente.
	 */
	$from_email = apply_filters( 'sc_contact_from_email', 'no-reply@santocafe.cl' );
	/**
	 * Nombre visible del remitente.
	 *
	 * @param string $from_name Nombre del remitente.
	 */
	$from_name = apply_filters( 'sc_contact_from_name', 'Santo Café' );

	$base_headers = array(
		'Content-Type: text/html; charset=UTF-8',
		sprintf( 'From: %s <%s>', $from_name, $from_email ),
	);

	/**
	 * Destinatario del aviso interno de contacto.
	 *
	 * @param string $recipient Email del admin que recibe las consultas.
	 */
	$admin_to = apply_filters( 'sc_contact_recipient', 'hola@santocafe.cl' );

	// Aviso al admin: Reply-To del usuario, para responder directo a la consulta.
	$admin_headers   = $base_headers;
	$admin_headers[] = sprintf( 'Reply-To: %s <%s>', $name, $email );
	$admin_sent      = wp_mail( $admin_to, 'Nuevo mensaje de contacto · ' . $subject, $admin_html, $admin_headers );

	// Confirmación al usuario: si igual responde, su mensaje cae en hola@ (no en no-reply).
	$confirm_headers   = $base_headers;
	$confirm_headers[] = 'Reply-To: hola@santocafe.cl';
	wp_mail( $email, 'Recibimos tu mensaje · Santo Café', $confirm_html, $confirm_headers );

	if ( ! $admin_sent ) {
		wp_send_json_error(
			array( 'message' => 'No pudimos enviar tu mensaje. Intenta de nuevo o escríbenos a hola@santocafe.cl.' ),
			500
		);
	}

	wp_send_json_success( array( 'message' => '¡Mensaje enviado! Te responderemos a la brevedad.' ) );
}

/**
 * Carga un template HTML de email del tema y reemplaza los placeholders {{clave}}.
 *
 * @param string $file Nombre del archivo en /emails.
 * @param array  $vars Mapa clave => valor (ya escapado por el caller).
 * @return string HTML final, o cadena vacía si no se pudo leer.
 */
function sc_render_contact_email( $file, $vars ) {
	$path = get_stylesheet_directory() . '/emails/' . $file;
	if ( ! is_readable( $path ) ) {
		return '';
	}
	$html = (string) file_get_contents( $path ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- archivo local del tema.
	foreach ( $vars as $key => $value ) {
		$html = str_replace( '{{' . $key . '}}', $value, $html );
	}
	return $html;
}
