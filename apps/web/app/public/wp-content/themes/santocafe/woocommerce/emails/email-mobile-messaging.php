<?php
/**
 * Email Mobile Messaging — Santo Café override (vacío a propósito).
 *
 * WooCommerce agrega en los emails de "Nuevo pedido" un bloque promocionando su
 * app móvil ("Procesa tus pedidos sobre la marcha. Consigue la aplicación.").
 * No lo queremos, así que sobrescribimos el template para que no renderice nada.
 *
 * @see woocommerce/templates/emails/email-mobile-messaging.php
 * @package santocafe
 * @version 10.0.0
 */

defined( 'ABSPATH' ) || exit;
