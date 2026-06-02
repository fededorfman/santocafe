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

    // ============================================================
    // Transparent navbar — adds .is-scrolled to header after 30px scroll
    // ============================================================
    var $siteHeader   = $('.js-site-header');
    var SCROLL_OFFSET = 30;

    function updateNavBg() {
        if ( $(window).scrollTop() > SCROLL_OFFSET ) {
            $siteHeader.addClass('is-scrolled');
        } else {
            $siteHeader.removeClass('is-scrolled');
        }
    }

    updateNavBg(); // run on load (handles browser back-navigation)
    $(window).on('scroll.navbg', updateNavBg);

    // ============================================================
    // Hero scroll indicator
    // ============================================================
    var $scrollIndicator = $('.js-scroll-indicator');
    if ($scrollIndicator.length) {
        setTimeout(function () {
            $scrollIndicator.addClass('is-visible');
        }, 800);

        // Hide on scroll
        $(window).on('scroll.hero', function () {
            if ($(this).scrollTop() > 80) {
                $scrollIndicator.removeClass('is-visible');
                $(window).off('scroll.hero');
            }
        });
    }

    // ============================================================
    // Product Card — format selector (250g / 1kg)
    // ============================================================
    $(document).on('click', '.product-card__format .pill-selector__option', function () {
        var $pill  = $(this);
        var $card  = $pill.closest('.product-card');
        var price  = $pill.data('price');
        var addUrl = $pill.data('add-url');

        $pill.siblings('.pill-selector__option').removeClass('is-selected');
        $pill.addClass('is-selected');

        if (price)  $card.find('.js-card-price').text(price);
        if (addUrl) $card.find('.js-card-add').attr('href', addUrl);
    });

    // ============================================================
    // Product Quick View Modal
    // ============================================================
    var $modal       = $('#product-quick-view');
    var $modalDialog = $modal.find('.js-modal-dialog');
    var lastFocus    = null;

    function openModal() {
        $modal.addClass('is-open').attr('aria-hidden', 'false');
        $('body').css('overflow', 'hidden');
        // Focus first focusable element inside dialog
        setTimeout(function () {
            $modal.find('.product-modal__close').first().trigger('focus');
        }, 300);
    }

    function closeModal() {
        $modal.removeClass('is-open').attr('aria-hidden', 'true');
        $('body').css('overflow', '');
        if (lastFocus) $(lastFocus).trigger('focus');
    }

    // Open on info button click
    $(document).on('click', '.js-product-info', function () {
        var productId = $(this).data('product-id');
        if (!productId) return;

        lastFocus = this;

        // Reset dialog to loading state
        $modalDialog.html(
            '<div class="product-modal__loading">' +
            '<div class="spinner" aria-hidden="true"></div>Cargando…</div>'
        );
        openModal();

        $.ajax({
            url:    SC.ajaxUrl,
            type:   'POST',
            data:   {
                action:     'sc_product_quick_view',
                nonce:      SC.nonce,
                product_id: productId,
            },
            success: function (response) {
                if (response.success && response.data.html) {
                    $modalDialog.html(response.data.html);
                } else {
                    $modalDialog.html(
                        '<div class="product-modal__loading">No se pudo cargar el producto.</div>'
                    );
                }
            },
            error: function () {
                $modalDialog.html(
                    '<div class="product-modal__loading">Error al cargar. Intentá de nuevo.</div>'
                );
            },
        });
    });

    // Close on overlay / close button
    $(document).on('click', '.js-modal-close', closeModal);

    // Close on ESC
    $(document).on('keydown', function (e) {
        if (e.key === 'Escape' && $modal.hasClass('is-open')) closeModal();
    });

    // Modal format selector — same logic, different price/add targets
    $(document).on('click', '.product-modal__format .pill-selector__option', function () {
        var $pill  = $(this);
        var price  = $pill.data('price');
        var addUrl = $pill.data('add-url');

        $pill.siblings('.pill-selector__option').removeClass('is-selected');
        $pill.addClass('is-selected');

        if (price)  $modal.find('.js-modal-price').text(price);
        if (addUrl) $modal.find('.js-modal-add').attr('href', addUrl);
    });

})(jQuery);
