<?php
defined('ABSPATH') || exit;

$shop_url    = ( function_exists( 'wc_get_page_id' ) && wc_get_page_id( 'shop' ) > 0 )
    ? get_permalink( wc_get_page_id( 'shop' ) )
    : home_url( '/' );
$cart_url    = function_exists( 'wc_get_cart_url' )    ? wc_get_cart_url()    : home_url( '/carrito/' );
$account_url = function_exists( 'wc_get_account_endpoint_url' )
    ? wc_get_account_endpoint_url( 'dashboard' )
    : home_url( '/cuenta/' );
?>

<footer class="site-footer">
    <div class="container">
        <div class="site-footer__grid">

            <!-- Col 1: Identidad -->
            <div class="site-footer__col site-footer__brand">
                <a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="site-footer__logo">
                    <img src="<?php echo esc_url( get_template_directory_uri() . '/assets/images/logo.png' ); ?>"
                         alt="<?php bloginfo( 'name' ); ?>"
                         width="100" height="35">
                </a>
                <p class="site-footer__tagline">
                    Café de especialidad.<br>Del origen a tu taza, en Chile.
                </p>
                <div class="site-footer__social">
                    <a href="#" class="social-icon" aria-label="Facebook" rel="noopener">
                        <svg viewBox="0 0 24 24" fill="currentColor" width="18" height="18" aria-hidden="true">
                            <path d="M18 2h-3a5 5 0 00-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 011-1h3z"/>
                        </svg>
                    </a>
                    <a href="#" class="social-icon" aria-label="Instagram" rel="noopener">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"
                             stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                             width="18" height="18" aria-hidden="true">
                            <rect x="2" y="2" width="20" height="20" rx="5" ry="5"/>
                            <circle cx="12" cy="12" r="4"/>
                            <circle cx="17.5" cy="6.5" r="1" fill="currentColor" stroke="none"/>
                        </svg>
                    </a>
                </div>
            </div>

            <!-- Col 2: Navegación principal -->
            <div class="site-footer__col">
                <h4 class="site-footer__heading">Navegación</h4>
                <ul class="site-footer__links">
                    <li><a href="<?php echo esc_url( home_url( '/#catalogo' ) ); ?>">Nuestros Cafés</a></li>
                    <li><a href="<?php echo esc_url( home_url( '/#nosotros' ) ); ?>">Sobre Nosotros</a></li>
                    <li><a href="<?php echo esc_url( home_url( '/#contacto' ) ); ?>">Contacto</a></li>
                </ul>
            </div>

            <!-- Col 3: Preparación -->
            <div class="site-footer__col">
                <h4 class="site-footer__heading">Preparación</h4>
                <ul class="site-footer__links">
                    <li><a href="#">Café para espresso</a></li>
                    <li><a href="#">Café para italiana</a></li>
                    <li><a href="#">Café para filtro</a></li>
                    <li><a href="#">Café arábica</a></li>
                    <li><a href="#">Café ecológico</a></li>
                </ul>
            </div>

            <!-- Col 4: Legal -->
            <div class="site-footer__col">
                <h4 class="site-footer__heading">Legal</h4>
                <ul class="site-footer__links">
                    <?php
                    $sc_legal_pages = [
                        'aviso-legal'            => 'Aviso legal',
                        'politica-de-privacidad' => 'Política de privacidad',
                        'politica-de-cookies'    => 'Política de cookies',
                        'condiciones-de-venta'   => 'Condiciones de venta',
                    ];
                    foreach ( $sc_legal_pages as $sc_slug => $sc_label ) :
                        $sc_page = get_page_by_path( $sc_slug );
                        $sc_url  = $sc_page ? get_permalink( $sc_page ) : home_url( '/' . $sc_slug . '/' );
                        ?>
                        <li><a href="<?php echo esc_url( $sc_url ); ?>"><?php echo esc_html( $sc_label ); ?></a></li>
                    <?php endforeach; ?>
                </ul>
            </div>

        </div>
    </div>

    <div class="site-footer__bottom">
        <div class="container site-footer__bottom-inner">
            <span>© <?php echo esc_html( date( 'Y' ) ); ?> Santo Café · Café de especialidad en Chile.</span>
            <span class="site-footer__bottom-tagline">Un buen día, un buen café.</span>
        </div>
    </div>
</footer>

<?php wp_footer(); ?>
</body>
</html>
