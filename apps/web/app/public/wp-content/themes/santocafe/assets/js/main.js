/**
 * Santo Café — main.js
 * Mobile drawer, shipping banner, WooCommerce cart badge sync.
 */
(function ($) {
    'use strict';

    // ============================================================
    // Mobile Drawer
    // ============================================================
    var $drawer  = $('.js-mobile-drawer');
    var $overlay = $('.js-drawer-overlay');
    var $toggles = $('.js-menu-toggle');

    function openDrawer() {
        $drawer.addClass('is-open').attr('aria-hidden', 'false');
        $overlay.addClass('is-visible');
        $('body').css('overflow', 'hidden');
        $toggles.attr('aria-expanded', 'true');
    }

    function closeDrawer() {
        $drawer.removeClass('is-open').attr('aria-hidden', 'true');
        $overlay.removeClass('is-visible');
        $('body').css('overflow', '');
        $toggles.attr('aria-expanded', 'false');
    }

    $toggles.on('click', function () {
        $drawer.hasClass('is-open') ? closeDrawer() : openDrawer();
    });

    $overlay.on('click', closeDrawer);

    $(document).on('keydown', function (e) {
        if (e.key === 'Escape' && $drawer.hasClass('is-open')) {
            closeDrawer();
        }
    });

    // ============================================================
    // Shipping Banner — close and remember with sessionStorage
    // ============================================================
    var BANNER_KEY = 'sc_banner_closed';

    if (sessionStorage.getItem(BANNER_KEY)) {
        $('.js-shipping-banner').hide();
    }

    $(document).on('click', '.js-close-banner', function () {
        $(this).closest('.js-shipping-banner').slideUp(200);
        sessionStorage.setItem(BANNER_KEY, '1');
    });

    // Clear banner state when cart changes (new item added → recalculate)
    $(document.body).on('added_to_cart', function () {
        sessionStorage.removeItem(BANNER_KEY);
    });

    // ============================================================
    // Cart Badge — sync via WooCommerce fragments
    // ============================================================
    $(document.body).on('wc_fragments_refreshed wc_fragments_loaded', function () {
        var $badge = $('.cart-icon__badge');
        var count  = parseInt($badge.text(), 10) || 0;
        $badge.toggleClass('is-empty', count === 0);
    });

    // ============================================================
    // Header Spacer — dynamic height (accounts for banner show/hide)
    // ============================================================
    function updateSpacer() {
        var h = $('.site-header').outerHeight(true) || 0;
        $('.header-spacer').css('height', h + 'px');
    }

    updateSpacer();
    $(window).on('resize', updateSpacer);

    // Also update after banner closes
    $(document).on('click', '.js-close-banner', function () {
        setTimeout(updateSpacer, 250);
    });

})(jQuery);
