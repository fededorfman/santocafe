<?php
defined('ABSPATH') || exit;

$features = [
    [
        'title' => 'Frescura en cada taza',
        'desc'  => 'Elegimos el tueste más reciente posible para perfiles definidos y máxima frescura.',
        'icon'  => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M12 2c0 0-4 4-4 8a4 4 0 0 0 8 0c0-4-4-8-4-8z"/>
                        <path d="M12 10c0 0-2 2-2 3.5a2 2 0 0 0 4 0C14 12 12 10 12 10z"/>
                        <path d="M8.5 19.5 C7 21 9 22 12 22s5-1 3.5-2.5"/>
                    </svg>',
    ],
    [
        'title' => 'Envíos Gratis',
        'desc'  => 'En 24–48 horas hábiles para pedidos desde $50.000 a todo Chile.',
        'icon'  => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <rect x="1" y="3" width="15" height="13" rx="1"/>
                        <path d="M16 8h4l3 5v4h-7V8z"/>
                        <circle cx="5.5" cy="18.5" r="2.5"/>
                        <circle cx="18.5" cy="18.5" r="2.5"/>
                    </svg>',
    ],
    [
        'title' => 'SCA 82 a 92 Puntos',
        'desc'  => 'Garantía de cafés de especialidad evaluados internacionalmente.',
        'icon'  => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <circle cx="12" cy="8" r="6"/>
                        <path d="M8.56 2.75c4.37 6.03 6.02 9.42 8.03 17.72m2.54-15.38c-3.72 4.35-8.94 5.66-16.88 5.85m19.5 1.9c-3.5-.93-6.63-.82-8.94 0-2.58.92-5.01 2.86-7.44 6.32"/>
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
        <div class="features__grid">
            <?php foreach ( $features as $feature ) : ?>
            <div class="feature-card">
                <div class="feature-card__icon" aria-hidden="true">
                    <?php echo $feature['icon']; // SVG is safe — defined in PHP, not user input ?>
                </div>
                <h3 class="feature-card__title"><?php echo esc_html( $feature['title'] ); ?></h3>
                <p class="feature-card__desc"><?php echo esc_html( $feature['desc'] ); ?></p>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
