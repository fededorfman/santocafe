/**
 * Santo Café — main.js
 * Mobile drawer, shipping banner, WooCommerce cart badge sync.
 */
(function ($) {
    'use strict';

    // Autoplay (carousels/galleries) stays off for users who prefer reduced motion.
    var reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)');

    // ============================================================
    // Tope global de longitud de inputs: ningún campo acepta texto
    // infinito. Si un campo no declara su propio maxlength, le ponemos
    // un tope sano (los <textarea> algo más holgado). Es defensa
    // adicional — el server igual valida sus propios límites.
    // ============================================================
    $(function () {
        var SKIP = {
            hidden: 1, checkbox: 1, radio: 1, file: 1, submit: 1, button: 1,
            image: 1, reset: 1, range: 1, number: 1, date: 1, 'datetime-local': 1,
            month: 1, week: 1, time: 1, color: 1
        };
        $('input, textarea').each(function () {
            if (this.tagName === 'INPUT' && SKIP[(this.type || '').toLowerCase()]) { return; }
            if (this.maxLength && this.maxLength > 0) { return; } // ya tiene tope propio
            this.maxLength = this.tagName === 'TEXTAREA' ? 5000 : 200;
        });
    });

    // ============================================================
    // Anti-drag: evita el "fantasma" al click-y-arrastrar imágenes en todo el
    // sitio (cobertura cross-browser, incl. Firefox). De paso cancela el drag
    // nativo de los links del navbar, que cambiaba el cursor (flicker).
    // ============================================================
    document.addEventListener('dragstart', function (e) {
        if (e.target.closest('img, .site-nav__action-btn, .site-nav__logo')) {
            e.preventDefault();
        }
    });

    // ============================================================
    // Aviso de ajuste de stock: PHP lo setea en SC.stockNotice cuando recortó el
    // carrito por falta de stock. Se muestra como banner fijo arriba del
    // formulario (checkout por bloques o clásico) y se queda hasta que el usuario
    // lo cierre. Independiente del sistema de notices de WooCommerce.
    // ============================================================
    if (window.SC && SC.stockNotice) {
        (function (msg) {
            var isCheckout = $('.wp-block-woocommerce-checkout').length > 0 ||
                             document.body.classList.contains('woocommerce-checkout');

            if (isCheckout) {
                // Banner fijo arriba del formulario (solo en checkout/pagar pedido).
                var $banner = $(
                    '<div class="sc-stock-banner" role="alert">' +
                        '<svg class="sc-stock-banner__icon" viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' +
                            '<circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/>' +
                        '</svg>' +
                        '<span class="sc-stock-banner__text"></span>' +
                        '<button type="button" class="sc-stock-banner__close" aria-label="Cerrar">×</button>' +
                    '</div>'
                );
                $banner.find('.sc-stock-banner__text').text(msg);

                var $target = $('.wp-block-woocommerce-checkout').first();
                if ($target.length) {
                    $banner.insertBefore($target);
                } else {
                    $target = $('.sc-pay, .woocommerce').first();
                    if ($target.length) {
                        $banner.insertBefore($target);
                    } else {
                        $('.page-main .container, main').first().prepend($banner);
                    }
                }

                $banner.on('click', '.sc-stock-banner__close', function () { $banner.remove(); });
            } else {
                // Toast flotante en el resto de las pantallas (no se espera el
                // aviso fuera del checkout; el toast es menos invasivo).
                var $t = $('<div class="sc-toast" role="status" aria-live="polite"></div>').text(msg);
                var $x = $('<button type="button" class="sc-toast__close" aria-label="Cerrar">×</button>');
                $t.append($x).appendTo('body');
                $t[0].offsetWidth; // reflow para disparar la transición de entrada
                $t.addClass('is-visible');
                var dismiss = function () { $t.removeClass('is-visible'); setTimeout(function () { $t.remove(); }, 300); };
                $x.on('click', dismiss);
                setTimeout(dismiss, 9000);
            }
        })(SC.stockNotice);
    }

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

    // Close the drawer when a menu option is chosen (anchor jump or page nav).
    $drawer.on('click', '.mobile-drawer__nav a', function () {
        closeDrawer();
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

    // Refleja el stock del peso seleccionado en el botón "Añadir".
    function setCardAddState($card) {
        var $sel    = $card.find('.product-card__weight.is-selected');
        var inStock = !$sel.length || String($sel.data('instock')) !== '0';
        var $add    = $card.find('.js-card-add');
        $add.toggleClass('is-disabled', !inStock).attr('aria-disabled', inStock ? null : 'true');
        $add.find('.js-card-add-label').text(inStock ? 'Añadir' : 'Sin stock');
    }

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

        setCardAddState($card);
    });

    // Estado inicial de cada tarjeta (peso por defecto).
    $('.product-card').each(function () { setCardAddState($(this)); });

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
            setDetailCtaState();
        });

        // Refleja el stock del formato seleccionado en el botón "Agregar".
        function setDetailCtaState() {
            var $sel    = $detail.find('.product-detail__format .is-selected');
            var inStock = !$sel.length || String($sel.data('instock')) !== '0';
            var $cta    = $detail.find('.product-detail__cta');
            $cta.toggleClass('is-disabled', !inStock).prop('disabled', !inStock);
            $cta.find('.js-cta-label').text(inStock ? 'Agregar al carrito —' : 'Sin stock');
            $cta.find('.js-cta-price').prop('hidden', !inStock);
            if (!inStock) $cta.find('.js-cta-original').prop('hidden', true);
        }
        setDetailCtaState();

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
    // scPost — POST a admin-ajax con reintento ante nonce vencido.
    //
    // Si la página viene de la caché (LiteSpeed en prod), el SC.nonce embebido
    // puede estar desactualizado respecto de la sesión real → check_ajax_referer
    // falla y el server responde -1 con HTTP 403. Cuando eso pasa, pedimos un
    // nonce fresco al endpoint sin caché (sc_refresh_nonce), lo guardamos en
    // SC.nonce y reintentamos UNA sola vez. Devuelve una promesa compatible con
    // .done()/.fail()/.always() (misma firma que $.post) para los call sites.
    // ============================================================
    function scPost(data) {
        var dfd = $.Deferred();

        function fire(isRetry) {
            $.post(SC.ajaxUrl, $.extend({ nonce: SC.nonce }, data))
                .done(function (res, textStatus, jqXHR) {
                    dfd.resolve(res, textStatus, jqXHR);
                })
                .fail(function (jqXHR, textStatus, errorThrown) {
                    // 403 = nonce inválido. Refrescamos y reintentamos una vez.
                    if (!isRetry && jqXHR && jqXHR.status === 403) {
                        $.post(SC.ajaxUrl, { action: 'sc_refresh_nonce' })
                            .done(function (r) {
                                if (r && r.success && r.data && r.data.nonce) {
                                    SC.nonce = r.data.nonce;
                                    fire(true);
                                } else {
                                    dfd.reject(jqXHR, textStatus, errorThrown);
                                }
                            })
                            .fail(function () {
                                dfd.reject(jqXHR, textStatus, errorThrown);
                            });
                        return;
                    }
                    dfd.reject(jqXHR, textStatus, errorThrown);
                });
        }

        fire(false);
        return dfd.promise();
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

    // Page-load cart refresh — replaces the dequeued wc-cart-fragments for the
    // initial load. wc-cart-fragments was removed because its sessionStorage cache
    // overwrote our AJAX updates; this version has no sessionStorage side effects.
    // Only fires when woocommerce_items_in_cart cookie is present (cart has items),
    // so visitors with empty carts pay zero cost.
    $(function () {
        function hasCookie(name) {
            return document.cookie.split(';').some(function (c) {
                return c.trim().indexOf(name + '=') === 0;
            });
        }
        if (hasCookie('woocommerce_items_in_cart')) {
            $.getJSON('/?wc-ajax=get_refreshed_fragments')
                .done(function (data) {
                    if (data && data.fragments) {
                        applyFragments(data.fragments);
                        $(document.body).trigger('wc_fragments_loaded');
                    }
                });
        }
    });

    // Mutate the cart (qty / molienda / remove) via sc_update_cart.
    // Works for both the cart page (.cart-item) and the drawer (.mini_cart_item).
    var scCartModified = false;

    function postCart(data, $row) {
        if ($row && $row.length) $row.addClass('is-updating');

        scPost($.extend({ action: 'sc_update_cart' }, data))
            .done(function (res) {
                if (res && res.success) {
                    applyFragments(res.data.fragments);
                    scCartModified = true;
                    if (res.data.notice) window.alert(res.data.notice);
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
        if (scCartModified && $('body').hasClass('woocommerce-checkout')) {
            window.location.reload();
        }
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

    // Abrir el drawer al volver de "Repetir pedido" (?sc_opencart=1).
    if (/[?&]sc_opencart=1(?:&|$)/.test(window.location.search)) {
        openCartDrawer();
    }

    // POST to sc_add_to_cart, refresh fragments and open the drawer
    function addToCart(data, $btn) {
        if ($btn && $btn.length) $btn.addClass('is-loading').prop('disabled', true);

        scPost($.extend({ action: 'sc_add_to_cart' }, data))
            .done(function (res) {
                if (res && res.success) {
                    applyFragments(res.data.fragments);
                    openCartDrawer();
                } else if (res && res.data && res.data.message) {
                    window.alert(res.data.message);
                }
            })
            .always(function () {
                if ($btn && $btn.length) $btn.removeClass('is-loading').prop('disabled', false);
            });
    }

    // --- Card "Añadir" ---
    $(document).on('click', '.js-card-add', function (e) {
        e.preventDefault();
        if ($(this).hasClass('is-disabled')) return; // sin stock
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

    // --- Product detail form ---
    $(document).on('submit', '.product-detail__cart-form', function (e) {
        e.preventDefault();
        var $form = $(this);
        if ($form.find('.product-detail__cta').hasClass('is-disabled')) return; // sin stock

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
        PW_RE: /^(?=.*[A-Za-z])(?=.*[0-9]).{8,}$/, // 8+ con al menos una letra y un número

        init: function () {
            $(document).on('submit', 'form.js-validate', this.onSubmit.bind(this));
            // Clear a field's error as soon as it becomes valid again.
            $(document).on('input change blur', 'form.js-validate .sc-field--error :input', function () {
                FormValidate.validateRow($(this).closest(FormValidate.ROW));
            });
            // Validación en vivo de contraseñas (requisitos + coincidencia) desde
            // la primera tecla, no solo después de un error ya mostrado.
            $(document).on('input blur',
                'form.js-validate .validate-password :input, form.js-validate .validate-password-match :input',
                function (e) {
                    var $field = $(this);
                    var $row   = $field.closest(FormValidate.ROW);
                    // Mientras el campo está vacío no molestamos con "obligatorio"
                    // al tipear; eso queda para blur / submit.
                    if (e.type === 'input' && $.trim(($field.val() || '').toString()) === '') {
                        FormValidate.clear($row);
                    } else {
                        FormValidate.validateRow($row);
                    }
                    // Si este es el campo de origen, re-evaluamos el "repetir" ligado.
                    var id = $field.attr('id');
                    if (id) {
                        $('form.js-validate .validate-password-match').each(function () {
                            if ($(this).data('pwMatch') !== '#' + id) { return; }
                            var $confirm = $(this).find(':input').first();
                            if ($.trim(($confirm.val() || '').toString()) !== '') {
                                FormValidate.validateRow($(this));
                            }
                        });
                    }
                }
            );
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
                error = 'Ingresa un email válido.';
            } else if (val !== '' && ($row.hasClass('validate-phone') || type === 'tel') && !this.PHONE_RE.test(val)) {
                error = 'Ingresa un teléfono válido.';
            } else if (val !== '' && $row.data('minlength') && val.length < parseInt($row.data('minlength'), 10)) {
                error = 'Escribe al menos ' + parseInt($row.data('minlength'), 10) + ' caracteres.';
            } else if (val !== '' && $row.hasClass('validate-password') && !this.PW_RE.test(val)) {
                error = 'Mínimo 8 caracteres, con al menos una letra y un número.';
            } else if (val !== '' && $row.hasClass('validate-password-match')) {
                // La fila apunta al campo de origen vía data-pw-match (selector).
                var matchSel = $row.data('pwMatch');
                var matchVal = matchSel ? $.trim(($(matchSel).val() || '').toString()) : '';
                if (val !== matchVal) {
                    error = 'Las contraseñas no coinciden.';
                }
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

            scPost({
                action:  'sc_change_password',
                current: cur,
                'new':   p1
            }).done(function (res) {
                if (res && res.success) {
                    // Cambiar la contraseña rota la sesión y el nonce anterior:
                    // el server devuelve uno fresco para que un segundo cambio
                    // (u otra acción AJAX) no falle con 403. También refrescamos
                    // el link de "Cerrar sesión", cuyo nonce quedó obsoleto.
                    if (res.data && res.data.nonce) { SC.nonce = res.data.nonce; }
                    if (res.data && res.data.logout_url) {
                        $('.js-logout-link').attr('href', res.data.logout_url);
                    }
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
                    .text('Ocurrió un error. Intenta de nuevo.').removeAttr('hidden');
            }).always(function () {
                $btn.prop('disabled', false);
            });
        }
    };

    PasswordForm.init();

    // ============================================================
    // Contact form — validate (via FormValidate), then POST via AJAX.
    // Envía 2 emails (admin + confirmación). En éxito, swap por el
    // mensaje de gracias; en error, toast flotante y se puede reintentar.
    // ============================================================
    function scContactToast(msg) {
        var $t = $('<div class="sc-toast sc-toast--error" role="alert" aria-live="assertive"></div>').text(msg);
        var $x = $('<button type="button" class="sc-toast__close" aria-label="Cerrar">×</button>');
        $t.append($x).appendTo('body');
        $t[0].offsetWidth; // reflow para la transición de entrada
        $t.addClass('is-visible');
        var dismiss = function () { $t.removeClass('is-visible'); setTimeout(function () { $t.remove(); }, 300); };
        $x.on('click', dismiss);
        setTimeout(dismiss, 8000);
    }

    $(document).on('submit', 'form.js-contact-form', function (e) {
        // FormValidate (js-validate) already ran and marked invalid fields.
        e.preventDefault();
        var $form = $(this);

        // If FormValidate flagged any field, stop here (errors are shown).
        if ($form.find('.sc-field--error').length) {
            return;
        }

        var $btn = $form.find('button[type="submit"]');
        if ($btn.prop('disabled')) { return; }
        $btn.prop('disabled', true).addClass('is-loading');

        scPost({
            action:     'sc_contact',
            nombre:     $form.find('[name="nombre"]').val(),
            email:      $form.find('[name="email"]').val(),
            mensaje:    $form.find('[name="mensaje"]').val(),
            sc_website: $form.find('[name="sc_website"]').val()
        }).done(function (res) {
            if (res && res.success) {
                $form.attr('hidden', true);
                $form.siblings('.js-contact-success').removeAttr('hidden');
            } else {
                var d   = (res && res.data) || {};
                var msg = d.message || 'No pudimos enviar tu mensaje. Intenta de nuevo.';
                // Si el server indica qué campos fallaron, los marcamos inline.
                if (Array.isArray(d.fields) && d.fields.length) {
                    var $firstBad = null;
                    d.fields.forEach(function (nm) {
                        var $row = $form.find('[name="' + nm + '"]').closest('.form-field, .form-row');
                        if ($row.length) {
                            FormValidate.mark($row, msg);
                            if (!$firstBad) { $firstBad = $row.find(':input').first(); }
                        }
                    });
                    if ($firstBad && $firstBad.length) { $firstBad.trigger('focus'); }
                } else {
                    scContactToast(msg);
                }
                $btn.prop('disabled', false).removeClass('is-loading');
            }
        }).fail(function () {
            scContactToast('No pudimos enviar tu mensaje. Revisa tu conexión e intenta de nuevo.');
            $btn.prop('disabled', false).removeClass('is-loading');
        });
    });

    // ============================================================
    // Disable zoom (desktop): trackpad pinch and Ctrl/Cmd + wheel both
    // fire a wheel event with ctrlKey = true. Safari pinch fires gesture
    // events. Mobile pinch is handled by the viewport meta + touch-action.
    // ============================================================
    document.addEventListener('wheel', function (e) {
        if (e.ctrlKey || e.metaKey) {
            e.preventDefault();
        }
    }, { passive: false });

    ['gesturestart', 'gesturechange', 'gestureend'].forEach(function (type) {
        document.addEventListener(type, function (e) {
            e.preventDefault();
        });
    });

    // ============================================================
    // Anchor-scroll offset: keep --sc-header-offset in sync with the real
    // fixed-header height so #anchor jumps land below it. The header height
    // changes by breakpoint and when the shipping banner shows/hides.
    // ============================================================
    (function syncHeaderOffset() {
        var header = document.querySelector('.js-site-header');
        if (!header) return;

        function update() {
            document.documentElement.style.setProperty(
                '--sc-header-offset', header.offsetHeight + 'px'
            );
        }

        update();
        window.addEventListener('load', update);
        window.addEventListener('resize', update);
        window.addEventListener('orientationchange', update);
        // Cart fragments re-rendered → re-measure.
        $(document.body).on('wc_fragments_refreshed wc_fragments_loaded added_to_cart', update);
    })();

    // ============================================================
    // Features carousel (mobile ≤600px)
    // The 4 benefit panels become a swipeable, looping horizontal
    // gallery with dot pagination. On larger screens it stays a grid
    // and this code stays dormant.
    // ============================================================
    (function initFeaturesCarousel() {
        var track    = document.querySelector('.js-features-carousel');
        var dotsWrap = document.querySelector('.js-features-dots');
        if (!track || !dotsWrap) return;

        var cards     = Array.prototype.slice.call(track.children);
        if (cards.length < 2) return;

        var mq        = window.matchMedia('(max-width: 800px)');
        var dots      = [];
        var current   = 0;
        var timer     = null;
        var scrollRAF = null;
        var AUTOPLAY  = 4500;

        function scrollToCard(i) {
            var card = cards[i];
            if (!card) return;
            var left = card.offsetLeft - (track.clientWidth - card.clientWidth) / 2;
            track.scrollTo({ left: left, behavior: 'smooth' });
        }

        function setActive(i) {
            current = i;
            for (var d = 0; d < dots.length; d++) {
                dots[d].classList.toggle('is-active', d === i);
                dots[d].setAttribute('aria-selected', d === i ? 'true' : 'false');
            }
        }

        function nearestCard() {
            var center  = track.scrollLeft + track.clientWidth / 2;
            var nearest = 0, min = Infinity;
            for (var i = 0; i < cards.length; i++) {
                var c = cards[i].offsetLeft + cards[i].clientWidth / 2;
                var dist = Math.abs(c - center);
                if (dist < min) { min = dist; nearest = i; }
            }
            return nearest;
        }

        function onScroll() {
            if (scrollRAF) cancelAnimationFrame(scrollRAF);
            scrollRAF = requestAnimationFrame(function () {
                setActive(nearestCard());
            });
        }

        function startAutoplay() {
            stopAutoplay();
            if (!mq.matches || reducedMotion.matches) return;
            timer = setInterval(function () {
                scrollToCard((current + 1) % cards.length); // wraps → infinite
            }, AUTOPLAY);
        }
        function stopAutoplay() {
            if (timer) { clearInterval(timer); timer = null; }
        }

        function buildDots() {
            dotsWrap.innerHTML = '';
            dots = cards.map(function (_, i) {
                var b = document.createElement('button');
                b.type = 'button';
                b.className = 'features__dot';
                b.setAttribute('role', 'tab');
                b.setAttribute('aria-label', 'Ir al beneficio ' + (i + 1));
                b.addEventListener('click', function () {
                    stopAutoplay();
                    scrollToCard(i);
                    startAutoplay();
                });
                dotsWrap.appendChild(b);
                return b;
            });
            setActive(0);
        }

        var bound = false;
        function enable() {
            buildDots();
            if (!bound) {
                track.addEventListener('scroll', onScroll, { passive: true });
                track.addEventListener('touchstart', stopAutoplay, { passive: true });
                track.addEventListener('touchend', startAutoplay, { passive: true });
                bound = true;
            }
            track.scrollTo({ left: 0 });
            setActive(0);
            startAutoplay();
        }
        function disable() {
            stopAutoplay();
            dotsWrap.innerHTML = '';
            dots = [];
        }

        function apply() { mq.matches ? enable() : disable(); }

        apply();
        if (mq.addEventListener) {
            mq.addEventListener('change', apply);
        } else if (mq.addListener) {
            mq.addListener(apply); // Safari < 14
        }
    })();

    // ============================================================
    // Galería de imágenes (pdesc-gallery) — descripción de producto
    // y "Nuestra historia". Soporta múltiples instancias por página.
    // Auto-advance, drag/swipe, navegación por miniaturas.
    // ============================================================
    (function initPdescGalleries() {
        Array.prototype.forEach.call(
            document.querySelectorAll('[data-pdesc-gallery]'),
            function (gallery) {

            var track  = gallery.querySelector('[data-gallery-track]');
            var slides = Array.from(gallery.querySelectorAll('.pdesc-gallery__slide'));
            var thumbs = Array.from(gallery.querySelectorAll('[data-gallery-thumb]'));
            if (!track || slides.length < 2) return; // una sola imagen — sin carrusel

            var current  = 0;
            var timer    = null;
            var INTERVAL = 4500; // ms entre avances automáticos

            function goTo(index) {
                slides[current].classList.remove('is-active');
                slides[current].setAttribute('aria-hidden', 'true');
                if (thumbs[current]) {
                    thumbs[current].classList.remove('is-active');
                    thumbs[current].setAttribute('aria-selected', 'false');
                }

                current = (index + slides.length) % slides.length;

                slides[current].classList.add('is-active');
                slides[current].setAttribute('aria-hidden', 'false');
                if (thumbs[current]) {
                    thumbs[current].classList.add('is-active');
                    thumbs[current].setAttribute('aria-selected', 'true');
                }
            }

            function startAuto() {
                stopAuto();
                if (reducedMotion.matches) return;
                timer = setInterval(function () { goTo(current + 1); }, INTERVAL);
            }
            function stopAuto() {
                if (timer) { clearInterval(timer); timer = null; }
            }
            function resetAuto() { stopAuto(); startAuto(); }

            // Thumbnail clicks
            thumbs.forEach(function (btn) {
                btn.addEventListener('click', function () {
                    goTo(parseInt(btn.getAttribute('data-gallery-thumb'), 10));
                    resetAuto();
                });
            });

            // Drag / swipe (mouse + touch)
            var dragStartX = null;
            var THRESHOLD  = 50; // px

            function onDragStart(x) { dragStartX = x; }
            function onDragEnd(x) {
                if (dragStartX === null) return;
                var delta = x - dragStartX;
                dragStartX = null;
                if (Math.abs(delta) < THRESHOLD) return;
                goTo(delta < 0 ? current + 1 : current - 1);
                resetAuto();
            }

            // Touch
            track.addEventListener('touchstart', function (e) {
                onDragStart(e.touches[0].clientX);
                stopAuto();
            }, { passive: true });
            track.addEventListener('touchend', function (e) {
                onDragEnd(e.changedTouches[0].clientX);
                startAuto();
            }, { passive: true });

            // Mouse drag — preventDefault corta el image-drag nativo del browser
            // antes de que secuestre el puntero y podamos medir el delta.
            track.addEventListener('mousedown', function (e) {
                e.preventDefault();
                onDragStart(e.clientX);
                stopAuto();
            });

            // Por las dudas: cancelar cualquier dragstart que burbujee del <img>.
            track.addEventListener('dragstart', function (e) {
                e.preventDefault();
            });
            document.addEventListener('mouseup', function (e) {
                if (dragStartX === null) return;
                onDragEnd(e.clientX);
                startAuto();
            });

            // Pausa al pasar el mouse
            gallery.addEventListener('mouseenter', stopAuto);
            gallery.addEventListener('mouseleave', startAuto);

            startAuto();
        });
    })();

    // ============================================================
    // Cancelar pedido: pedir confirmación antes de seguir el link (cancela el
    // pedido y no se puede deshacer). Apuntamos a la URL de cancelación
    // (cancel_order=true) para cubrir la lista de pedidos, el detalle y gracias.
    // ============================================================
    $(document).on('click', 'a[href*="cancel_order=true"]', function (e) {
        if (!window.confirm('¿Seguro que quieres cancelar este pedido? No se puede deshacer.')) {
            e.preventDefault();
        }
    });

    // ============================================================
    // "Usar dirección de envío": copia la dirección de envío sobre la de
    // facturación en la página de Direcciones. Pide confirmación si ya había
    // datos de facturación. El botón llega deshabilitado si no hay envío cargado.
    // ============================================================
    $(document).on('click', '.js-copy-shipping', function () {
        var $btn = $(this);
        if ($btn.prop('disabled')) return;

        if (String($btn.data('hasBilling')) === '1' &&
            !window.confirm('Ya tienes una dirección de facturación cargada. ¿Quieres reemplazarla por la dirección de envío?')) {
            return;
        }

        var original = $btn.text();
        $btn.prop('disabled', true).text('Copiando…');

        scPost({ action: 'sc_copy_shipping_to_billing' })
            .done(function (res) {
                if (res && res.success) {
                    window.location.reload();
                } else {
                    window.alert((res && res.data && res.data.message) || 'No se pudo copiar la dirección.');
                    $btn.prop('disabled', false).text(original);
                }
            })
            .fail(function () {
                window.alert('Hubo un error. Intenta de nuevo.');
                $btn.prop('disabled', false).text(original);
            });
    });

    // ── Password: eye toggle ──────────────────────────────────────────────
    $(document).on('click', '.sc-pw-toggle', function () {
        var $btn   = $(this);
        var $input = $btn.closest('.sc-pw-field').find('input');
        var hide   = $input.attr('type') === 'text';
        $input.attr('type', hide ? 'password' : 'text');
        $btn.toggleClass('is-visible', !hide);
        $btn.attr('aria-label', hide ? 'Ver contraseña' : 'Ocultar contraseña');
    });

    // ── Password: generate ───────────────────────────────────────────────
    function scFillPassword($input, pw) {
        // Change type before setting value — some browsers discard the value on type change.
        $input.attr('type', 'text').val(pw);
        $input[0].dispatchEvent(new Event('input',  { bubbles: true }));
        $input[0].dispatchEvent(new Event('change', { bubbles: true }));
        $input.closest('.sc-pw-field').find('.sc-pw-toggle')
              .addClass('is-visible').attr('aria-label', 'Ocultar contraseña');
    }

    $(document).on('click', '.sc-pw-generate', function () {
        var upper  = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        var lower  = 'abcdefghijklmnopqrstuvwxyz';
        var digits = '0123456789';
        var all    = upper + lower + digits;
        var pw     = [
            upper [Math.floor(Math.random() * upper.length)],
            lower [Math.floor(Math.random() * lower.length)],
            digits[Math.floor(Math.random() * digits.length)]
        ];
        for (var i = 3; i < 8; i++) {
            pw.push(all[Math.floor(Math.random() * all.length)]);
        }
        pw = pw.sort(function () { return Math.random() - 0.5; }).join('');

        scFillPassword($(this).closest('.sc-pw-field').find('input'), pw);

        var match = $(this).data('fill-match');
        if (match) {
            scFillPassword($(match), pw);
        }
    });

})(jQuery);
