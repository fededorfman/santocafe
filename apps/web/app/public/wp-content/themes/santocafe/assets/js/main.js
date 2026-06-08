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
    // Transparent navbar — transparent at top on pages with .hero,
    // dark + blur when scrolled. Detected via DOM, not WP body class.
    // ============================================================
    var $siteHeader   = $('.js-site-header');
    var SCROLL_OFFSET = 30;
    var hasHero       = $('.hero').length > 0;

    function updateNavBg() {
        var scrolled = $(window).scrollTop() > SCROLL_OFFSET;

        if ( scrolled || !hasHero ) {
            // Dark + blur
            $siteHeader.addClass('is-scrolled').removeClass('is-transparent');
        } else {
            // Transparent (only on hero pages at the top)
            $siteHeader.addClass('is-transparent').removeClass('is-scrolled');
        }
    }

    updateNavBg();
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
    $(document).on('click', '.product-card__weights .product-card__weight', function () {
        var $btn   = $(this);
        var $card  = $btn.closest('.product-card');
        var price  = $btn.data('price');
        var addUrl = $btn.data('add-url');
        var orig   = $btn.data('original');
        var disc   = parseInt($btn.data('discount'), 10) || 0;

        $btn.siblings('.product-card__weight').removeClass('is-selected');
        $btn.addClass('is-selected');

        if (price)  $card.find('.js-card-price').text(price);
        if (addUrl) $card.find('.js-card-add').attr('href', addUrl);

        var $orig = $card.find('.js-card-original');
        var $disc = $card.find('.js-card-discount');
        if (disc > 0) {
            $orig.text(orig).prop('hidden', false);
            $disc.text('-' + disc + '%').prop('hidden', false);
        } else {
            $orig.prop('hidden', true);
            $disc.prop('hidden', true);
        }
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
        var orig   = $pill.data('original');
        var disc   = parseInt($pill.data('discount'), 10) || 0;

        $pill.siblings('.pill-selector__option').removeClass('is-selected');
        $pill.addClass('is-selected');

        if (price)  $modal.find('.js-modal-price').text(price);
        if (addUrl) $modal.find('.js-modal-add').attr('href', addUrl);

        var $o = $modal.find('.js-modal-original');
        var $d = $modal.find('.js-modal-discount');
        if (disc > 0) {
            $o.text(orig).prop('hidden', false);
            $d.text('-' + disc + '%').prop('hidden', false);
        } else {
            $o.prop('hidden', true);
            $d.prop('hidden', true);
        }
    });

    // ============================================================
    // Product Detail Page
    // ============================================================
    var $detail = $('.product-detail-page');

    if ($detail.length) {

        // Recalculate CTA price (+ struck original) = unit × quantity
        function updateDetailCtaPrice() {
            var $sel = $detail.find('.product-detail__format .is-selected');
            var raw  = parseInt($sel.data('raw-price'), 10) || 0;
            var comp = parseInt($sel.data('original-raw'), 10) || 0;
            var disc = parseInt($sel.data('discount'), 10) || 0;
            var qty  = parseInt($detail.find('.js-qty-input').val(), 10) || 1;

            $detail.find('.js-cta-price').text(formatClp(raw * qty));

            var $o = $detail.find('.js-cta-original');
            if (disc > 0) {
                $o.text(formatClp(comp * qty)).prop('hidden', false);
            } else {
                $o.prop('hidden', true);
            }
        }

        // Format an integer as CLP: 15500 → "$15.500"
        function formatClp(amount) {
            return '$' + String(amount).replace(/\B(?=(\d{3})+(?!\d))/g, '.');
        }

        // --- Format selector (250g / 1kg) ---
        $detail.on('click', '.product-detail__format .pill-selector__option', function () {
            var $pill   = $(this);
            var price   = $pill.data('price');
            var perCup  = $pill.data('per-cup');
            var varId   = $pill.data('variation-id');
            var peso    = $pill.data('peso');

            var orig    = $pill.data('original');
            var disc    = parseInt($pill.data('discount'), 10) || 0;

            $pill.siblings('.pill-selector__option').removeClass('is-selected');
            $pill.addClass('is-selected');

            // Update displayed price + per-cup
            $detail.find('.js-detail-price').text(price);
            $detail.find('.js-per-cup').contents().last().replaceWith(perCup + '/taza');

            // Update discount (original struck + %)
            var $o = $detail.find('.js-detail-original');
            var $d = $detail.find('.js-detail-discount');
            if (disc > 0) {
                $o.text(orig).prop('hidden', false);
                $d.text('-' + disc + '%').prop('hidden', false);
            } else {
                $o.prop('hidden', true);
                $d.prop('hidden', true);
            }

            // Update hidden form inputs
            $detail.find('.js-variation-id').val(varId);
            $detail.find('.js-peso-input').val(peso);

            updateDetailCtaPrice();
        });

        // --- Molienda selector ---
        $detail.on('click', '.molienda-option', function () {
            var $opt = $(this);
            $opt.siblings('.molienda-option').removeClass('is-selected').attr('aria-pressed', 'false');
            $opt.addClass('is-selected').attr('aria-pressed', 'true');
            $detail.find('.js-molienda-input').val($opt.data('value'));
        });

        // --- Quantity picker ---
        $detail.on('click', '.js-qty-btn', function () {
            var action  = $(this).data('action');
            var $input  = $detail.find('.qty-picker__input');
            var current = parseInt($input.val(), 10) || 1;
            var next    = action === 'plus'
                ? Math.min(current + 1, 20)
                : Math.max(current - 1, 1);

            $input.val(next);
            $detail.find('.js-qty-input').val(next);
            updateDetailCtaPrice();
        });

        // --- Tabs ---
        $detail.on('click', '.tab-btn', function () {
            var $btn = $(this);
            var tab  = $btn.data('tab');

            $btn.siblings('.tab-btn').removeClass('is-active').attr('aria-selected', 'false');
            $btn.addClass('is-active').attr('aria-selected', 'true');

            $detail.find('.tab-panel').removeClass('is-active');
            $detail.find('#tab-' + tab).addClass('is-active');
        });
    }

    // ============================================================
    // Cart — unified fragment application (drawer + cart page)
    // Each fragment includes its wrapper → replaceWith. Selectors not
    // present on the current page simply no-op.
    // ============================================================
    function applyFragments(fragments) {
        $.each(fragments, function (selector, html) {
            $(selector).replaceWith(html);
        });
        syncCartDrawerEmpty();
    }

    // Toggle the drawer's empty state (hides the footer buttons when empty).
    function syncCartDrawerEmpty() {
        var $drawer = $('.js-cart-drawer');
        if ( ! $drawer.length ) return;
        $drawer.toggleClass( 'is-empty', $drawer.find('.mini_cart_item').length === 0 );
    }

    // Mutate the cart (qty / molienda / remove) via sc_update_cart.
    // Works for both the cart page (.cart-item) and the drawer (.mini_cart_item).
    function postCart(data, $row) {
        if ($row && $row.length) $row.addClass('is-updating');

        $.post(SC.ajaxUrl, $.extend({ action: 'sc_update_cart', nonce: SC.nonce }, data))
            .done(function (res) {
                if (res && res.success) {
                    sessionStorage.removeItem('sc_banner_closed');
                    applyFragments(res.data.fragments);
                }
            })
            .always(function () {
                if ($row && $row.length) $row.removeClass('is-updating');
            });
    }

    // Quantity ±  (global: cart page + drawer)
    $(document).on('click', '.js-cart-qty', function () {
        var $btn   = $(this);
        var $input = $btn.siblings('.qty-picker__input');
        var cur    = parseInt($input.val(), 10) || 1;
        var qty    = $btn.data('action') === 'plus'
            ? Math.min(cur + 1, 20)
            : Math.max(cur - 1, 1);

        if (qty === cur) return;
        postCart({ cart_action: 'update_qty', cart_key: $btn.data('key'), qty: qty },
                 $btn.closest('.cart-item, .mini_cart_item'));
    });

    // Molienda pill  (global)
    $(document).on('click', '.js-cart-molienda', function () {
        var $b = $(this);
        if ($b.hasClass('is-selected')) return;
        postCart({ cart_action: 'change_molienda', cart_key: $b.data('key'), molienda: $b.data('molienda') },
                 $b.closest('.cart-item, .mini_cart_item'));
    });

    // Remove item  (global)
    $(document).on('click', '.js-cart-remove', function () {
        var $b = $(this);
        postCart({ cart_action: 'remove', cart_key: $b.data('key') },
                 $b.closest('.cart-item, .mini_cart_item'));
    });

    // ============================================================
    // Cart Drawer open/close + AJAX add-to-cart
    // ============================================================
    var $cartDrawer        = $('.js-cart-drawer');
    var $cartDrawerOverlay = $('.js-cart-drawer-overlay');

    function openCartDrawer() {
        $cartDrawer.addClass('is-open').attr('aria-hidden', 'false');
        $cartDrawerOverlay.addClass('is-visible');
        $('body').css('overflow', 'hidden');
    }

    function closeCartDrawer() {
        $cartDrawer.removeClass('is-open').attr('aria-hidden', 'true');
        $cartDrawerOverlay.removeClass('is-visible');
        $('body').css('overflow', '');
    }

    // Open via cart icon / mobile menu link (closes the mobile menu first)
    $(document).on('click', '.js-open-cart-drawer', function (e) {
        e.preventDefault();
        closeDrawer();        // close the mobile nav drawer if open (no-op otherwise)
        openCartDrawer();
    });

    // Close via button / overlay / ESC
    $(document).on('click', '.js-cart-drawer-close, .js-cart-drawer-overlay', closeCartDrawer);
    $(document).on('keydown', function (e) {
        if (e.key === 'Escape' && $cartDrawer.hasClass('is-open')) closeCartDrawer();
    });

    // Initial empty-state sync (hides footer when the cart is empty on load)
    syncCartDrawerEmpty();

    // POST to sc_add_to_cart, refresh fragments and open the drawer
    function addToCart(data, $btn) {
        if ($btn && $btn.length) $btn.addClass('is-loading').prop('disabled', true);

        $.post(SC.ajaxUrl, $.extend({ action: 'sc_add_to_cart', nonce: SC.nonce }, data))
            .done(function (res) {
                if (res && res.success) {
                    sessionStorage.removeItem('sc_banner_closed');
                    applyFragments(res.data.fragments);
                    openCartDrawer();
                }
            })
            .always(function () {
                if ($btn && $btn.length) $btn.removeClass('is-loading').prop('disabled', false);
            });
    }

    // --- Card "Añadir" ---
    $(document).on('click', '.js-card-add', function (e) {
        e.preventDefault();
        var $card    = $(this).closest('.product-card');
        var $weights = $card.find('.product-card__weights');
        var $sel     = $weights.find('.product-card__weight.is-selected');

        addToCart({
            product_id:   $weights.data('product-id'),
            variation_id: $sel.data('variation-id'),
            peso:         $sel.data('peso'),
            molienda:     'Grano',
            quantity:     1
        }, $(this));
    });

    // --- Modal "Añadir" ---
    $(document).on('click', '.js-modal-add', function (e) {
        e.preventDefault();
        var $format = $('.product-modal__format');
        var $pill   = $format.find('.pill-selector__option.is-selected');

        addToCart({
            product_id:   $format.data('product-id'),
            variation_id: $pill.data('variation-id'),
            peso:         $pill.data('peso'),
            molienda:     'Grano',
            quantity:     1
        }, $(this));
    });

    // --- Product detail form ---
    $(document).on('submit', '.product-detail__cart-form', function (e) {
        e.preventDefault();
        var $form = $(this);

        addToCart({
            product_id:   $form.find('input[name="add-to-cart"]').val(),
            variation_id: $form.find('.js-variation-id').val(),
            peso:         $form.find('.js-peso-input').val(),
            molienda:     $form.find('.js-molienda-input').val(),
            quantity:     $form.find('.js-qty-input').val()
        }, $form.find('.product-detail__cta'));
    });

    // ============================================================
    // FormValidate — reusable per-field form validation.
    //
    // Opt in by adding `js-validate` to any <form>. On submit it marks
    // empty-required and invalid fields (red border + inline message) and
    // blocks submission, focusing the first offender. Errors clear as the
    // user fixes each field.
    //
    // Rules are read from the field, so it works with both WooCommerce
    // fields (.validate-required / .validate-email / .validate-phone on the
    // .form-row) and plain HTML inputs ([required], type=email/tel).
    //
    // To use elsewhere: add class `js-validate` to the form. Done.
    // ============================================================
    var FormValidate = {
        ROW: '.form-row, .woocommerce-form-row, .sc-form-row, .form-field',
        EMAIL_RE: /^[^\s@]+@[^\s@]+\.[^\s@]+$/,
        PHONE_RE: /^[\d\s\-+().]{6,}$/,

        init: function () {
            $(document).on('submit', 'form.js-validate', this.onSubmit.bind(this));
            // Clear a field's error as soon as it becomes valid again.
            $(document).on('input change blur', 'form.js-validate .sc-field--error :input', function () {
                FormValidate.validateRow($(this).closest(FormValidate.ROW));
            });
        },

        onSubmit: function (e) {
            var $form = $(e.currentTarget);
            var $first = null;

            $form.find(this.ROW).each(function () {
                var $row = $(this);
                if (!FormValidate.validateRow($row) && !$first) {
                    $first = $row.find(':input').filter(':visible').first();
                }
            });

            if ($first && $first.length) {
                e.preventDefault();
                $first.trigger('focus');
            }
        },

        // Returns true when the row is valid (or has no field to check).
        validateRow: function ($row) {
            var $input = $row.find('input, select, textarea').filter(':not([type=hidden])').first();
            if (!$input.length) {
                return true;
            }

            var val      = $.trim(($input.val() || '').toString());
            var type     = ($input.attr('type') || '').toLowerCase();
            var required = $row.hasClass('validate-required') || $input.prop('required');
            var error    = '';

            if (required && val === '') {
                error = 'Este campo es obligatorio.';
            } else if (val !== '' && ($row.hasClass('validate-email') || type === 'email') && !this.EMAIL_RE.test(val)) {
                error = 'Ingresá un email válido.';
            } else if (val !== '' && ($row.hasClass('validate-phone') || type === 'tel') && !this.PHONE_RE.test(val)) {
                error = 'Ingresá un teléfono válido.';
            }

            if (error) {
                this.mark($row, error);
                return false;
            }
            this.clear($row);
            return true;
        },

        mark: function ($row, msg) {
            $row.addClass('sc-field--error');
            var $msg = $row.find('.sc-field__error');
            if (!$msg.length) {
                $msg = $('<span class="sc-field__error" role="alert"></span>');
                $row.append($msg);
            }
            $msg.text(msg);
        },

        clear: function ($row) {
            $row.removeClass('sc-field--error').find('.sc-field__error').remove();
        }
    };

    FormValidate.init();

    // ============================================================
    // PasswordForm — inline validation + AJAX change (no page reload).
    // Reuses FormValidate's per-field marking. Validates current/new/confirm
    // client-side, then posts to sc_change_password; server errors (e.g. wrong
    // current password) come back as per-field messages without refreshing.
    // ============================================================
    var PasswordForm = {
        PW_RE: /^(?=.*[A-Za-z])(?=.*[0-9]).{8,}$/,

        init: function () {
            $(document).on('submit', 'form.js-password-form', this.onSubmit);
            // Clear a field's error as soon as the user edits it.
            $(document).on('input', 'form.js-password-form .sc-field--error :input', function () {
                FormValidate.clear($(this).closest('.form-row'));
            });
        },

        onSubmit: function (e) {
            e.preventDefault();
            var $form = $(e.currentTarget);
            var $cur  = $form.find('#password_current');
            var $p1   = $form.find('#password_1');
            var $p2   = $form.find('#password_2');
            var cur = $cur.val(), p1 = $p1.val(), p2 = $p2.val();
            var $feedback = $form.find('.js-password-feedback');

            $feedback.attr('hidden', true).removeClass('is-error is-success').text('');
            [ $cur, $p1, $p2 ].forEach(function ($f) { FormValidate.clear($f.closest('.form-row')); });

            var $first = null;
            var fail = function ($input, msg) {
                FormValidate.mark($input.closest('.form-row'), msg);
                if (!$first) { $first = $input; }
            };

            if (!cur) { fail($cur, 'Este campo es obligatorio.'); }
            if (!p1) { fail($p1, 'Este campo es obligatorio.'); }
            else if (!PasswordForm.PW_RE.test(p1)) { fail($p1, 'Mínimo 8 caracteres, con al menos una letra y un número.'); }
            if (!p2) { fail($p2, 'Este campo es obligatorio.'); }
            else if (p1 && p1 !== p2) { fail($p2, 'Las contraseñas no coinciden.'); }

            if ($first) { $first.trigger('focus'); return; }

            var $btn = $form.find('button[type="submit"]');
            $btn.prop('disabled', true);

            $.post(SC.ajaxUrl, {
                action:  'sc_change_password',
                nonce:   SC.nonce,
                current: cur,
                'new':   p1
            }).done(function (res) {
                if (res && res.success) {
                    $cur.val(''); $p1.val(''); $p2.val('');
                    $feedback.removeClass('is-error').addClass('is-success')
                        .text((res.data && res.data.message) || 'Contraseña actualizada.').removeAttr('hidden');
                } else {
                    var d = (res && res.data) || {};
                    var $field = d.field ? $form.find('#' + d.field) : $cur;
                    FormValidate.mark($field.closest('.form-row'), d.message || 'No se pudo cambiar la contraseña.');
                    $field.trigger('focus');
                }
            }).fail(function () {
                $feedback.removeClass('is-success').addClass('is-error')
                    .text('Ocurrió un error. Intentá de nuevo.').removeAttr('hidden');
            }).always(function () {
                $btn.prop('disabled', false);
            });
        }
    };

    PasswordForm.init();

    // ============================================================
    // Contact form — validate (via FormValidate) then, on success,
    // replace the form with a thank-you message (no page reload).
    // ============================================================
    $(document).on('submit', 'form.js-contact-form', function (e) {
        // FormValidate (js-validate) already ran and marked invalid fields.
        e.preventDefault();
        var $form = $(this);

        // If FormValidate flagged any field, stop here (errors are shown).
        if ($form.find('.sc-field--error').length) {
            return;
        }

        // Valid → swap the form for the success message.
        $form.attr('hidden', true);
        $form.siblings('.js-contact-success').removeAttr('hidden');
    });

})(jQuery);
