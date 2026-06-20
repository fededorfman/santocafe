<?php
defined('ABSPATH') || exit;

$hero_image = get_template_directory_uri() . '/assets/images/hero.jpg';
?>

<section class="hero" id="inicio" aria-label="Bienvenida">

    <?php // La imagen real va en una var CSS para apilarla sobre el LQIP (placeholder borroso en _home.css). ?>
    <div class="hero__bg" style="--hero-img: url('<?php echo esc_url( $hero_image ); ?>');"
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

        <a href="#catalogo" class="btn btn--lg hero__cta hero__cta--gooey">
            <span class="hero__cta-label">
                Comprar café
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none"
                     stroke="currentColor" stroke-width="1.4" stroke-linecap="round"
                     stroke-linejoin="round" aria-hidden="true">
                    <path d="M4 9h13v5a5 5 0 0 1-5 5H9a5 5 0 0 1-5-5V9z"></path>
                    <path d="M17 10h2.4a2.4 2.4 0 0 1 0 4.8H17"></path>
                    <path d="M7.5 5.5c0-1 .8-1.4.8-2.5M11 5.5c0-1 .8-1.4.8-2.5"></path>
                </svg>
            </span>
            <span class="hero__cta-blobs" aria-hidden="true">
                <span></span><span></span><span></span>
            </span>
        </a>

        <?php /* Filtro SVG "gooey" para el relleno con blobs del CTA (decorativo). */ ?>
        <svg class="hero__goo-defs" width="0" height="0" aria-hidden="true" focusable="false">
            <defs>
                <filter id="sc-goo">
                    <feGaussianBlur in="SourceGraphic" stdDeviation="10" result="blur"></feGaussianBlur>
                    <feColorMatrix in="blur" mode="matrix"
                        values="1 0 0 0 0  0 1 0 0 0  0 0 1 0 0  0 0 0 18 -7" result="goo"></feColorMatrix>
                    <feBlend in="SourceGraphic" in2="goo"></feBlend>
                </filter>
            </defs>
        </svg>

    </div>

    <div class="hero__scroll-indicator js-scroll-indicator" aria-hidden="true">
        <div class="scroll-mouse">
            <div class="scroll-wheel"></div>
        </div>
    </div>

</section>
