<?php
/**
 * Emails automáticos (lifecycle) — Santo Café.
 *
 * Motor de envíos programados por consulta a la base, gestionable desde el admin
 * (WooCommerce → Emails automáticos). Usa Action Scheduler (incluido en
 * WooCommerce) para un job diario que escanea quién califica y envía los
 * templates de marca de /emails, con deduplicación y opt-out.
 *
 * Automatizaciones (todas consultables, sin infra extra):
 *   - cumpleanos    : a quienes cumplen años hoy (meta sc_birthday).
 *   - reposicion    : clientes cuyo ÚLTIMO pedido fue hace N días.
 *   - resena        : pedidos completados hace N días (pide reseña del producto).
 *   - reactivacion  : clientes sin comprar hace N días (win-back).
 *
 * NO incluidas (requieren captura adicional, se hacen con plugin/ESP):
 *   carrito abandonado, volvió-stock, newsletter/promo masivos.
 *
 * @package santocafe
 */

defined( 'ABSPATH' ) || exit;

/* ============================================================
 * Definiciones y configuración
 * ============================================================ */

/**
 * Catálogo de automatizaciones disponibles con sus parámetros por defecto.
 *
 * @return array<string,array>
 */
function sc_auto_email_defs() {
	return array(
		'cumpleanos'   => array(
			'label'    => 'Cumpleaños',
			'desc'     => 'A quienes cumplen años hoy (según su fecha de nacimiento en la cuenta). Con cupón único.',
			'template' => 'cumpleanos.html',
			'params'   => array( 'coupon_type' => 'percent', 'coupon_amount' => 10, 'coupon_days' => 15 ),
		),
		'reposicion'   => array(
			'label'    => 'Reposición (no te quedes sin café)',
			'desc'     => 'A clientes cuyo último pedido fue hace N días (se les estaría acabando el café).',
			'template' => 'reposicion.html',
			'params'   => array( 'days' => 21 ),
		),
		'resena'       => array(
			'label'    => 'Solicitud de reseña',
			'desc'     => 'A quienes recibieron su pedido (estado "Entregado") hace N días, para que reseñen su café.',
			'template' => 'resena.html',
			'params'   => array( 'days' => 3 ),
		),
		'reactivacion' => array(
			'label'    => 'Reactivación (win-back)',
			'desc'     => 'A clientes que no compran hace N días, con un cupón único para volver.',
			'template' => 'reactivacion.html',
			'params'   => array( 'days' => 90, 'coupon_type' => 'percent', 'coupon_amount' => 10, 'coupon_days' => 15 ),
		),
	);
}

/**
 * Configuración efectiva (defaults + opción guardada).
 *
 * @return array{send_hour:int,items:array<string,array>}
 */
function sc_auto_cfg() {
	$defs  = sc_auto_email_defs();
	$saved = get_option( 'sc_auto_emails', array() );
	$cfg   = array(
		'send_hour'  => isset( $saved['send_hour'] ) ? (int) $saved['send_hour'] : 9,
		'test_email' => isset( $saved['test_email'] ) ? $saved['test_email'] : '',
		'items'      => array(),
	);
	foreach ( $defs as $key => $def ) {
		$row = array_merge( array( 'enabled' => false ), $def['params'] );
		if ( ! empty( $saved['items'][ $key ] ) && is_array( $saved['items'][ $key ] ) ) {
			$row = array_merge( $row, $saved['items'][ $key ] );
		}
		$cfg['items'][ $key ] = $row;
	}
	return $cfg;
}

/* ============================================================
 * Programación (Action Scheduler)
 * ============================================================ */

/**
 * Próximo timestamp para HH:00 en la zona horaria del sitio.
 *
 * @param int $hour Hora 0-23.
 * @return int Epoch (UTC).
 */
function sc_auto_next_run_ts( $hour ) {
	$hour = max( 0, min( 23, (int) $hour ) );
	$dt   = new DateTime( 'now', wp_timezone() );
	$dt->setTime( $hour, 0, 0 );
	if ( $dt->getTimestamp() <= time() ) {
		$dt->modify( '+1 day' );
	}
	return $dt->getTimestamp();
}

/** Programa (o re-programa) el job diario. */
function sc_auto_reschedule( $hour ) {
	if ( ! function_exists( 'as_schedule_recurring_action' ) ) {
		return;
	}
	as_unschedule_all_actions( 'sc_daily_email_jobs', array(), 'santocafe' );
	as_schedule_recurring_action( sc_auto_next_run_ts( $hour ), DAY_IN_SECONDS, 'sc_daily_email_jobs', array(), 'santocafe' );
}

add_action(
	'init',
	function () {
		if ( ! function_exists( 'as_next_scheduled_action' ) ) {
			return;
		}
		if ( false === as_next_scheduled_action( 'sc_daily_email_jobs', array(), 'santocafe' ) ) {
			sc_auto_reschedule( sc_auto_cfg()['send_hour'] );
		}
		// Limpieza semanal de cupones automáticos vencidos.
		if ( false === as_next_scheduled_action( 'sc_cleanup_auto_coupons', array(), 'santocafe' ) ) {
			as_schedule_recurring_action( time() + HOUR_IN_SECONDS, WEEK_IN_SECONDS, 'sc_cleanup_auto_coupons', array(), 'santocafe' );
		}
	}
);

/* ============================================================
 * Runner diario
 * ============================================================ */

add_action( 'sc_daily_email_jobs', 'sc_run_daily_email_jobs' );

/** Recorre las automatizaciones activas, envía y deduplica. */
function sc_run_daily_email_jobs() {
	$cfg  = sc_auto_cfg();
	$defs = sc_auto_email_defs();
	$cap  = (int) apply_filters( 'sc_auto_cap_per_run', 80 ); // límite por corrida (SMTP).
	$sent = 0;
	$log  = array();

	foreach ( $defs as $key => $def ) {
		$item_cfg = $cfg['items'][ $key ];
		if ( empty( $item_cfg['enabled'] ) ) {
			$log[ $key ] = array( 'enabled' => false, 'sent' => 0 );
			continue;
		}
		$count = 0;
		foreach ( sc_auto_recipients( $key, $item_cfg ) as $item ) {
			if ( $sent >= $cap ) {
				break 2;
			}
			if ( sc_auto_send_one( $key, $item, $item_cfg, $def ) ) {
				sc_auto_mark_sent( $key, $item );
				++$count;
				++$sent;
			}
		}
		$log[ $key ] = array( 'enabled' => true, 'sent' => $count );
	}

	update_option( 'sc_auto_emails_last', array( 'time' => time(), 'log' => $log ), false );
}

/* ============================================================
 * Criterios (quién califica) — devuelven la lista REAL de envío
 * (criterio + sin opt-out + sin duplicar).
 * ============================================================ */

/**
 * @param string $key   Automatización.
 * @param array  $cfg   Config del item.
 * @param int    $limit 0 = sin límite.
 * @return array<int,array{user_id:int,email:string,first_name:string,order:?WC_Order,product:?WC_Product}>
 */
function sc_auto_recipients( $key, $cfg, $limit = 0 ) {
	switch ( $key ) {
		case 'cumpleanos':
			return sc_auto_recipients_cumpleanos( $cfg, $limit );
		case 'reposicion':
			return sc_auto_recipients_reposicion( $cfg, $limit );
		case 'resena':
			return sc_auto_recipients_resena( $cfg, $limit );
		case 'reactivacion':
			return sc_auto_recipients_reactivacion( $cfg, $limit );
	}
	return array();
}

function sc_auto_recipients_cumpleanos( $cfg, $limit = 0 ) {
	$today = wp_date( 'm-d' );
	$year  = wp_date( 'Y' );
	$out   = array();
	$users = get_users( array( 'meta_key' => 'sc_birthday', 'fields' => array( 'ID', 'user_email', 'display_name' ) ) ); // phpcs:ignore WordPress.DB.SlowDBQuery
	foreach ( $users as $u ) {
		if ( get_user_meta( $u->ID, 'sc_email_optout', true ) ) {
			continue;
		}
		$b = get_user_meta( $u->ID, 'sc_birthday', true );
		// El dato es 'YYYY-MM-DD'; comparamos el MM-DD directo (sin zona horaria).
		if ( ! $b || substr( (string) $b, 5, 5 ) !== $today ) {
			continue;
		}
		if ( (string) get_user_meta( $u->ID, '_sc_sent_cumpleanos', true ) === (string) $year ) {
			continue;
		}
		$out[] = array(
			'user_id'    => (int) $u->ID,
			'email'      => $u->user_email,
			'first_name' => sc_auto_first_name( $u->ID, $u->display_name ),
			'order'      => null,
			'product'    => null,
		);
		if ( $limit && count( $out ) >= $limit ) {
			break;
		}
	}
	return $out;
}

function sc_auto_recipients_reposicion( $cfg, $limit = 0 ) {
	if ( ! function_exists( 'wc_get_orders' ) ) {
		return array();
	}
	$days = max( 1, (int) ( $cfg['days'] ?? 90 ) );
	$ini  = strtotime( wp_date( 'Y-m-d', strtotime( "-{$days} days" ) ) . ' 00:00:00' );
	$fin  = strtotime( wp_date( 'Y-m-d', strtotime( "-{$days} days" ) ) . ' 23:59:59' );
	$out  = array();

	$orders = wc_get_orders(
		array(
			'status'       => array( 'wc-completed' ),
			'date_created' => $ini . '...' . $fin,
			'limit'        => -1,
		)
	);
	foreach ( $orders as $order ) {
		$cid = $order->get_customer_id();
		if ( ! $cid || get_user_meta( $cid, 'sc_email_optout', true ) ) {
			continue;
		}
		if ( get_user_meta( $cid, '_sc_sent_reposicion_' . $order->get_id(), true ) ) {
			continue;
		}
		// ¿Es su último pedido? (sin pedidos posteriores)
		$later = wc_get_orders(
			array( 'customer_id' => $cid, 'date_created' => '>' . $fin, 'limit' => 1, 'return' => 'ids' )
		);
		if ( $later ) {
			continue;
		}
		$out[] = array(
			'user_id'    => (int) $cid,
			'email'      => $order->get_billing_email(),
			'first_name' => $order->get_billing_first_name(),
			'order'      => $order,
			'product'    => sc_auto_first_product( $order ),
		);
		if ( $limit && count( $out ) >= $limit ) {
			break;
		}
	}
	return $out;
}

function sc_auto_recipients_resena( $cfg, $limit = 0 ) {
	if ( ! function_exists( 'wc_get_orders' ) ) {
		return array();
	}
	$days = max( 1, (int) ( $cfg['days'] ?? 14 ) );
	$ini  = strtotime( wp_date( 'Y-m-d', strtotime( "-{$days} days" ) ) . ' 00:00:00' );
	$fin  = strtotime( wp_date( 'Y-m-d', strtotime( "-{$days} days" ) ) . ' 23:59:59' );
	$out  = array();

	$orders = wc_get_orders(
		array(
			'status'       => array( 'wc-completed' ),
			'date_created' => $ini . '...' . $fin,
			'limit'        => -1,
		)
	);
	foreach ( $orders as $order ) {
		$cid   = $order->get_customer_id();
		$email = $order->get_billing_email();
		if ( $cid && get_user_meta( $cid, 'sc_email_optout', true ) ) {
			continue;
		}
		$sent = $cid
			? get_user_meta( $cid, '_sc_sent_resena_' . $order->get_id(), true )
			: $order->get_meta( '_sc_sent_resena' );
		if ( $sent ) {
			continue;
		}
		$product = sc_auto_first_product( $order );
		if ( ! $product || ! $email ) {
			continue;
		}
		$out[] = array(
			'user_id'    => (int) $cid,
			'email'      => $email,
			'first_name' => $order->get_billing_first_name(),
			'order'      => $order,
			'product'    => $product,
		);
		if ( $limit && count( $out ) >= $limit ) {
			break;
		}
	}
	return $out;
}

function sc_auto_recipients_reactivacion( $cfg, $limit = 0 ) {
	if ( ! function_exists( 'wc_get_customer_last_order' ) ) {
		return array();
	}
	$days   = max( 1, (int) ( $cfg['days'] ?? 120 ) );
	$cutoff = strtotime( "-{$days} days" );
	$out    = array();

	$users = get_users( array( 'role' => 'customer', 'fields' => array( 'ID', 'user_email', 'display_name' ) ) );
	foreach ( $users as $u ) {
		if ( get_user_meta( $u->ID, 'sc_email_optout', true ) ) {
			continue;
		}
		$last = wc_get_customer_last_order( $u->ID );
		if ( ! $last || ! $last->get_date_created() ) {
			continue;
		}
		if ( $last->get_date_created()->getTimestamp() > $cutoff ) {
			continue; // compró hace poco
		}
		$sent = (int) get_user_meta( $u->ID, '_sc_sent_reactivacion', true );
		if ( $sent && ( time() - $sent ) < $days * DAY_IN_SECONDS ) {
			continue; // cooldown
		}
		$out[] = array(
			'user_id'    => (int) $u->ID,
			'email'      => $u->user_email,
			'first_name' => sc_auto_first_name( $u->ID, $u->display_name ),
			'order'      => $last,
			'product'    => null,
		);
		if ( $limit && count( $out ) >= $limit ) {
			break;
		}
	}
	return $out;
}

/** Marca el envío para no repetir. */
function sc_auto_mark_sent( $key, $item ) {
	$uid   = (int) $item['user_id'];
	$order = $item['order'];
	switch ( $key ) {
		case 'cumpleanos':
			if ( $uid ) {
				update_user_meta( $uid, '_sc_sent_cumpleanos', wp_date( 'Y' ) );
			}
			break;
		case 'reposicion':
			if ( $uid && $order ) {
				update_user_meta( $uid, '_sc_sent_reposicion_' . $order->get_id(), time() );
			}
			break;
		case 'resena':
			if ( $order ) {
				if ( $uid ) {
					update_user_meta( $uid, '_sc_sent_resena_' . $order->get_id(), time() );
				} else {
					$order->update_meta_data( '_sc_sent_resena', time() );
					$order->save();
				}
			}
			break;
		case 'reactivacion':
			if ( $uid ) {
				update_user_meta( $uid, '_sc_sent_reactivacion', time() );
			}
			break;
	}
}

/* ============================================================
 * Render + envío
 * ============================================================ */

function sc_auto_first_name( $uid, $fallback = '' ) {
	$f = $uid ? get_user_meta( $uid, 'first_name', true ) : '';
	if ( ! $f && $uid ) {
		$f = get_user_meta( $uid, 'billing_first_name', true );
	}
	return $f ? $f : $fallback;
}

function sc_auto_first_product( $order ) {
	if ( ! $order ) {
		return null;
	}
	foreach ( $order->get_items() as $it ) {
		$p = $it->get_product();
		if ( $p ) {
			return $p;
		}
	}
	return null;
}

/** Variables comunes (footer, marca) para los templates. */
function sc_auto_common_vars( $user_id ) {
	$assets = get_stylesheet_directory_uri() . '/assets/images';
	$icons  = apply_filters( 'sc_email_assets_url', $assets . '/email' );
	return array(
		'LOGO_URL'        => esc_url( apply_filters( 'sc_email_logo_url', $assets . '/logo.png' ) ),
		'SITE_URL'        => esc_url( home_url( '/' ) ),
		'SHOP_URL'        => esc_url( function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'shop' ) : home_url( '/' ) ),
		'INSTAGRAM_URL'   => 'https://instagram.com/santocafespecialtycoffee',
		'WHATSAPP_URL'    => 'https://wa.me/56951414791',
		'IG_ICON'         => esc_url( $icons . '/instagram.png' ),
		'WA_ICON'         => esc_url( $icons . '/whatsapp.png' ),
		'year'            => esc_html( wp_date( 'Y' ) ),
		'UNSUBSCRIBE_URL' => esc_url( sc_auto_unsub_url( (int) $user_id ) ),
	);
}

/** Mapa de placeholders para una automatización + destinatario. */
function sc_auto_context( $key, $item, $cfg ) {
	$vars               = sc_auto_common_vars( $item['user_id'] );
	$vars['first_name'] = esc_html( $item['first_name'] ? $item['first_name'] : 'Hola' );

	if ( in_array( $key, array( 'cumpleanos', 'reactivacion' ), true ) && ! empty( $item['email'] ) ) {
		$cd     = max( 1, (int) ( $cfg['coupon_days'] ?? 15 ) );
		$type   = ( 'fixed_cart' === ( $cfg['coupon_type'] ?? 'percent' ) ) ? 'fixed_cart' : 'percent';
		$amount = max( 0, (float) ( $cfg['coupon_amount'] ?? 10 ) );
		$prefix = ( 'cumpleanos' === $key ) ? 'CUMPLE' : 'VUELVE';
		// Cupón único por cliente (se crea al enviar, no en el preview).
		$code   = sc_auto_generate_coupon( $prefix, $item['email'], $type, $amount, $cd );
		$value  = 'percent' === $type
			? ( ( (float) $amount === floor( $amount ) ? (int) $amount : $amount ) . '%' )
			: sc_format_clp( (int) $amount );
		$vars['coupon_code']  = esc_html( $code );
		$vars['coupon_value'] = esc_html( $value );
		$vars['expiry_date']  = esc_html( date_i18n( 'j \d\e F \d\e Y', strtotime( "+{$cd} days" ) ) );
		// URL al inicio con el cupón listo para aplicarse solo (?sc_coupon=...).
		$curl                 = $code ? add_query_arg( 'sc_coupon', rawurlencode( $code ), home_url( '/' ) ) . '#catalogo' : home_url( '/#catalogo' );
		$vars['coupon_url']   = esc_url( $curl );
	}

	if ( in_array( $key, array( 'reposicion', 'resena' ), true ) && $item['product'] ) {
		$p     = $item['product'];
		$img   = $p->get_image_id() ? wp_get_attachment_image_url( $p->get_image_id(), 'woocommerce_thumbnail' ) : '';
		if ( ! $img && function_exists( 'wc_placeholder_img_src' ) ) {
			$img = wc_placeholder_img_src();
		}
		$vars['product_name']  = esc_html( $p->get_name() );
		$vars['product_image'] = esc_url( $img );
		$vars['product_url']   = esc_url( $p->get_permalink() );
		$vars['review_url']    = esc_url( $p->get_permalink() . '#reviews' );
	}
	return $vars;
}

/** Carga un template de /emails y reemplaza {{placeholders}}. */
function sc_auto_render( $template_file, $vars ) {
	$path = get_stylesheet_directory() . '/emails/' . $template_file;
	if ( ! is_readable( $path ) ) {
		return '';
	}
	$html = (string) file_get_contents( $path ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
	foreach ( $vars as $k => $v ) {
		$html = str_replace( '{{' . $k . '}}', $v, $html );
	}
	// Cualquier placeholder sobrante se vacía para no mostrar {{...}}.
	$html = preg_replace( '/\{\{[A-Za-z0-9_]+\}\}/', '', $html );
	return $html;
}

function sc_auto_subject( $key ) {
	switch ( $key ) {
		case 'cumpleanos':
			return '¡Feliz cumpleaños! Tenemos un regalo para ti 🎁';
		case 'reposicion':
			return '¿Se te está acabando el café?';
		case 'resena':
			return '¿Qué te pareció tu café?';
		case 'reactivacion':
			return 'Te extrañamos en Santo Café';
	}
	return 'Santo Café';
}

/** Envía un email de una automatización a un destinatario. */
function sc_auto_send_one( $key, $item, $cfg, $def ) {
	if ( empty( $item['email'] ) ) {
		return false;
	}
	$html = sc_auto_render( $def['template'], sc_auto_context( $key, $item, $cfg ) );
	if ( '' === $html ) {
		return false;
	}
	$from_email = apply_filters( 'sc_contact_from_email', 'no-reply@santocafe.cl' );
	$from_name  = apply_filters( 'sc_contact_from_name', 'Santo Café' );
	$headers    = array(
		'Content-Type: text/html; charset=UTF-8',
		sprintf( 'From: %s <%s>', $from_name, $from_email ),
		'Reply-To: hola@santocafe.cl',
	);
	return (bool) wp_mail( $item['email'], sc_auto_subject( $key ), $html, $headers );
}

/* ============================================================
 * Cupones dinámicos (únicos por cliente, con vencimiento)
 * ============================================================ */

/**
 * Crea un cupón WooCommerce único para un cliente y devuelve el código.
 * Restringido a su email, de un solo uso y con vencimiento. Marcado con
 * _sc_auto_coupon para poder limpiarlo cuando expira.
 *
 * @param string $prefix Prefijo legible (ej. CUMPLE).
 * @param string $email  Email del beneficiario (restricción).
 * @param string $type   'percent' o 'fixed_cart'.
 * @param float  $amount Monto del descuento.
 * @param int    $days   Días hasta el vencimiento.
 * @return string Código generado ('' si WooCommerce no está disponible).
 */
function sc_auto_generate_coupon( $prefix, $email, $type, $amount, $days ) {
	if ( ! class_exists( 'WC_Coupon' ) || ! function_exists( 'wc_get_coupon_id_by_code' ) ) {
		return '';
	}
	$prefix = strtoupper( preg_replace( '/[^A-Za-z0-9]/', '', (string) $prefix ) );
	$prefix = $prefix ? $prefix : 'SANTO';

	// Código único: PREFIJO-XXXXXX. Reintenta si por azar ya existe.
	$code = '';
	for ( $i = 0; $i < 5; $i++ ) {
		$candidate = $prefix . '-' . strtoupper( wp_generate_password( 6, false, false ) );
		if ( ! wc_get_coupon_id_by_code( $candidate ) ) {
			$code = $candidate;
			break;
		}
	}
	if ( '' === $code ) {
		return '';
	}

	$coupon = new WC_Coupon();
	$coupon->set_code( $code );
	$coupon->set_discount_type( 'fixed_cart' === $type ? 'fixed_cart' : 'percent' );
	$coupon->set_amount( (float) $amount );
	$coupon->set_individual_use( true );
	$coupon->set_usage_limit( 1 ); // un solo uso global: ya es único y personal.
	// Sin restricción de email a propósito: permite que el cupón se auto-aplique
	// desde el link del email aunque la persona no haya ingresado su mail aún.
	// La unicidad + límite de 1 uso lo mantienen efectivamente personal.
	$coupon->set_date_expires( strtotime( '+' . max( 1, (int) $days ) . ' days' ) );
	$coupon->set_description( 'Cupón automático Santo Café (' . $prefix . ') para ' . $email );
	$coupon->add_meta_data( '_sc_auto_coupon', 1, true );
	$coupon->save();

	return $code;
}

/** Borra los cupones automáticos ya vencidos (limpieza semanal). */
function sc_auto_cleanup_coupons() {
	if ( ! class_exists( 'WC_Coupon' ) ) {
		return 0;
	}
	$ids = get_posts(
		array(
			'post_type'      => 'shop_coupon',
			'post_status'    => 'publish',
			'posts_per_page' => 500,
			'fields'         => 'ids',
			'meta_key'       => '_sc_auto_coupon', // phpcs:ignore WordPress.DB.SlowDBQuery
			'meta_value'     => 1,                 // phpcs:ignore WordPress.DB.SlowDBQuery
		)
	);
	$now     = time();
	$deleted = 0;
	foreach ( $ids as $id ) {
		$coupon = new WC_Coupon( $id );
		$exp    = $coupon->get_date_expires();
		if ( $exp && $exp->getTimestamp() < $now ) {
			wp_delete_post( $id, true );
			++$deleted;
		}
	}
	return $deleted;
}
add_action( 'sc_cleanup_auto_coupons', 'sc_auto_cleanup_coupons' );

/* ============================================================
 * Opt-out (cancelar suscripción)
 * ============================================================ */

function sc_auto_unsub_token( $user_id ) {
	return wp_hash( 'sc_unsub_' . (int) $user_id );
}

function sc_auto_unsub_url( $user_id ) {
	if ( ! $user_id ) {
		return home_url( '/' );
	}
	return add_query_arg(
		array( 'sc_unsub' => (int) $user_id, 'k' => sc_auto_unsub_token( $user_id ) ),
		home_url( '/' )
	);
}

add_action(
	'template_redirect',
	function () {
		if ( empty( $_GET['sc_unsub'] ) ) {
			return;
		}
		$uid = absint( $_GET['sc_unsub'] );
		$k   = isset( $_GET['k'] ) ? sanitize_text_field( wp_unslash( $_GET['k'] ) ) : '';
		if ( ! $uid || ! hash_equals( sc_auto_unsub_token( $uid ), $k ) ) {
			sc_unsub_render_page(
				'Enlace inválido',
				'<p>Este enlace de baja no es válido o ya expiró.</p>'
				. '<p><a class="btn btn--ghost" href="' . esc_url( home_url( '/' ) ) . '">Ir al inicio</a></p>',
				400
			);
		}

		// La baja se confirma solo por POST: evita que el prefetch del correo
		// (Gmail/Outlook) dé de baja a alguien sin que haya hecho clic.
		$is_post = ( 'POST' === ( $_SERVER['REQUEST_METHOD'] ?? '' ) );
		if ( $is_post && ! empty( $_POST['sc_unsub_confirm'] ) ) {
			update_user_meta( $uid, 'sc_email_optout', 1 );
			sc_unsub_render_page(
				'Listo, te diste de baja',
				'<p>No te enviaremos más correos promocionales.</p>'
				. '<p class="muted">Los correos sobre tus pedidos los seguirás recibiendo normalmente.</p>'
				. '<p style="margin-top:24px;"><a class="btn" href="' . esc_url( home_url( '/' ) ) . '">Volver al inicio</a></p>'
			);
		}

		// GET: pedir confirmación.
		$user   = get_userdata( $uid );
		$email  = $user ? $user->user_email : '';
		$action = esc_url( add_query_arg( array( 'sc_unsub' => $uid, 'k' => $k ), home_url( '/' ) ) );
		sc_unsub_render_page(
			'¿Cancelar los correos promocionales?',
			'<p>Estás por darte de baja de los correos promocionales de Santo Café'
			. ( $email ? ' <strong>(' . esc_html( $email ) . ')</strong>' : '' ) . '.</p>'
			. '<p class="muted">Seguirás recibiendo los correos sobre tus pedidos.</p>'
			. '<form method="post" action="' . $action . '" style="margin-top:24px;">'
			. '<input type="hidden" name="sc_unsub_confirm" value="1">'
			. '<button type="submit" class="btn">Sí, darme de baja</button> '
			. '<a class="btn btn--ghost" href="' . esc_url( home_url( '/' ) ) . '">No, mantenerme</a>'
			. '</form>'
		);
	}
);

/** Página HTML simple y de marca para el flujo de baja. */
function sc_unsub_render_page( $title, $body_html, $status = 200 ) {
	status_header( (int) $status );
	nocache_headers();
	$logo = esc_url( get_stylesheet_directory_uri() . '/assets/images/logo.png' );
	?>
<!doctype html>
<html lang="es">
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<meta name="robots" content="noindex,nofollow">
	<title><?php echo esc_html( $title ); ?> · Santo Café</title>
	<style>
		body{margin:0;background:#1a1310;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Arial,sans-serif;color:#2c1d11;}
		.wrap{min-height:100vh;display:flex;align-items:center;justify-content:center;padding:24px;box-sizing:border-box;}
		.card{background:#fcfaf7;max-width:480px;width:100%;border-radius:16px;padding:40px 32px;text-align:center;box-shadow:0 20px 60px rgba(0,0,0,.35);}
		.card img{height:54px;width:auto;margin:0 0 22px;}
		h1{font-size:22px;line-height:1.3;margin:0 0 14px;color:#1a1310;}
		p{font-size:15px;line-height:1.6;margin:0 0 12px;}
		.muted{color:#7a6c5b;font-size:13px;}
		.btn{display:inline-block;margin:6px;padding:12px 22px;border-radius:30px;font-weight:700;font-size:15px;text-decoration:none;border:0;cursor:pointer;background:#1a1310;color:#fff;}
		.btn--ghost{background:transparent;color:#1a1310;border:1px solid #d9cdb8;}
	</style>
</head>
<body>
	<div class="wrap">
		<div class="card">
			<img src="<?php echo $logo; // phpcs:ignore WordPress.Security.EscapeOutput ?>" alt="Santo Café">
			<h1><?php echo esc_html( $title ); ?></h1>
			<?php echo $body_html; // phpcs:ignore WordPress.Security.EscapeOutput -- HTML controlado, ya escapado arriba. ?>
		</div>
	</div>
</body>
</html>
	<?php
	exit;
}

/* ============================================================
 * Admin: WooCommerce → Emails automáticos
 * ============================================================ */

add_action(
	'admin_menu',
	function () {
		add_submenu_page(
			'woocommerce',
			'Emails automáticos',
			'Emails automáticos',
			'manage_woocommerce',
			'sc-auto-emails',
			'sc_auto_admin_page'
		);
	}
);

function sc_auto_admin_page() {
	if ( ! current_user_can( 'manage_woocommerce' ) ) {
		return;
	}
	$cfg     = sc_auto_cfg();
	$defs    = sc_auto_email_defs();
	$last    = get_option( 'sc_auto_emails_last', array() );
	$next    = function_exists( 'as_next_scheduled_action' ) ? as_next_scheduled_action( 'sc_daily_email_jobs', array(), 'santocafe' ) : false;
	$ajax_nonce = wp_create_nonce( 'sc_auto_ajax' );
	?>
	<div class="wrap">
		<h1>Emails automáticos</h1>
		<p>Envíos programados que <strong>no</strong> hace WooCommerce. Un job diario revisa quién califica y envía el template correspondiente. Solo se envía a quien no se dio de baja y a cada persona una sola vez por evento.</p>
		<?php if ( ! empty( $_GET['updated'] ) ) : ?>
			<div class="notice notice-success is-dismissible"><p>Cambios guardados.</p></div>
		<?php endif; ?>
		<?php if ( ! function_exists( 'as_schedule_recurring_action' ) ) : ?>
			<div class="notice notice-error"><p>WooCommerce / Action Scheduler no está activo: el envío automático no correrá.</p></div>
		<?php endif; ?>

		<p>
			<strong>Próxima corrida:</strong>
			<?php echo $next ? esc_html( wp_date( 'd/m/Y H:i', $next ) ) : '—'; ?>
			<?php if ( ! empty( $last['time'] ) ) : ?>
				&nbsp;·&nbsp; <strong>Última:</strong> <?php echo esc_html( wp_date( 'd/m/Y H:i', $last['time'] ) ); ?>
			<?php endif; ?>
		</p>

		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
			<input type="hidden" name="action" value="sc_save_auto_emails">
			<?php wp_nonce_field( 'sc_auto_emails' ); ?>

			<table class="form-table" role="presentation">
				<tr>
					<th scope="row"><label for="sc_send_hour">Hora de envío diaria</label></th>
					<td>
						<input name="sc[send_hour]" id="sc_send_hour" type="number" min="0" max="23" value="<?php echo esc_attr( $cfg['send_hour'] ); ?>" class="small-text"> :00
						<p class="description">Hora local de Chile en que corre el escaneo diario.</p>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="sc-test-email">Email para pruebas</label></th>
					<td>
						<input name="sc[test_email]" id="sc-test-email" type="email" class="regular-text" value="<?php echo esc_attr( $cfg['test_email'] ? $cfg['test_email'] : wp_get_current_user()->user_email ); ?>" placeholder="hola@santocafe.cl">
						<p class="description">A esta dirección se envían las pruebas (botón "Enviar prueba"). No afecta los envíos reales.</p>
					</td>
				</tr>
			</table>

			<h2>Automatizaciones</h2>
			<table class="widefat striped">
				<thead>
					<tr>
						<th style="width:38%">Automatización</th>
						<th>Parámetros</th>
						<th style="width:90px">Activo</th>
						<th style="width:240px">Destinatarios / prueba</th>
					</tr>
				</thead>
				<tbody>
				<?php foreach ( $defs as $key => $def ) : $row = $cfg['items'][ $key ]; ?>
					<tr>
						<td>
							<strong><?php echo esc_html( $def['label'] ); ?></strong><br>
							<span class="description"><?php echo esc_html( $def['desc'] ); ?></span><br>
							<code><?php echo esc_html( $def['template'] ); ?></code>
							<?php if ( ! empty( $last['log'][ $key ]['sent'] ) ) : ?>
								<br><span class="description">Último envío: <?php echo (int) $last['log'][ $key ]['sent']; ?></span>
							<?php endif; ?>
						</td>
						<td>
							<?php if ( isset( $def['params']['days'] ) ) : ?>
								<label>Días: <input type="number" min="1" max="3650" class="small-text" name="sc[items][<?php echo esc_attr( $key ); ?>][days]" value="<?php echo esc_attr( $row['days'] ); ?>"></label><br>
							<?php endif; ?>
							<?php if ( isset( $def['params']['coupon_type'] ) ) : ?>
								<label>Descuento:
									<select name="sc[items][<?php echo esc_attr( $key ); ?>][coupon_type]">
										<option value="percent" <?php selected( $row['coupon_type'], 'percent' ); ?>>%</option>
										<option value="fixed_cart" <?php selected( $row['coupon_type'], 'fixed_cart' ); ?>>$ fijo</option>
									</select>
									<input type="number" min="0" step="1" class="small-text" name="sc[items][<?php echo esc_attr( $key ); ?>][coupon_amount]" value="<?php echo esc_attr( $row['coupon_amount'] ); ?>">
								</label><br>
								<label>Vence en (días): <input type="number" min="1" max="365" class="small-text" name="sc[items][<?php echo esc_attr( $key ); ?>][coupon_days]" value="<?php echo esc_attr( $row['coupon_days'] ); ?>"></label>
							<?php endif; ?>
						</td>
						<td>
							<label><input type="checkbox" name="sc[items][<?php echo esc_attr( $key ); ?>][enabled]" value="1" <?php checked( ! empty( $row['enabled'] ) ); ?>> Activo</label>
						</td>
						<td>
							<button type="button" class="button sc-auto-preview" data-key="<?php echo esc_attr( $key ); ?>">Ver elegibles</button>
							<button type="button" class="button sc-auto-test" data-key="<?php echo esc_attr( $key ); ?>">Enviar prueba</button>
							<div class="sc-auto-result" data-for="<?php echo esc_attr( $key ); ?>" style="margin-top:6px;font-size:12px;color:#555;"></div>
						</td>
					</tr>
				<?php endforeach; ?>
				</tbody>
			</table>

			<?php submit_button( 'Guardar cambios' ); ?>
		</form>

		<p class="description">Los cupones se generan <strong>solos y únicos por cliente</strong> al enviar el email (código tipo <code>CUMPLE-AB12CD</code>), restringidos a su mail, de un solo uso y con el vencimiento indicado. Los vencidos se borran en una limpieza semanal automática. Los envíos masivos (newsletter/promo) y aviso de stock no van por acá (necesitan otra herramienta).</p>
	</div>

	<script>
	(function(){
		var ajaxurl = <?php echo wp_json_encode( admin_url( 'admin-ajax.php' ) ); ?>;
		var nonce   = <?php echo wp_json_encode( $ajax_nonce ); ?>;
		function post(action, key, cb, extras){
			var fd = new FormData();
			fd.append('action', action); fd.append('nonce', nonce); fd.append('key', key);
			if (extras) { Object.keys(extras).forEach(function(k){ fd.append(k, extras[k]); }); }
			fetch(ajaxurl, {method:'POST', body:fd, credentials:'same-origin'})
				.then(function(r){return r.json();}).then(cb)
				.catch(function(){ cb({success:false,data:{message:'Error de red'}}); });
		}
		function box(key){ return document.querySelector('.sc-auto-result[data-for="'+key+'"]'); }
		document.querySelectorAll('.sc-auto-preview').forEach(function(b){
			b.addEventListener('click', function(){
				var key=b.dataset.key, el=box(key); el.textContent='Calculando…';
				post('sc_auto_preview', key, function(res){
					if(!res.success){ el.textContent='Error.'; return; }
					var d=res.data; var t='Elegibles ahora: '+d.total;
					if(d.sample && d.sample.length){ t+=' — '+d.sample.join(', ')+(d.total>d.sample.length?'…':''); }
					el.textContent=t;
				});
			});
		});
		document.querySelectorAll('.sc-auto-test').forEach(function(b){
			b.addEventListener('click', function(){
				var key=b.dataset.key, el=box(key); el.textContent='Enviando prueba…';
				var te = document.getElementById('sc-test-email');
				post('sc_auto_test', key, function(res){
					el.textContent = (res.data && res.data.message) ? res.data.message : (res.success?'Enviado':'Error');
				}, { test_email: te ? te.value : '' });
			});
		});
	})();
	</script>
	<?php
}

add_action( 'admin_post_sc_save_auto_emails', 'sc_auto_save' );

function sc_auto_save() {
	if ( ! current_user_can( 'manage_woocommerce' ) ) {
		wp_die( 'No autorizado' );
	}
	check_admin_referer( 'sc_auto_emails' );

	$defs = sc_auto_email_defs();
	$in   = isset( $_POST['sc'] ) ? wp_unslash( $_POST['sc'] ) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput -- saneado campo por campo abajo.
	$prev = sc_auto_cfg();
	$out  = array(
		'send_hour'  => max( 0, min( 23, (int) ( $in['send_hour'] ?? 9 ) ) ),
		'test_email' => sanitize_email( $in['test_email'] ?? '' ),
		'items'      => array(),
	);
	foreach ( $defs as $key => $def ) {
		$i   = isset( $in['items'][ $key ] ) && is_array( $in['items'][ $key ] ) ? $in['items'][ $key ] : array();
		$row = array( 'enabled' => ! empty( $i['enabled'] ) );
		foreach ( $def['params'] as $pk => $pv ) {
			if ( in_array( $pk, array( 'days', 'coupon_days' ), true ) ) {
				$row[ $pk ] = max( 1, (int) ( $i[ $pk ] ?? $pv ) );
			} elseif ( 'coupon_amount' === $pk ) {
				$row[ $pk ] = max( 0, (float) ( $i[ $pk ] ?? $pv ) );
			} elseif ( 'coupon_type' === $pk ) {
				$row[ $pk ] = ( 'fixed_cart' === ( $i[ $pk ] ?? $pv ) ) ? 'fixed_cart' : 'percent';
			} else {
				$row[ $pk ] = sanitize_text_field( $i[ $pk ] ?? $pv );
			}
		}
		$out['items'][ $key ] = $row;
	}
	update_option( 'sc_auto_emails', $out );

	if ( (int) $prev['send_hour'] !== (int) $out['send_hour'] ) {
		sc_auto_reschedule( $out['send_hour'] );
	}

	wp_safe_redirect( add_query_arg( array( 'page' => 'sc-auto-emails', 'updated' => 1 ), admin_url( 'admin.php' ) ) );
	exit;
}

/* ============================================================
 * AJAX: preview de elegibles + envío de prueba
 * ============================================================ */

add_action( 'wp_ajax_sc_auto_preview', 'sc_auto_ajax_preview' );
function sc_auto_ajax_preview() {
	if ( ! current_user_can( 'manage_woocommerce' ) ) {
		wp_send_json_error( array( 'message' => 'No autorizado' ) );
	}
	check_ajax_referer( 'sc_auto_ajax', 'nonce' );
	$key  = isset( $_POST['key'] ) ? sanitize_key( wp_unslash( $_POST['key'] ) ) : '';
	$defs = sc_auto_email_defs();
	if ( ! isset( $defs[ $key ] ) ) {
		wp_send_json_error( array( 'message' => 'Automatización desconocida' ) );
	}
	$cfg  = sc_auto_cfg()['items'][ $key ];
	$list = sc_auto_recipients( $key, $cfg );
	$sample = array();
	foreach ( array_slice( $list, 0, 20 ) as $it ) {
		$sample[] = $it['email'] . ( $it['first_name'] ? ' (' . $it['first_name'] . ')' : '' );
	}
	wp_send_json_success( array( 'total' => count( $list ), 'sample' => $sample ) );
}

add_action( 'wp_ajax_sc_auto_test', 'sc_auto_ajax_test' );
function sc_auto_ajax_test() {
	if ( ! current_user_can( 'manage_woocommerce' ) ) {
		wp_send_json_error( array( 'message' => 'No autorizado' ) );
	}
	check_ajax_referer( 'sc_auto_ajax', 'nonce' );
	$key  = isset( $_POST['key'] ) ? sanitize_key( wp_unslash( $_POST['key'] ) ) : '';
	$defs = sc_auto_email_defs();
	if ( ! isset( $defs[ $key ] ) ) {
		wp_send_json_error( array( 'message' => 'Automatización desconocida' ) );
	}
	$full = sc_auto_cfg();
	$cfg  = $full['items'][ $key ];
	$user = wp_get_current_user();
	$to   = isset( $_POST['test_email'] ) ? sanitize_email( wp_unslash( $_POST['test_email'] ) ) : '';
	if ( ! is_email( $to ) ) {
		$to = $full['test_email'] && is_email( $full['test_email'] ) ? $full['test_email'] : $user->user_email;
	}
	$item = array(
		'user_id'    => $user->ID,
		'email'      => $to,
		'first_name' => sc_auto_first_name( $user->ID, 'Equipo' ),
		'order'      => null,
		'product'    => null,
	);
	// Para reposición/reseña usamos un pedido/producto de ejemplo.
	if ( in_array( $key, array( 'reposicion', 'resena' ), true ) ) {
		if ( function_exists( 'wc_get_orders' ) ) {
			$os = wc_get_orders( array( 'limit' => 1, 'orderby' => 'date', 'order' => 'DESC' ) );
			if ( $os ) {
				$item['order']   = $os[0];
				$item['product'] = sc_auto_first_product( $os[0] );
			}
		}
		if ( ! $item['product'] && function_exists( 'wc_get_products' ) ) {
			$ps = wc_get_products( array( 'limit' => 1 ) );
			if ( $ps ) {
				$item['product'] = $ps[0];
			}
		}
	}
	$ok = sc_auto_send_one( $key, $item, $cfg, $defs[ $key ] );
	if ( $ok ) {
		wp_send_json_success( array( 'message' => 'Prueba enviada a ' . $item['email'] ) );
	}
	wp_send_json_error( array( 'message' => 'No se pudo enviar (revisá SMTP / que el template exista).' ) );
}
