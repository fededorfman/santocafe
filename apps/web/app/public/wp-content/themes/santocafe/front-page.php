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

// Product quick-view modal shell (populated via AJAX)
?>
<div class="product-modal" id="product-quick-view" role="dialog"
     aria-modal="true" aria-label="Vista rápida del producto" aria-hidden="true">
    <div class="product-modal__overlay js-modal-close" aria-hidden="true"></div>
    <div class="product-modal__dialog js-modal-dialog">
        <div class="product-modal__loading">
            <div class="spinner" aria-hidden="true"></div>
            Cargando…
        </div>
    </div>
</div>
<?php

get_footer();
