<?php
/**
 * Order Item Details — Santo Café override.
 * Adds the product thumbnail and a structured name/meta/qty layout.
 * Keeps the WooCommerce hooks/filters so it stays compatible.
 *
 * @see woocommerce/templates/order/order-details-item.php
 * @package santocafe
 */

defined( 'ABSPATH' ) || exit;

if ( ! apply_filters( 'woocommerce_order_item_visible', true, $item ) ) {
	return;
}

$is_visible        = $product && $product->is_visible();
$product_permalink = apply_filters( 'woocommerce_order_item_permalink', $is_visible ? $product->get_permalink( $item ) : '', $item, $order );

$qty          = $item->get_quantity();
$refunded_qty = $order->get_qty_refunded_for_item( $item_id );
if ( $refunded_qty ) {
	$qty_display = '<del>' . esc_html( $qty ) . '</del> <ins>' . esc_html( $qty - ( $refunded_qty * -1 ) ) . '</ins>';
} else {
	$qty_display = esc_html( $qty );
}

$thumb = $product ? $product->get_image( 'woocommerce_thumbnail', [ 'class' => 'sc-order-item__img' ] ) : '';
$name  = apply_filters(
	'woocommerce_order_item_name',
	$product_permalink ? sprintf( '<a href="%s">%s</a>', $product_permalink, $item->get_name() ) : $item->get_name(),
	$item,
	$is_visible
);
?>
<tr class="<?php echo esc_attr( apply_filters( 'woocommerce_order_item_class', 'woocommerce-table__line-item order_item', $item, $order ) ); ?>">

	<td class="woocommerce-table__product-name product-name" data-title="Producto">
		<div class="sc-order-item">
			<?php if ( $thumb ) : ?>
				<div class="sc-order-item__media">
					<?php if ( $product_permalink ) : ?>
						<a href="<?php echo esc_url( $product_permalink ); ?>"><?php echo $thumb; // phpcs:ignore ?></a>
					<?php else : echo $thumb; // phpcs:ignore ?>
					<?php endif; ?>
				</div>
			<?php endif; ?>

			<div class="sc-order-item__info">
				<span class="sc-order-item__name"><?php echo wp_kses_post( $name ); ?></span>
				<span class="sc-order-item__qty">Cantidad: <strong><?php echo wp_kses_post( $qty_display ); ?></strong></span>

				<div class="sc-order-item__meta">
					<?php
					do_action( 'woocommerce_order_item_meta_start', $item_id, $item, $order, false );
					wc_display_item_meta( $item ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
					do_action( 'woocommerce_order_item_meta_end', $item_id, $item, $order, false );
					?>
				</div>
			</div>
		</div>
	</td>

	<td class="woocommerce-table__product-total product-total" data-title="Total">
		<?php echo $order->get_formatted_line_subtotal( $item ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
	</td>

</tr>

<?php if ( $show_purchase_note && $purchase_note ) : ?>
<tr class="woocommerce-table__product-purchase-note product-purchase-note">
	<td colspan="2"><?php echo wpautop( do_shortcode( wp_kses_post( $purchase_note ) ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></td>
</tr>
<?php endif; ?>
