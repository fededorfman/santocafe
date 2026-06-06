<?php
defined('ABSPATH') || exit;

$cart_count = ( function_exists( 'WC' ) && WC()->cart )
    ? WC()->cart->get_cart_contents_count()
    : 0;
$logo_url   = get_template_directory_uri() . '/assets/images/logo.png';
$cart_url   = function_exists( 'wc_get_cart_url' )    ? wc_get_cart_url()    : home_url( '/carrito/' );
$account_url = function_exists( 'wc_get_account_endpoint_url' )
    ? wc_get_account_endpoint_url( 'dashboard' )
    : home_url( '/cuenta/' );
?><!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo( 'charset' ); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>

<header class="site-header js-site-header">

    <div class="js-banner-slot">
        <?php get_template_part( 'template-parts/shipping-banner' ); ?>
    </div>

    <nav class="site-nav" aria-label="Navegación principal">
        <div class="container site-nav__inner">

            <button class="site-nav__hamburger js-menu-toggle"
                    aria-label="Abrir menú" aria-expanded="false"
                    aria-controls="mobile-drawer">
                <span class="hamburger-bar"></span>
                <span class="hamburger-bar"></span>
                <span class="hamburger-bar"></span>
            </button>

            <a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="site-nav__logo">
                <img src="<?php echo esc_url( $logo_url ); ?>"
                     alt="<?php bloginfo( 'name' ); ?>"
                     width="120" height="52">
                <span class="site-nav__brand-name">Santo Café</span>
            </a>

            <?php
            wp_nav_menu( [
                'theme_location' => 'primary',
                'container'      => false,
                'menu_class'     => 'site-nav__menu',
                'fallback_cb'    => function () {
                    echo '<ul class="site-nav__menu">
                        <li><a href="' . esc_url( home_url( '/#catalogo' ) ) . '">Tienda</a></li>
                        <li><a href="' . esc_url( home_url( '/#nosotros' ) ) . '">Nosotros</a></li>
                        <li><a href="' . esc_url( home_url( '/#contacto' ) ) . '">Contacto</a></li>
                    </ul>';
                },
            ] );
            ?>

            <div class="site-nav__actions">

                <a href="<?php echo esc_url( $account_url ); ?>"
                   class="site-nav__action-btn" aria-label="Mi cuenta">
                    <svg class="icon" viewBox="0 0 24 24" fill="none"
                         stroke="currentColor" stroke-width="2" stroke-linecap="round"
                         stroke-linejoin="round" aria-hidden="true">
                        <circle cx="12" cy="8" r="4"/>
                        <path d="M4 20c0-4 3.6-7 8-7s8 3 8 7"/>
                    </svg>
                </a>

                <a href="<?php echo esc_url( $cart_url ); ?>"
                   class="site-nav__action-btn site-nav__cart js-open-cart-drawer" aria-label="Carrito de compras">
                    <svg class="icon" viewBox="0 0 24 24" fill="none"
                         stroke="currentColor" stroke-width="2" stroke-linecap="round"
                         stroke-linejoin="round" aria-hidden="true">
                        <path d="M6 2L3 6v14a2 2 0 002 2h14a2 2 0 002-2V6l-3-4z"/>
                        <line x1="3" y1="6" x2="21" y2="6"/>
                        <path d="M16 10a4 4 0 01-8 0"/>
                    </svg>
                    <span class="cart-icon__badge js-cart-count <?php echo $cart_count ? '' : 'is-empty'; ?>">
                        <?php echo esc_html( $cart_count ); ?>
                    </span>
                </a>

            </div>

        </div>
    </nav>

    <div id="mobile-drawer" class="mobile-drawer js-mobile-drawer" aria-hidden="true">
        <div class="mobile-drawer__header">
            <span class="mobile-drawer__title">Menú</span>
            <button class="mobile-drawer__close js-menu-toggle" aria-label="Cerrar menú">✕</button>
        </div>
        <nav class="mobile-drawer__nav" aria-label="Menú mobile">
            <?php
            wp_nav_menu( [
                'theme_location' => 'primary',
                'container'      => false,
                'menu_class'     => 'mobile-drawer__menu',
                'fallback_cb'    => function () use ( $cart_url, $account_url ) {
                    echo '<ul class="mobile-drawer__menu">
                        <li><a href="' . esc_url( home_url( '/#catalogo' ) ) . '">Tienda</a></li>
                        <li><a href="' . esc_url( home_url( '/#nosotros' ) ) . '">Nosotros</a></li>
                        <li><a href="' . esc_url( home_url( '/#contacto' ) ) . '">Contacto</a></li>
                        <li><a href="' . esc_url( $cart_url ) . '">Carrito de Compras</a></li>
                        <li><a href="' . esc_url( $account_url ) . '">Mi Cuenta</a></li>
                    </ul>';
                },
            ] );
            ?>
        </nav>
    </div>

    <div class="mobile-drawer-overlay js-drawer-overlay" aria-hidden="true"></div>

</header>

<!-- Cart drawer (mini-cart, slides from the right) -->
<aside class="cart-drawer js-cart-drawer" aria-hidden="true" aria-label="Carrito de compras">
    <div class="cart-drawer__header">
        <span class="cart-drawer__title">Tu carrito</span>
        <button class="cart-drawer__close js-cart-drawer-close" aria-label="Cerrar carrito">✕</button>
    </div>

    <div class="cart-drawer__body">
        <div class="widget_shopping_cart_content">
            <?php woocommerce_mini_cart(); ?>
        </div>
    </div>

    <div class="cart-drawer__footer">
        <button type="button" class="btn btn--outline btn--full js-cart-drawer-close">
            Seguir comprando
        </button>
        <a href="<?php echo esc_url( function_exists( 'wc_get_checkout_url' ) ? wc_get_checkout_url() : $cart_url ); ?>"
           class="btn btn--primary btn--full cart-drawer__checkout">
            Finalizar compra →
        </a>
    </div>
</aside>
<div class="cart-drawer-overlay js-cart-drawer-overlay" aria-hidden="true"></div>
