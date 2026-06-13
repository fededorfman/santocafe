<?php
defined('ABSPATH') || exit;

$hero_image = get_template_directory_uri() . '/assets/images/hero.jpg';
?>

<section class="hero" id="inicio" aria-label="Bienvenida">

    <div class="hero__bg" style="background-image: url('<?php echo esc_url( $hero_image ); ?>');"
         role="img" aria-label="Cafetera espresso con latte art"></div>
    <div class="hero__overlay" aria-hidden="true"></div>

    <div class="container hero__content">

        <span class="hero__eyebrow">Café de especialidad en Santiago</span>

        <h1 class="hero__title">
            <span class="hero__title-line1">Un buen día</span>
            <span class="hero__title-line2">es un buen café.</span>
        </h1>

        <p class="hero__description">
            Seleccionamos los mejores orígenes de especialidad con el tueste más
            reciente, para que lleguen frescos a tu taza.<br>
            Envío gratis desde $50.000.
        </p>

        <div class="hero__chips">
            <span class="hero__chip">
                <svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M12 17.27 18.18 21l-1.64-7.03L22 9.24l-7.19-.61L12 2 9.19 8.63 2 9.24l5.46 4.73L5.82 21z"/></svg>SCA 82+
            </span>
            <span class="hero__chip">8 orígenes latinos</span>
            <span class="hero__chip">Tueste reciente</span>
            <span class="hero__chip">Envío en 24–48 h</span>
        </div>

        <a href="#catalogo" class="btn btn--primary btn--lg hero__cta">
            Comprar café
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none"
                 stroke="currentColor" stroke-width="2" stroke-linecap="round"
                 stroke-linejoin="round" aria-hidden="true">
                <line x1="5" y1="12" x2="19" y2="12"/>
                <polyline points="12 5 19 12 12 19"/>
            </svg>
        </a>

    </div>

    <div class="hero__scroll-indicator js-scroll-indicator" aria-hidden="true">
        <div class="scroll-mouse">
            <div class="scroll-wheel"></div>
        </div>
    </div>

</section>
