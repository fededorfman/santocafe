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
                    ¿Tenés alguna<br>
                    consulta?
                </h2>
                <p class="contacto__desc">
                    Estamos para ayudarte. Ya sea por un pedido, una duda sobre nuestros
                    cafés o simplemente para charlar sobre especialidad — escribinos.
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
                            <a href="https://wa.me/56900000000" class="contacto__item-value"
                               target="_blank" rel="noopener">
                                +56 9 0000 0000
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
                            <a href="https://instagram.com/santocafe" class="contacto__item-value"
                               target="_blank" rel="noopener">
                                @santocafe
                            </a>
                        </div>
                    </div>

                </div>
            </div>

            <!-- Formulario -->
            <div>
                <?php if ( shortcode_exists( 'contact-form-7' ) || shortcode_exists( 'wpforms' ) ) :
                    // Si Contact Form 7 o WPForms está instalado, mostrar el shortcode
                    echo do_shortcode( '[contact-form-7 id="1" title="Contacto"]' );
                else : ?>
                <form class="contacto__form" action="#" method="post"
                      aria-label="Formulario de contacto">

                    <div class="form-field">
                        <label for="contact-nombre">Nombre</label>
                        <input type="text" id="contact-nombre" name="nombre"
                               placeholder="Tu nombre" autocomplete="name">
                    </div>

                    <div class="form-field">
                        <label for="contact-email">Email</label>
                        <input type="email" id="contact-email" name="email"
                               placeholder="tu@email.com" autocomplete="email">
                    </div>

                    <div class="form-field">
                        <label for="contact-mensaje">Mensaje</label>
                        <textarea id="contact-mensaje" name="mensaje"
                                  placeholder="¿En qué te podemos ayudar?"></textarea>
                    </div>

                    <button type="submit" class="btn btn--primary">
                        Enviar mensaje
                    </button>

                </form>
                <?php endif; ?>
            </div>

        </div>
    </div>
</section>
