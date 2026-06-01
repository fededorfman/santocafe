<?php
defined('ABSPATH') || exit;
get_header();
?>

<main class="site-main">
    <div class="container" style="padding-top:var(--spacing-xl); padding-bottom:var(--spacing-xl);">
        <?php if ( have_posts() ) : while ( have_posts() ) : the_post(); ?>
            <h1><?php the_title(); ?></h1>
            <?php the_content(); ?>
        <?php endwhile; endif; ?>
    </div>
</main>

<?php get_footer();
