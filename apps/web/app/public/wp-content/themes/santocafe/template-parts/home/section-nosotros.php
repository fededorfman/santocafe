<?php
defined('ABSPATH') || exit;

$values = [
    [ 'text' => 'Selección directa de los mejores orígenes del mundo' ],
    [ 'text' => 'Tostado artesanal en pequeños lotes, con frescura garantizada' ],
    [ 'text' => 'Cafés evaluados bajo estándares SCA, entre 82 y 92 puntos' ],
    [ 'text' => 'Envío a todo Chile en 24-48 horas hábiles' ],
];
?>

<section class="nosotros" id="nosotros" aria-label="Sobre nosotros">
    <div class="container">
        <div class="nosotros__grid">

            <!-- Texto -->
            <div class="nosotros__text">
                <span class="nosotros__label">Nuestra historia</span>
                <h2 class="nosotros__title">
                    Pasión por el café<br>
                    <span class="text-dorado">en cada taza.</span>
                </h2>
                <div class="nosotros__body">
                    <p>
                        Santo Café nació de la convicción de que un buen café puede transformar
                        el inicio del día. Somos tostadores artesanales comprometidos con la
                        calidad y la trazabilidad: conocemos el origen de cada grano que llega
                        a tu taza.
                    </p>
                    <p>
                        Trabajamos directamente con productores de Colombia, Perú, Bolivia,
                        Brasil, Guatemala y Costa Rica, seleccionando únicamente cafés de
                        especialidad con puntajes SCA entre 82 y 92 puntos. Tostamos en
                        pequeños lotes para garantizar frescura y perfiles de sabor definidos.
                    </p>
                </div>

                <div class="nosotros__values">
                    <?php foreach ( $values as $value ) : ?>
                    <div class="nosotros__value-item">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"
                             stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                             aria-hidden="true">
                            <polyline points="20 6 9 17 4 12"/>
                        </svg>
                        <span><?php echo esc_html( $value['text'] ); ?></span>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Imagen -->
            <div class="nosotros__image-wrap">
                <img class="nosotros__image"
                     src="https://images.unsplash.com/photo-1495474472287-4d71bcdd2085?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80"
                     alt="Tostado artesanal de café en Santo Café"
                     width="800" height="600"
                     loading="lazy">
            </div>

        </div>
    </div>
</section>
