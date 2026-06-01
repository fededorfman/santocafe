<?php
/**
 * Template: Front Page (Homepage)
 * Used automatically when a static front page is set in WP Settings → Reading.
 */
defined('ABSPATH') || exit;

get_header();

get_template_part('template-parts/home/section-hero');
get_template_part('template-parts/home/section-features');
get_template_part('template-parts/home/section-catalog');

get_footer();
