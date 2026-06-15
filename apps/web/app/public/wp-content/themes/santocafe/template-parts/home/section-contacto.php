<?php
defined('ABSPATH') || exit;
?>

<section class="contacto" id="contacto" aria-label="Contacto">
    <div class="container">
        <div class="contacto__grid">

            <!-- Info -->
            <div class="contacto__info">
                <span class="contacto__label">Contacto</span>
                <h2 class="contacto__title">
                    ¿Tienes alguna<br>
                    consulta?
                </h2>
                <p class="contacto__desc">
                    Estamos para ayudarte. Ya sea por un pedido, una duda sobre nuestros
                    cafés o simplemente para charlar sobre especialidad, escríbenos.
                </p>

                <div class="contacto__items">

                    <div class="contacto__item">
                        <div class="contacto__item-icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                 stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/>
                                <polyline points="22,6 12,13 2,6"/>
                            </svg>
                        </div>
                        <div>
                            <span class="contacto__item-label">Email</span>
                            <a href="mailto:hola@santocafe.cl" class="contacto__item-value">
                                hola@santocafe.cl
                            </a>
                        </div>
                    </div>

                    <div class="contacto__item">
                        <div class="contacto__item-icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                 stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M21 11.5a8.38 8.38 0 01-.9 3.8 8.5 8.5 0 01-7.6 4.7 8.38 8.38 0 01-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 01-.9-3.8 8.5 8.5 0 014.7-7.6 8.38 8.38 0 013.8-.9h.5a8.48 8.48 0 018 8v.5z"/>
                            </svg>
                        </div>
                        <div>
                            <span class="contacto__item-label">WhatsApp</span>
                            <a href="https://wa.me/56951414791" class="contacto__item-value"
                               target="_blank" rel="noopener">
                                +56 9 5141 4791
                            </a>
                        </div>
                    </div>

                    <div class="contacto__item">
                        <div class="contacto__item-icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                 stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                                <rect x="2" y="2" width="20" height="20" rx="5" ry="5"/>
                                <path d="M16 11.37A4 4 0 1112.63 8 4 4 0 0116 11.37z"/>
                                <line x1="17.5" y1="6.5" x2="17.51" y2="6.5"/>
                            </svg>
                        </div>
                        <div>
                            <span class="contacto__item-label">Instagram</span>
                            <a href="https://instagram.com/santocafespecialtycoffee" class="contacto__item-value"
                               target="_blank" rel="noopener">
                                @santocafespecialtycoffee
                            </a>
                        </div>
                    </div>

                </div>
            </div>

            <!-- Formulario -->
            <div class="contacto__form-col">
                <?php if ( shortcode_exists( 'contact-form-7' ) || shortcode_exists( 'wpforms' ) ) :
                    // Si Contact Form 7 o WPForms está instalado, mostrar el shortcode
                    echo do_shortcode( '[contact-form-7 id="1" title="Contacto"]' );
                else : ?>
                <form class="contacto__form js-validate js-contact-form" action="#" method="post" novalidate
                      aria-label="Formulario de contacto">

                    <div class="form-field validate-required">
                        <label for="contact-nombre">Nombre <span class="form-field__req" aria-hidden="true">*</span></label>
                        <input type="text" id="contact-nombre" name="nombre"
                               placeholder="Tu nombre…" autocomplete="name" required aria-required="true">
                    </div>

                    <div class="form-field validate-required">
                        <label for="contact-email">Email <span class="form-field__req" aria-hidden="true">*</span></label>
                        <input type="email" id="contact-email" name="email"
                               placeholder="tu@email.com" autocomplete="email" spellcheck="false" required aria-required="true">
                    </div>

                    <div class="form-field validate-required">
                        <label for="contact-mensaje">Mensaje <span class="form-field__req" aria-hidden="true">*</span></label>
                        <textarea id="contact-mensaje" name="mensaje"
                                  placeholder="Cuéntanos en qué te podemos ayudar…" required aria-required="true"></textarea>
                    </div>

                    <button type="submit" class="btn btn--primary">
                        Enviar mensaje
                    </button>

                </form>

                <div class="contacto__success js-contact-success" role="status" hidden>
                    <span class="contacto__success-icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>
                    </span>
                    <h3 class="contacto__success-title">¡Mensaje enviado!</h3>
                    <p class="contacto__success-text">Gracias por escribirnos. Te responderemos a la brevedad.</p>
                </div>
                <?php endif; ?>
            </div>

        </div>
    </div>
</section>
