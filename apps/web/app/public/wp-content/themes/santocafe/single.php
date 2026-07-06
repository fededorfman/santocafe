<?php
/**
 * Single Post — Santo Café
 *
 * Template for guide articles (Guías de preparación, explicadores, etc.).
 * JSON-LD (FAQPage, BreadcrumbList) is emitted by inc/seo.php via wp_head.
 *
 * @package santocafe
 */
defined( 'ABSPATH' ) || exit;

get_header();
?>

<main class="site-main page-main" id="main">
    <div class="container">

        <?php get_template_part( 'template-parts/breadcrumbs' ); ?>

        <?php while ( have_posts() ) : the_post(); ?>

        <article class="sc-article" id="post-<?php the_ID(); ?>" <?php post_class(); ?>>

            <header class="sc-article__header">
                <?php
                $sc_cats = get_the_category();
                if ( $sc_cats ) :
                    $sc_cat = $sc_cats[0];
                ?>
                    <span class="sc-article__kicker">
                        <a href="<?php echo esc_url( get_category_link( $sc_cat->term_id ) ); ?>">
                            <?php echo esc_html( $sc_cat->name ); ?>
                        </a>
                    </span>
                <?php endif; ?>

                <h1 class="sc-article__title"><?php the_title(); ?></h1>

                <div class="sc-article__meta">
                    <time datetime="<?php echo esc_attr( get_the_date( 'Y-m-d' ) ); ?>">
                        <?php echo esc_html( get_the_date( 'j \d\e F \d\e Y' ) ); ?>
                    </time>
                    <?php
                    // Estimated read time (~200 wpm)
                    $sc_word_count = str_word_count( wp_strip_all_tags( get_the_content() ) );
                    $sc_read_min   = max( 1, (int) ceil( $sc_word_count / 200 ) );
                    ?>
                    <span class="sc-article__read-time">
                        <?php echo esc_html( $sc_read_min . ' min de lectura' ); ?>
                    </span>
                </div>
            </header>

            <?php if ( has_post_thumbnail() ) : ?>
                <img
                    class="sc-article__featured-image"
                    src="<?php echo esc_url( get_the_post_thumbnail_url( get_the_ID(), 'large' ) ); ?>"
                    alt="<?php echo esc_attr( get_the_title() ); ?>"
                    loading="eager"
                    width="1200" height="525">
            <?php endif; ?>

            <div class="sc-article__body">
                <?php the_content(); ?>
            </div>

            <?php
            // CTA block — link to store
            ?>
            <div class="sc-article__cta">
                <span class="sc-article__cta-text">
                    Explora nuestros cafés de especialidad
                </span>
                <a href="<?php echo esc_url( home_url( '/#catalogo' ) ); ?>" class="btn btn--primary">
                    Ver cafés
                </a>
            </div>

        </article>

        <?php
        // Related posts (same category, exclude current)
        $sc_related = get_posts( [
            'posts_per_page'      => 3,
            'post__not_in'        => [ get_the_ID() ],
            'category__in'        => wp_get_post_categories( get_the_ID() ),
            'ignore_sticky_posts' => true,
            'orderby'             => 'rand',
        ] );

        if ( ! empty( $sc_related ) ) :
        ?>
        <aside class="sc-article__related">
            <h2 class="sc-article__related-title">Sigue leyendo</h2>
            <div class="sc-article__related-grid">
                <?php foreach ( $sc_related as $sc_rpost ) : ?>
                    <a class="sc-guide-card" href="<?php echo esc_url( get_permalink( $sc_rpost->ID ) ); ?>">
                        <?php if ( has_post_thumbnail( $sc_rpost->ID ) ) : ?>
                            <img class="sc-guide-card__thumb"
                                 src="<?php echo esc_url( get_the_post_thumbnail_url( $sc_rpost->ID, 'medium' ) ); ?>"
                                 alt="<?php echo esc_attr( get_the_title( $sc_rpost->ID ) ); ?>"
                                 loading="lazy" width="400" height="225">
                        <?php else : ?>
                            <div class="sc-guide-card__thumb"></div>
                        <?php endif; ?>
                        <div class="sc-guide-card__body">
                            <span class="sc-guide-card__kicker">Guía</span>
                            <h3 class="sc-guide-card__title">
                                <?php echo esc_html( get_the_title( $sc_rpost->ID ) ); ?>
                            </h3>
                            <p class="sc-guide-card__excerpt">
                                <?php echo esc_html( wp_trim_words( get_the_excerpt( $sc_rpost ), 18, '…' ) ); ?>
                            </p>
                        </div>
                    </a>
                <?php endforeach; wp_reset_postdata(); ?>
            </div>
        </aside>
        <?php endif; ?>

        <?php endwhile; ?>

    </div>
</main>

<?php get_footer(); ?>
