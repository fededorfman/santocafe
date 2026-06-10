<?php
/**
 * 404 — página no encontrada.
 * Estado vacío con el mismo patrón visual que el carrito vacío
 * (imagen redonda + mensaje + CTA al catálogo).
 */
defined( 'ABSPATH' ) || exit;

get_header();

$catalog_url = home_url( '/#catalogo' );
?>

<main class="site-main page-main" id="main">
    <div class="container">
        <section class="error-404">

            <div class="error-404__image" aria-hidden="true">
                <img src="<?php echo esc_url( get_template_directory_uri() . '/assets/images/404.png' ); ?>"
                     alt="" width="200" height="200" loading="eager">
            </div>

            <h1 class="error-404__title">Ups, no pudimos encontrar tu café.</h1>
            <p class="error-404__text">
                La página que buscás no existe o se movió.<br>
                Descubrí nuestros orígenes de especialidad.
            </p>
            <a href="<?php echo esc_url( $catalog_url ); ?>" class="btn btn--primary btn--lg">
                Ver nuestros cafés
            </a>

        </section>
    </div>
</main>

<?php
get_footer();
