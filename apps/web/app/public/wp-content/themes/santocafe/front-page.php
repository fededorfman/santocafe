<?php
/**
 * Template: Front Page (Homepage)
 * Used automatically when a static front page is set in WP Settings → Reading.
 */
defined('ABSPATH') || exit;

get_header();

echo '<main id="main">';
get_template_part('template-parts/home/section-hero');
get_template_part('template-parts/home/section-features');
get_template_part('template-parts/home/section-catalog');
get_template_part('template-parts/home/section-divider');
get_template_part('template-parts/home/section-nosotros');
get_template_part('template-parts/home/section-guias');
get_template_part('template-parts/home/section-reviews');
get_template_part('template-parts/home/section-contacto');
echo '</main>';

get_footer();
