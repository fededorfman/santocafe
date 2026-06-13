<?php
/**
 * "Order received" heading — Santo Café override.
 *
 * En este tema, este template solo se usa en las "puertas" de verificación que
 * muestra WooCommerce cuando hay que iniciar sesión o verificar el email para
 * ver un pedido (core lo invoca con $order = false). El caso exitoso usa
 * checkout/thankyou.php, que tiene su propio hero. Lo dejamos sin salida para no
 * duplicar el encabezado: la tarjeta de login/verificación ya trae su título.
 *
 * @see woocommerce/templates/checkout/order-received.php
 * @var WC_Order|false $order
 * @package santocafe
 */

defined( 'ABSPATH' ) || exit;
