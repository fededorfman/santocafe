<?php
/**
 * Archive / Category — Santo Café
 *
 * Hub page for Guías (and any other post category archives).
 * JSON-LD BreadcrumbList is emitted by inc/seo.php via wp_head.
 *
 * @package santocafe
 */
defined( 'ABSPATH' ) || exit;

get_header();

$sc_queried = get_queried_object();
$sc_is_cat  = $sc_queried instanceof WP_Term;
$sc_title   = $sc_is_cat ? $sc_queried->name : 'Guías de Café';
$sc_desc    = $sc_is_cat && $sc_queried->description
    ? $sc_queried->description
    : 'Todo lo que necesitas saber para preparar y elegir tu café de especialidad.';
?>

<main class="site-main page-main" id="main">
    <div class="container">

        <?php get_template_part( 'template-parts/breadcrumbs' ); ?>

        <section class="sc-archive">

            <header class="sc-archive__header">
                <span class="sc-archive__kicker">Café de especialidad</span>
                <h1 class="sc-archive__title"><?php echo esc_html( $sc_title ); ?></h1>
                <p class="sc-archive__desc"><?php echo esc_html( $sc_desc ); ?></p>
            </header>

            <?php if ( have_posts() ) : ?>

                <div class="sc-archive__grid">
                    <?php while ( have_posts() ) : the_post(); ?>

                        <a class="sc-guide-card" href="<?php the_permalink(); ?>">

                            <?php if ( has_post_thumbnail() ) : ?>
                                <img class="sc-guide-card__thumb"
                                     src="<?php echo esc_url( get_the_post_thumbnail_url( get_the_ID(), 'medium_large' ) ); ?>"
                                     alt="<?php echo esc_attr( get_the_title() ); ?>"
                                     loading="lazy" width="768" height="432">
                            <?php else : ?>
                                <div class="sc-guide-card__thumb"></div>
                            <?php endif; ?>

                            <div class="sc-guide-card__body">
                                <?php
                                $sc_cats = get_the_category();
                                if ( $sc_cats ) :
                                ?>
                                    <span class="sc-guide-card__kicker">
                                        <?php echo esc_html( $sc_cats[0]->name ); ?>
                                    </span>
                                <?php endif; ?>

                                <h2 class="sc-guide-card__title"><?php the_title(); ?></h2>

                                <p class="sc-guide-card__excerpt">
                                    <?php echo esc_html( wp_trim_words( get_the_excerpt(), 20, '…' ) ); ?>
                                </p>

                                <div class="sc-guide-card__footer">
                                    <time datetime="<?php echo esc_attr( get_the_date( 'Y-m-d' ) ); ?>">
                                        <?php echo esc_html( get_the_date( 'j M Y' ) ); ?>
                                    </time>
                                    <?php
                                    $sc_wc  = str_word_count( wp_strip_all_tags( get_the_content() ) );
                                    $sc_min = max( 1, (int) ceil( $sc_wc / 200 ) );
                                    echo esc_html( $sc_min . ' min' );
                                    ?>
                                </div>
                            </div>

                        </a>

                    <?php endwhile; ?>
                </div>

                <?php
                // Pagination
                the_posts_pagination( [
                    'mid_size'           => 2,
                    'prev_text'          => '← Anteriores',
                    'next_text'          => 'Siguientes →',
                    'screen_reader_text' => 'Navegación de páginas',
                    'class'              => 'sc-pagination',
                ] );
                ?>

            <?php else : ?>

                <div class="sc-orders-empty" style="text-align:center; padding: var(--spacing-2xl) 0;">
                    <h2 style="font-size:1.5rem; margin-bottom:1rem;">Próximamente</h2>
                    <p style="color:var(--color-texto-suave);">Estamos preparando guías para ayudarte a sacar el mejor partido de tu café.</p>
                    <a href="<?php echo esc_url( home_url( '/#catalogo' ) ); ?>" class="btn btn--primary" style="margin-top:1.5rem; display:inline-block;">
                        Ver nuestros cafés
                    </a>
                </div>

            <?php endif; ?>

        </section>

    </div>
</main>

<?php get_footer(); ?>
