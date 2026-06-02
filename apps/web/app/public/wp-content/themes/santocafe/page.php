<?php
/**
 * Default page template.
 * Provides the page shell for generic pages and WooCommerce shortcode
 * pages (cart/checkout/account). WooCommerce pages render their own
 * headings, so the auto title is suppressed for them.
 */
defined( 'ABSPATH' ) || exit;

get_header();
?>

<main class="site-main page-main">
    <div class="container">
        <?php
        while ( have_posts() ) :
            the_post();

            $is_wc_page = function_exists( 'is_woocommerce' )
                && ( is_cart() || is_checkout() || is_account_page() );

            if ( ! $is_wc_page ) {
                echo '<h1 class="page-title">' . esc_html( get_the_title() ) . '</h1>';
            }

            the_content();
        endwhile;
        ?>
    </div>
</main>

<?php
get_footer();
