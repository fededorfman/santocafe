<?php
/**
 * My Account page (logged in) — Santo Café override.
 * Greeting + two-column layout (navigation left, content right).
 *
 * @see woocommerce/templates/myaccount/my-account.php
 * @version 3.5.0
 */
defined( 'ABSPATH' ) || exit;

$current_user = wp_get_current_user();
$sc_name      = $current_user->first_name ? $current_user->first_name : $current_user->display_name;
?>

<div class="sc-myaccount">

    <h1 class="sc-myaccount__greeting">Hola, <?php echo esc_html( $sc_name ); ?></h1>

    <div class="sc-myaccount__layout">
        <?php do_action( 'woocommerce_account_navigation' ); ?>

        <div class="woocommerce-MyAccount-content">
            <?php do_action( 'woocommerce_account_content' ); ?>
        </div>
    </div>

</div>
