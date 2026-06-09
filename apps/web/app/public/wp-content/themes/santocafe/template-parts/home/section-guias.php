<?php
/**
 * Home — Panel de últimas guías
 * Muestra las 4 guías más recientes de la categoría "guias".
 * Si no hay guías publicadas, el panel no se renderiza.
 */
defined('ABSPATH') || exit;

$sc_guias = new WP_Query( [
    'category_name'       => 'guias',
    'posts_per_page'      => 4,
    'orderby'             => 'date',
    'order'               => 'DESC',
    'post_status'         => 'publish',
    'no_found_rows'       => true,
    'ignore_sticky_posts' => true,
] );

if ( ! $sc_guias->have_posts() ) {
    wp_reset_postdata();
    return;
}
?>

<section class="home-guias" aria-label="Guías de café">
    <div class="container">

        <header class="home-guias__header">
            <span class="home-guias__kicker">Guías de preparación</span>
            <h2 class="home-guias__title">Aprendé a preparar el café perfecto</h2>
            <p class="home-guias__desc">
                Desde elegir la molienda correcta hasta dominar tu método favorito.
            </p>
        </header>

        <div class="home-guias__grid">
            <?php while ( $sc_guias->have_posts() ) : $sc_guias->the_post(); ?>

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
                        <?php $sc_cat = get_the_category(); ?>
                        <?php if ( $sc_cat ) : ?>
                            <span class="sc-guide-card__kicker">
                                <?php echo esc_html( $sc_cat[0]->name ); ?>
                            </span>
                        <?php endif; ?>

                        <h3 class="sc-guide-card__title"><?php the_title(); ?></h3>

                        <p class="sc-guide-card__excerpt">
                            <?php echo esc_html( wp_trim_words( get_the_excerpt(), 18, '…' ) ); ?>
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

            <?php endwhile; wp_reset_postdata(); ?>
        </div>

        <div class="home-guias__cta">
            <a href="<?php echo esc_url( sc_guias_url() ); ?>" class="btn btn--outline">
                Ver todas las guías
                <svg viewBox="0 0 20 20" fill="none" stroke="currentColor"
                     stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                     aria-hidden="true" width="16" height="16">
                    <path d="M4 10h12M11 5l5 5-5 5"/>
                </svg>
            </a>
        </div>

    </div>
</section>
