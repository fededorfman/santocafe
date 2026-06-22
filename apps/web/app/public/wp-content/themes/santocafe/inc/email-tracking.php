<?php
/**
 * Open & click tracking de los emails automáticos — Santo Café.
 *
 * - Tabla propia wp_sc_email_log (se crea sola con dbDelta en el deploy).
 * - Pixel 1x1 para registrar aperturas (?sc_track=open&t=TOKEN).
 * - Redirect que registra clics y reenvía a la URL real (?sc_track=click&t=&u=).
 * - Stats por automatización para el panel.
 *
 * Nota: las aperturas dependen de que el cliente cargue imágenes (Gmail las
 * cachea por proxy, puede inflar/atrasar el dato). Los clics son más fiables.
 *
 * Loaded by functions.php.
 */
defined( 'ABSPATH' ) || exit;

const SC_EMAIL_LOG_DB_VERSION = '1';

/** Nombre de la tabla. */
function sc_email_log_table() {
	global $wpdb;
	return $wpdb->prefix . 'sc_email_log';
}

/* ============================================================
 * Instalación de la tabla (idempotente)
 * ============================================================ */
add_action( 'after_setup_theme', function () {
	if ( get_option( 'sc_email_log_db_version' ) === SC_EMAIL_LOG_DB_VERSION ) {
		return;
	}
	global $wpdb;
	$table   = sc_email_log_table();
	$charset = $wpdb->get_charset_collate();
	$sql = "CREATE TABLE {$table} (
		id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
		token varchar(32) NOT NULL,
		ekey varchar(32) NOT NULL DEFAULT '',
		user_id bigint(20) unsigned NOT NULL DEFAULT 0,
		email varchar(191) NOT NULL DEFAULT '',
		sent_at datetime DEFAULT NULL,
		opened_at datetime DEFAULT NULL,
		opens int unsigned NOT NULL DEFAULT 0,
		clicked_at datetime DEFAULT NULL,
		clicks int unsigned NOT NULL DEFAULT 0,
		PRIMARY KEY  (id),
		UNIQUE KEY token (token),
		KEY ekey (ekey)
	) {$charset};";
	require_once ABSPATH . 'wp-admin/includes/upgrade.php';
	dbDelta( $sql );
	update_option( 'sc_email_log_db_version', SC_EMAIL_LOG_DB_VERSION );
} );

/* ============================================================
 * Registro de envío + helpers de inyección
 * ============================================================ */

/** Crea la fila de log para un envío y devuelve el token (o '' si falla). */
function sc_email_log_record( $ekey, $user_id, $email ) {
	global $wpdb;
	if ( ! $email ) {
		return '';
	}
	$token = wp_generate_password( 24, false, false );
	$ok    = $wpdb->insert( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
		sc_email_log_table(),
		array(
			'token'   => $token,
			'ekey'    => substr( (string) $ekey, 0, 32 ),
			'user_id' => (int) $user_id,
			'email'   => substr( (string) $email, 0, 191 ),
			'sent_at' => current_time( 'mysql' ),
		),
		array( '%s', '%s', '%d', '%s', '%s' )
	);
	return $ok ? $token : '';
}

/** Pixel de apertura (img 1x1). */
function sc_email_pixel( $token ) {
	$src = add_query_arg( array( 'sc_track' => 'open', 't' => $token ), home_url( '/' ) );
	return '<img src="' . esc_url( $src ) . '" width="1" height="1" alt="" style="display:none;max-height:0;overflow:hidden;">';
}

/** Inyecta el pixel antes de </body> (o al final si no existe). */
function sc_email_inject_pixel( $html, $token ) {
	$pixel = sc_email_pixel( $token );
	if ( false !== stripos( $html, '</body>' ) ) {
		return str_ireplace( '</body>', $pixel . '</body>', $html );
	}
	return $html . $pixel;
}

/** Reescribe los href del sitio para pasar por el redirect de clics. */
function sc_email_wrap_links( $html, $token ) {
	return preg_replace_callback(
		'/href="(https?:\/\/[^"]+)"/i',
		function ( $m ) use ( $token ) {
			$url = html_entity_decode( $m[1], ENT_QUOTES, 'UTF-8' );
			// No trackear: baja, mailto, redes sociales.
			if ( stripos( $url, 'sc_unsub' ) !== false
				|| stripos( $url, 'instagram.com' ) !== false
				|| stripos( $url, 'wa.me' ) !== false
				|| stripos( $url, 'whatsapp' ) !== false ) {
				return $m[0];
			}
			$track = add_query_arg(
				array( 'sc_track' => 'click', 't' => $token, 'u' => $url ),
				home_url( '/' )
			);
			return 'href="' . esc_url( $track ) . '"';
		},
		$html
	);
}

/** Aplica open+click tracking a un HTML ya renderizado. Devuelve el HTML. */
function sc_email_apply_tracking( $html, $ekey, $user_id, $email ) {
	$token = sc_email_log_record( $ekey, $user_id, $email );
	if ( ! $token ) {
		return $html;
	}
	$html = sc_email_wrap_links( $html, $token );
	$html = sc_email_inject_pixel( $html, $token );
	return $html;
}

/* ============================================================
 * Endpoint: pixel de apertura + redirect de clic
 * ============================================================ */
add_action( 'init', function () {
	if ( empty( $_GET['sc_track'] ) || is_admin() ) {
		return;
	}
	$type  = sanitize_key( wp_unslash( $_GET['sc_track'] ) );
	$token = isset( $_GET['t'] ) ? sanitize_text_field( wp_unslash( $_GET['t'] ) ) : '';

	if ( 'open' === $type ) {
		if ( $token ) {
			sc_email_log_mark( $token, 'open' );
		}
		nocache_headers();
		header( 'Content-Type: image/gif' );
		// GIF transparente 1x1.
		echo base64_decode( 'R0lGODlhAQABAID/AP///wAAACwAAAAAAQABAAACAkQBADs=' ); // phpcs:ignore
		exit;
	}

	if ( 'click' === $type ) {
		if ( $token ) {
			sc_email_log_mark( $token, 'click' );
		}
		$url = isset( $_GET['u'] ) ? esc_url_raw( wp_unslash( $_GET['u'] ) ) : '';
		// wp_safe_redirect bloquea hosts externos → evita open-redirect.
		wp_safe_redirect( $url ? $url : home_url( '/' ) );
		exit;
	}
} );

/** Marca apertura/clic para un token (idempotente en la primera fecha). */
function sc_email_log_mark( $token, $type ) {
	global $wpdb;
	$table = sc_email_log_table();
	$now   = current_time( 'mysql' );
	if ( 'open' === $type ) {
		$wpdb->query( $wpdb->prepare( // phpcs:ignore WordPress.DB
			"UPDATE {$table} SET opens = opens + 1, opened_at = IFNULL(opened_at, %s) WHERE token = %s",
			$now, $token
		) );
	} elseif ( 'click' === $type ) {
		$wpdb->query( $wpdb->prepare( // phpcs:ignore WordPress.DB
			"UPDATE {$table} SET clicks = clicks + 1, clicked_at = IFNULL(clicked_at, %s) WHERE token = %s",
			$now, $token
		) );
	}
}

/* ============================================================
 * Stats para el panel
 * ============================================================ */

/** @return array{sent:int,opened:int,clicked:int} para una automatización. */
function sc_email_log_stats( $ekey ) {
	global $wpdb;
	$table = sc_email_log_table();
	$row   = $wpdb->get_row( $wpdb->prepare( // phpcs:ignore WordPress.DB
		"SELECT COUNT(*) AS sent,
			SUM( opened_at IS NOT NULL )  AS opened,
			SUM( clicked_at IS NOT NULL ) AS clicked
		 FROM {$table} WHERE ekey = %s",
		$ekey
	), ARRAY_A );
	return array(
		'sent'    => (int) ( $row['sent'] ?? 0 ),
		'opened'  => (int) ( $row['opened'] ?? 0 ),
		'clicked' => (int) ( $row['clicked'] ?? 0 ),
	);
}

/** Texto resumido "Enviados: X · Aperturas: Y (Z%) · Clics: W (V%)". */
function sc_email_log_stats_text( $ekey ) {
	$s    = sc_email_log_stats( $ekey );
	if ( ! $s['sent'] ) {
		return 'Sin envíos registrados todavía.';
	}
	$orate = round( $s['opened'] * 100 / $s['sent'] );
	$crate = round( $s['clicked'] * 100 / $s['sent'] );
	return sprintf(
		'Enviados: %d · Aperturas: %d (%d%%) · Clics: %d (%d%%)',
		$s['sent'], $s['opened'], $orate, $s['clicked'], $crate
	);
}
