<?php
/**
 * Reseña por pedido (no por producto) de Santo Café.
 *
 * El email de reseña lleva a una página propia donde el cliente elige 1 a 5
 * estrellas (con efecto de relleno) y deja un comentario opcional. La reseña
 * queda guardada en el pedido (_sc_order_rating / _sc_order_review).
 *
 * Si elige 1 a 4: se le pide contar en qué mejorar.
 * Si elige 5: se lo invita a dejar una reseña en Google o recomendar.
 *
 * Loaded by functions.php.
 */
defined( 'ABSPATH' ) || exit;

/* ============================================================
 * Token + URL de la página de reseña
 * ============================================================ */
function sc_review_token( $order_id ) {
	return wp_hash( 'sc_review_' . (int) $order_id );
}

function sc_review_url( $order_id, $rating = 0 ) {
	$args = array(
		'sc_review' => (int) $order_id,
		'k'         => sc_review_token( $order_id ),
	);
	if ( $rating >= 1 && $rating <= 5 ) {
		$args['rating'] = (int) $rating;
	}
	return add_query_arg( $args, home_url( '/' ) );
}

/** URL de reseña en Google (configurable desde el panel de Emails automáticos). */
function sc_google_review_url() {
	$opt = get_option( 'sc_auto_emails', array() );
	$url = is_array( $opt ) && ! empty( $opt['google_review_url'] ) ? $opt['google_review_url'] : '';
	return $url ? esc_url( $url ) : '';
}

/* ============================================================
 * Resumen de items del pedido (texto plano por línea)
 * ============================================================ */
function sc_order_items_lines( $order ) {
	$lines = array();
	if ( ! $order ) {
		return $lines;
	}
	foreach ( $order->get_items() as $item ) {
		$lines[] = $item->get_quantity() . '× ' . $item->get_name();
	}
	return $lines;
}

/**
 * Items del pedido con miniatura.
 *
 * @return array<int,array{img:string,label:string}>
 */
function sc_order_items_detailed( $order ) {
	$out = array();
	if ( ! $order ) {
		return $out;
	}
	foreach ( $order->get_items() as $item ) {
		$product = $item->get_product();
		$img_id  = $product ? $product->get_image_id() : 0;
		if ( ! $img_id && $product && $product->get_parent_id() ) {
			$img_id = get_post_thumbnail_id( $product->get_parent_id() );
		}
		$img = $img_id ? wp_get_attachment_image_url( $img_id, 'woocommerce_thumbnail' ) : '';
		if ( ! $img && function_exists( 'wc_placeholder_img_src' ) ) {
			$img = wc_placeholder_img_src( 'woocommerce_thumbnail' );
		}
		$out[] = array(
			'img'   => $img ? $img : '',
			'label' => $item->get_quantity() . '× ' . $item->get_name(),
		);
	}
	return $out;
}

/* ============================================================
 * Endpoint de la página de reseña
 * ============================================================ */
add_action( 'template_redirect', function () {
	if ( empty( $_GET['sc_review'] ) ) {
		return;
	}
	$oid = absint( $_GET['sc_review'] );
	$k   = isset( $_GET['k'] ) ? sanitize_text_field( wp_unslash( $_GET['k'] ) ) : '';
	if ( ! $oid || ! hash_equals( sc_review_token( $oid ), $k ) || ! function_exists( 'wc_get_order' ) ) {
		sc_review_render_invalid();
	}
	$order = wc_get_order( $oid );
	if ( ! $order ) {
		sc_review_render_invalid();
	}

	// Guardado (POST).
	if ( 'POST' === ( $_SERVER['REQUEST_METHOD'] ?? '' ) && isset( $_POST['sc_review_submit'] ) ) {
		$rating  = max( 1, min( 5, (int) ( $_POST['rating'] ?? 0 ) ) );
		$comment = isset( $_POST['comment'] ) ? sanitize_textarea_field( wp_unslash( $_POST['comment'] ) ) : '';
		$order->update_meta_data( '_sc_order_rating', $rating );
		$order->update_meta_data( '_sc_order_review', $comment );
		$order->update_meta_data( '_sc_order_reviewed_at', time() );
		$order->save();
		sc_review_render_thanks( $order, $rating );
	}

	// Formulario (GET).
	$preset = isset( $_GET['rating'] ) ? max( 0, min( 5, (int) $_GET['rating'] ) ) : (int) $order->get_meta( '_sc_order_rating' );
	sc_review_render_form( $order, $preset );
} );

/* ============================================================
 * Render de páginas (standalone, con la marca)
 * ============================================================ */

/** Cabecera + estilos comunes. Devuelve el HTML de apertura. */
function sc_review_layout_open( $title ) {
	status_header( 200 );
	nocache_headers();
	$logo = esc_url( get_stylesheet_directory_uri() . '/assets/images/logo.png' );
	ob_start();
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
		.card{background:#fcfaf7;max-width:520px;width:100%;border-radius:16px;padding:40px 34px;text-align:center;box-shadow:0 20px 60px rgba(0,0,0,.35);}
		.card img.logo{height:50px;width:auto;margin:0 0 20px;}
		h1{font-size:23px;line-height:1.3;margin:0 0 10px;color:#1a1310;}
		p{font-size:15px;line-height:1.6;margin:0 0 12px;}
		.muted{color:#7a6c5b;font-size:13px;}
		.order-box{background:#f3ece1;border-radius:12px;padding:14px 18px;margin:18px 0;text-align:left;}
		.order-box .lbl{font-size:12px;letter-spacing:.08em;text-transform:uppercase;color:#8a7d6b;margin:0 0 10px;}
		.order-item{display:flex;align-items:center;gap:12px;padding:5px 0;}
		.order-item__img{width:44px;height:44px;border-radius:8px;object-fit:cover;background:#fff;flex:0 0 auto;}
		.order-item span{font-size:14px;line-height:1.4;color:#2c1d11;}
		.sc-stars{display:inline-flex;flex-direction:row-reverse;justify-content:center;gap:8px;margin:8px 0 2px;}
		.sc-stars input{position:absolute;width:1px;height:1px;opacity:0;}
		.sc-stars label{font-size:46px;line-height:1;color:#d9cdb8;cursor:pointer;transition:transform .12s ease,color .15s ease;}
		.sc-stars label:hover{transform:scale(1.15);}
		.sc-stars label:hover,.sc-stars label:hover ~ label{color:#dfb33e;}
		.sc-stars input:checked ~ label{color:#dfb33e;}
		.sc-stars input:focus-visible + label{outline:2px solid #dfb33e;outline-offset:3px;border-radius:4px;}
		.dyn{min-height:24px;font-size:15px;font-weight:600;color:#1a1310;margin:6px 0 14px;}
		.dyn:empty{min-height:0;margin:0;}
		.dyn a{color:#8a6d1f;}
		textarea{width:100%;box-sizing:border-box;min-height:96px;border:1px solid #d9cdb8;border-radius:10px;padding:12px 14px;font-family:inherit;font-size:15px;color:#2c1d11;background:#fff;resize:vertical;}
		.btn{display:inline-block;margin:18px 6px 0;padding:13px 26px;border-radius:30px;font-weight:700;font-size:15px;text-decoration:none;border:0;cursor:pointer;background:#dfb33e;color:#1a1310;}
		.btn--dark{background:#1a1310;color:#fff;}
		.btn--ghost{background:transparent;color:#1a1310;border:1px solid #d9cdb8;}
		.btn[disabled]{opacity:.5;cursor:not-allowed;}
		.g-box{display:none;margin:14px 0 22px;}
		.g-box p{color:#3a2f27;}
		.coupon-box{display:inline-block;border:2px dashed #dfb33e;border-radius:12px;padding:12px 26px;font-size:24px;font-weight:700;letter-spacing:2px;color:#1a1310;margin:6px 0 4px;}
	</style>
</head>
<body>
	<div class="wrap">
		<div class="card">
			<img class="logo" src="<?php echo $logo; // phpcs:ignore WordPress.Security.EscapeOutput ?>" alt="Santo Café">
	<?php
	return ob_get_clean();
}

function sc_review_layout_close() {
	echo '</div></div></body></html>';
	exit;
}

function sc_review_render_invalid() {
	echo sc_review_layout_open( 'Enlace no válido' ); // phpcs:ignore WordPress.Security.EscapeOutput
	echo '<h1>Enlace no válido</h1>';
	echo '<p>Este enlace de reseña no es válido o ya expiró.</p>';
	echo '<p><a class="btn btn--ghost" href="' . esc_url( home_url( '/' ) ) . '">Ir a Santo Café</a></p>';
	sc_review_layout_close();
}

function sc_review_render_form( $order, $preset = 0 ) {
	$items    = sc_order_items_detailed( $order );
	$action   = esc_url( sc_review_url( $order->get_id() ) );
	$gurl     = sc_google_review_url();
	$existing = isset( $_GET['rating'] ) ? '' : sanitize_textarea_field( (string) $order->get_meta( '_sc_order_review' ) );

	echo sc_review_layout_open( 'Tu reseña' ); // phpcs:ignore WordPress.Security.EscapeOutput
	?>
	<h1>¿Cómo estuvo tu pedido?</h1>
	<p class="muted">Pedido #<?php echo esc_html( $order->get_order_number() ); ?>. Tu opinión nos ayuda muchísimo.</p>

	<?php if ( $items ) : ?>
	<div class="order-box">
		<p class="lbl">Tu pedido</p>
		<?php foreach ( $items as $it ) : ?>
		<div class="order-item">
			<?php if ( $it['img'] ) : ?>
			<img class="order-item__img" src="<?php echo esc_url( $it['img'] ); ?>" alt="" width="44" height="44">
			<?php endif; ?>
			<span><?php echo esc_html( $it['label'] ); ?></span>
		</div>
		<?php endforeach; ?>
	</div>
	<?php endif; ?>

	<form method="post" action="<?php echo $action; // phpcs:ignore WordPress.Security.EscapeOutput ?>">
		<div class="sc-stars" id="sc-stars" role="radiogroup" aria-label="Puntaje">
			<?php for ( $i = 5; $i >= 1; $i-- ) : ?>
			<input type="radio" id="sc-star-<?php echo $i; ?>" name="rating" value="<?php echo $i; ?>" <?php checked( $preset, $i ); ?> required>
			<label for="sc-star-<?php echo $i; ?>" title="<?php echo $i; ?>" aria-label="<?php echo esc_attr( $i ); ?> estrellas">&#9733;</label>
			<?php endfor; ?>
		</div>

		<div class="dyn" id="sc-dyn"></div>

		<div class="g-box" id="sc-gbox">
			<p style="margin:0 0 10px;">¡Nos encanta saber que te gustó tu pedido! Nos ayudarías muchísimo dejando una reseña pública o recomendándonos, así llegamos a más personas.</p>
			<?php if ( $gurl ) : ?>
			<a class="btn" href="<?php echo $gurl; // phpcs:ignore WordPress.Security.EscapeOutput ?>" target="_blank" rel="noopener">Dejar reseña en Google</a>
			<?php endif; ?>
		</div>

		<textarea name="comment" id="sc-comment" placeholder="Tu comentario (opcional)"><?php echo esc_textarea( $existing ); ?></textarea>

		<div>
			<button type="submit" name="sc_review_submit" value="1" class="btn">Enviar mi reseña</button>
		</div>
	</form>

	<script>
	(function(){
		var radios = document.querySelectorAll('#sc-stars input[name="rating"]');
		var dyn = document.getElementById('sc-dyn');
		var gbox = document.getElementById('sc-gbox');
		var ta = document.getElementById('sc-comment');
		function update(){
			var v = 0;
			radios.forEach(function(r){ if(r.checked){ v = parseInt(r.value,10); } });
			if(!v){ dyn.textContent=''; gbox.style.display='none'; return; }
			if(v >= 5){
				dyn.textContent='';
				gbox.style.display='block';
				if(ta){ ta.placeholder='¿Algo que quieras destacar? (opcional)'; }
			} else {
				gbox.style.display='none';
				dyn.textContent='Cuéntanos en qué podemos mejorar.';
				if(ta){ ta.placeholder='Cuéntanos qué pasó para mejorar (opcional)'; }
			}
		}
		radios.forEach(function(r){ r.addEventListener('change', update); });
		update();

		// Evita doble envío (doble click): bloquea el segundo submit.
		var form = document.querySelector('form');
		var sending = false;
		if (form) {
			form.addEventListener('submit', function (e) {
				if (sending) { e.preventDefault(); return; }
				sending = true;
				var btn = form.querySelector('button[type="submit"]');
				if (btn) { window.setTimeout(function(){ btn.disabled = true; btn.textContent = 'Enviando...'; }, 0); }
			});
		}
	})();
	</script>
	<?php
	sc_review_layout_close();
}

function sc_review_render_thanks( $order, $rating ) {
	$gurl = sc_google_review_url();
	$code = '';
	echo sc_review_layout_open( '¡Gracias!' ); // phpcs:ignore WordPress.Security.EscapeOutput
	echo '<h1>¡Gracias por tu reseña!</h1>';

	if ( $rating >= 5 ) {
		echo '<p>Nos alegra mucho que hayas disfrutado tu pedido.</p>';
		if ( $gurl ) {
			echo '<p>Si te animas, dejarnos una reseña en Google nos ayuda a llegar a más amantes del café.</p>';
			echo '<p><a class="btn" href="' . $gurl . '" target="_blank" rel="noopener">Dejar reseña en Google</a></p>'; // phpcs:ignore WordPress.Security.EscapeOutput
		}
	} elseif ( $rating >= 4 ) {
		echo '<p>Gracias por tu reseña. Tus comentarios nos ayudan a seguir mejorando.</p>';
	} else {
		// 3 estrellas o menos: disculpa + cupón de 10%. UN SOLO cupón por pedido:
		// releemos el pedido fresco de la base y solo generamos si no existe ya.
		$fresh = function_exists( 'wc_get_order' ) ? wc_get_order( $order->get_id() ) : $order;
		$code  = $fresh ? (string) $fresh->get_meta( '_sc_review_coupon' ) : '';
		if ( '' === $code && $fresh && function_exists( 'sc_auto_generate_coupon' ) ) {
			$code = sc_auto_generate_coupon( 'GRACIAS', $fresh->get_billing_email(), 'percent', 10, 30 );
			if ( $code ) {
				$fresh->update_meta_data( '_sc_review_coupon', $code );
				$fresh->save();
			}
		}
		echo '<p>Lamentamos que tu experiencia no haya sido la mejor. Gracias por contárnoslo: lo tomamos muy en serio para mejorar.</p>';
		if ( $code ) {
			echo '<p>Queremos compensarte con un <strong>10% de descuento</strong> para tu próximo pedido:</p>';
			echo '<div class="coupon-box">' . esc_html( $code ) . '</div>';
			echo '<p class="muted">Válido por 30 días, un solo uso.</p>';
			$curl = add_query_arg( 'sc_coupon', rawurlencode( $code ), home_url( '/' ) ) . '#catalogo';
			echo '<p><a class="btn" href="' . esc_url( $curl ) . '">Usar mi descuento</a></p>';
		}
	}

	$secondary = ( $rating <= 3 && $code );
	echo '<p><a class="btn ' . ( $secondary ? 'btn--ghost' : '' ) . '" href="' . esc_url( home_url( '/' ) ) . '">Volver a Santo Café</a></p>';
	sc_review_layout_close();
}

/* ============================================================
 * Admin: mostrar la reseña en la ficha del pedido
 * ============================================================ */
add_action( 'woocommerce_admin_order_data_after_order_details', function ( $order ) {
	$r = (int) $order->get_meta( '_sc_order_rating' );
	if ( ! $r ) {
		return;
	}
	$c     = (string) $order->get_meta( '_sc_order_review' );
	$stars = str_repeat( '★', $r ) . str_repeat( '☆', 5 - $r );
	echo '<p class="form-field form-field-wide" style="margin-top:10px;"><strong>Reseña del cliente:</strong> <span style="color:#dfb33e;font-size:16px;">' . esc_html( $stars ) . '</span> (' . (int) $r . '/5)</p>';
	if ( '' !== $c ) {
		echo '<p class="form-field form-field-wide" style="white-space:pre-wrap;background:#f7f7f7;padding:8px 10px;border-radius:6px;">' . esc_html( $c ) . '</p>';
	}
} );
