<?php
defined('ABSPATH') || exit;

$features = [
    [
        'title' => 'Frescura en cada taza',
        'desc'  => 'Elegimos el tueste más reciente posible para perfiles definidos y máxima frescura.',
        'icon'  => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M17 8h1a4 4 0 0 1 0 8h-1"/>
                        <path d="M3 8h14v9a4 4 0 0 1-4 4H7a4 4 0 0 1-4-4V8z"/>
                        <line x1="6" y1="1" x2="6" y2="4"/>
                        <line x1="10" y1="1" x2="10" y2="4"/>
                        <line x1="14" y1="1" x2="14" y2="4"/>
                    </svg>',
    ],
    [
        'title' => 'Envíos Gratis',
        'desc'  => 'En 24–48 horas hábiles para pedidos desde $50.000 a todo Chile.',
        'icon'  => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <rect x="1" y="3" width="15" height="13" rx="1"/>
                        <polygon points="16 8 20 8 23 11 23 16 16 16 16 8"/>
                        <circle cx="5.5" cy="18.5" r="2.5"/>
                        <circle cx="18.5" cy="18.5" r="2.5"/>
                    </svg>',
    ],
    [
        'title' => 'SCA 82 a 92 Puntos',
        'desc'  => 'Garantía de cafés de especialidad evaluados internacionalmente.',
        'icon'  => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <circle cx="12" cy="8" r="7"/>
                        <polyline points="8.21 13.89 7 23 12 20 17 23 15.79 13.88"/>
                    </svg>',
    ],
    [
        'title' => 'Pasión Auténtica',
        'desc'  => 'Somos amantes del café como tú, y nuestro compromiso está en la calidad.',
        'icon'  => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/>
                    </svg>',
    ],
];
?>

<section class="features" id="beneficios" aria-label="Nuestros beneficios">
    <div class="container">
        <div class="features__grid js-features-carousel">
            <?php foreach ( $features as $feature ) : ?>
            <div class="feature-card">
                <div class="feature-card__icon" aria-hidden="true">
                    <?php echo $feature['icon']; // SVG is safe — defined in PHP, not user input ?>
                </div>
                <h2 class="feature-card__title"><?php echo esc_html( $feature['title'] ); ?></h2>
                <p class="feature-card__desc"><?php echo esc_html( $feature['desc'] ); ?></p>
            </div>
            <?php endforeach; ?>
        </div>
        <?php // Dots are injected by JS on mobile, where the grid becomes a carousel. ?>
        <div class="features__dots js-features-dots" role="tablist" aria-label="Navegación de beneficios"></div>
    </div>
</section>
