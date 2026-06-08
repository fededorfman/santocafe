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
                    <a href="https://www.instagram.com/santocafespecialtycoffee/"
                       class="social-icon" aria-label="Santo Café en Instagram"
                       rel="noopener noreferrer" target="_blank">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"
                             stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                             width="18" height="18" aria-hidden="true">
                            <rect x="2" y="2" width="20" height="20" rx="5" ry="5"/>
                            <circle cx="12" cy="12" r="4"/>
                            <circle cx="17.5" cy="6.5" r="1" fill="currentColor" stroke="none"/>
                        </svg>
                    </a>
                    <a href="https://wa.me/56996416308"
                       class="social-icon" aria-label="Contactar por WhatsApp"
                       rel="noopener noreferrer" target="_blank">
                        <svg viewBox="0 0 24 24" fill="currentColor" width="18" height="18" aria-hidden="true">
                            <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/>
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

            <!-- Col 3: Guías -->
            <div class="site-footer__col">
                <h4 class="site-footer__heading">Guías</h4>
                <ul class="site-footer__links">
                    <?php
                    $sc_guias = [
                        'como-preparar-espresso'         => 'Cómo preparar espresso',
                        'como-preparar-cafe-en-italiana' => 'Café en italiana',
                        'como-preparar-cafe-de-filtro'   => 'Café de filtro (V60)',
                        'que-es-el-cafe-de-especialidad' => 'Qué es el café de especialidad',
                        'cafe-lavado-vs-natural'         => 'Lavado vs natural',
                    ];
                    foreach ( $sc_guias as $sc_guia_slug => $sc_guia_label ) :
                        // Link to guide post if it exists; otherwise to /guias/
                        $sc_guia_post = get_page_by_path( $sc_guia_slug, OBJECT, 'post' );
                        $sc_guia_url  = $sc_guia_post
                            ? get_permalink( $sc_guia_post )
                            : sc_guias_url();
                        ?>
                        <li><a href="<?php echo esc_url( $sc_guia_url ); ?>"><?php echo esc_html( $sc_guia_label ); ?></a></li>
                    <?php endforeach; ?>
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
