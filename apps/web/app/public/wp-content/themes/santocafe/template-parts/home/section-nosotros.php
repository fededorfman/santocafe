<?php
defined('ABSPATH') || exit;

$values = [
    [ 'text' => '<strong>Trato directo</strong> con los mejores orígenes del mundo.' ],
    [ 'text' => '<strong>100% arábica</strong>, la base del café de especialidad.' ],
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
                        especialidad <strong>100% arábica</strong>, con un
                        <strong>puntaje SCA mínimo de 82 puntos</strong>
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

            <!-- Galería -->
            <?php
            $sc_nos_imgs = [ 'sobre-nosotros.jpg', 'sobre-nosotros2.jpg', 'sobre-nosotros3.jpg', 'sobre-nosotros4.jpg' ];
            $sc_nos_base = get_template_directory_uri() . '/assets/images/';
            ?>
            <div class="nosotros__image-wrap">
                <div class="pdesc-gallery pdesc-gallery--multi" data-pdesc-gallery>
                    <div class="pdesc-gallery__track" data-gallery-track>
                        <?php foreach ( $sc_nos_imgs as $sc_i => $sc_img ) : ?>
                        <div class="pdesc-gallery__slide<?php echo 0 === $sc_i ? ' is-active' : ''; ?>"
                             aria-hidden="<?php echo 0 === $sc_i ? 'false' : 'true'; ?>">
                            <img src="<?php echo esc_url( $sc_nos_base . $sc_img ); ?>"
                                 alt="Café de especialidad de Santo Café"
                                 loading="lazy">
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <div class="pdesc-gallery__thumbs" role="tablist" aria-label="Galería Nuestra historia">
                        <?php foreach ( $sc_nos_imgs as $sc_i => $sc_img ) : ?>
                        <button class="pdesc-gallery__thumb<?php echo 0 === $sc_i ? ' is-active' : ''; ?>"
                                data-gallery-thumb="<?php echo esc_attr( $sc_i ); ?>"
                                role="tab"
                                aria-selected="<?php echo 0 === $sc_i ? 'true' : 'false'; ?>"
                                aria-label="Imagen <?php echo esc_attr( $sc_i + 1 ); ?>">
                            <img src="<?php echo esc_url( $sc_nos_base . $sc_img ); ?>"
                                 alt="" loading="lazy" aria-hidden="true">
                        </button>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

        </div>
    </div>
</section>
