<?php
/**
 * Template Name: Legal
 *
 * Long-form legal / policy pages (Aviso Legal, Política de Privacidad,
 * Política de Cookies, Condiciones de Venta). Renders the page content in a
 * narrow, readable column with the shared `.legal` typography.
 *
 * @package santocafe
 */

defined( 'ABSPATH' ) || exit;

get_header();
?>

<main class="site-main page-main">
    <div class="container">
        <?php while ( have_posts() ) : the_post(); ?>
        <article class="legal">
            <header class="legal__header">
                <h1 class="legal__title"><?php the_title(); ?></h1>
                <p class="legal__updated">
                    Última actualización:
                    <time datetime="<?php echo esc_attr( get_the_modified_date( 'Y-m-d' ) ); ?>">
                        <?php echo esc_html( get_the_modified_date( 'j \d\e F \d\e Y' ) ); ?>
                    </time>
                </p>
            </header>

            <div class="legal__body">
                <?php the_content(); ?>
            </div>
        </article>
        <?php endwhile; ?>
    </div>
</main>

<?php
get_footer();
