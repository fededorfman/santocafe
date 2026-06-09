<?php
/**
 * Reusable decorative divider:  ——— ☕ ———
 * Usage: get_template_part( 'template-parts/divider' );
 * Style/visibility is scoped by the parent context (.section-divider,
 * .sc-account, .woocommerce-MyAccount-content).
 */
defined('ABSPATH') || exit;
?>
<div class="sc-divider" aria-hidden="true">
    <span class="sc-divider__line"></span>
    <span class="sc-divider__icon">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round">
            <path d="M4 9h13v5a5 5 0 0 1-5 5H9a5 5 0 0 1-5-5V9z"/>
            <path d="M17 10h2.4a2.4 2.4 0 0 1 0 4.8H17"/>
            <path d="M7.5 5.5c0-1 .8-1.4.8-2.5M11 5.5c0-1 .8-1.4.8-2.5"/>
        </svg>
    </span>
    <span class="sc-divider__line"></span>
</div>
