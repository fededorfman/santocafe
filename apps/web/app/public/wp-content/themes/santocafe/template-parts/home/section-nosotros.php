<?php
defined('ABSPATH') || exit;

$values = [
    [ 'text' => '<strong>Trato directo</strong> con los mejores orígenes del mundo.' ],
    [ 'text' => 'Cafés de especialidad con <strong>+82 puntos SCA</strong>.' ],
    [ 'text' => '<strong>Tueste reciente</strong> para una máxima frescura.' ],
    [ 'text' => 'Envíos en la Región Metropolitana de Santiago en <strong>24-48&nbsp;horas hábiles</strong>.' ],
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
                        Santo Café nació con una certeza: un gran café tiene el poder de transformar
                        tu día. Nos comprometemos con la calidad y la trazabilidad, asegurándonos
                        de conocer la historia y el origen de cada grano que elijes.
                    </p>
                    <p>
                        Trabajamos mano a mano con productores de Colombia, Perú, Bolivia,
                        Brasil, Guatemala y Costa Rica. Seleccionamos exclusivamente cafés de
                        especialidad con un <strong>puntaje SCA mínimo de 82 puntos</strong>
                        y optamos siempre por un tueste reciente. Así, garantizamos que la
                        frescura y los descriptores únicos del grano lleguen intactos a tu mesa.
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
                        <span><?php echo wp_kses( $value['text'], [ 'strong' => [] ] ); ?></span>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Imagen -->
            <div class="nosotros__image-wrap">
                <img class="nosotros__image"
                     src="<?php echo esc_url( get_template_directory_uri() . '/assets/images/sobre-nosotros.png' ); ?>"
                     alt="Café de especialidad de Santo Café"
                     width="800" height="800"
                     loading="lazy">
            </div>

        </div>
    </div>
</section>
