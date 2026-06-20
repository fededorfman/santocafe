<?php
/**
 * Home — Reseñas de clientes.
 * Dos carouseles infinitos (uno se desplaza a la izquierda, otro a la derecha)
 * con scroll continuo por CSS. Todas 5 estrellas.
 */
defined( 'ABSPATH' ) || exit;

$sc_star  = '<svg class="review-card__star" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M12 17.27 18.18 21l-1.64-7.03L22 9.24l-7.19-.61L12 2 9.19 8.63 2 9.24l5.46 4.73L5.82 21z"/></svg>';
$sc_stars = str_repeat( $sc_star, 5 );

// Fila que se desplaza hacia la izquierda
$sc_reviews_left = [
    [ 'name' => 'Matías G.',     'text' => 'Compré café ahí por primera vez por recomendación, compré de 3 variedades y nunca mas volveré a comprar café de supermercado.. volveré a pedir pronto,' ],
    [ 'name' => 'Constanza V.',  'text' => 'pedí el de Colombia y se nota la diferencia con el de supermercado. llegó rapidísimo y bien embalado, ya van 3 pedidos y seguimos' ],
    [ 'name' => 'Felipe C.',     'text' => 'Yo era consumidor habitual de otra marca de café y quise buscar otro proveedor por cambiar un poco. He de decir que fue un descubrimiento y un acierto, la relación calidad-precio es inmejorable' ],
    [ 'name' => 'Antonia P.',    'text' => 'Lo regale para el dia del padre y a mi papa le encanto. despues volvi a comprar para la casa y no me arrepiento para nada' ],
    [ 'name' => 'Sebastián M.',  'text' => 'Café natural en grano y con sabor a café de verdad, ni comparación con muchas marcas comerciales que venden torrefacto a buen precio.' ],
    [ 'name' => 'Valentina H.',  'text' => 'Soy media exigente con el cafe y este me dejo contenta. se nota el tueste reciente, nada que ver con los molidos que venden en el super' ],
    [ 'name' => 'Fernanda L.',   'text' => 'buenísimo todo, el sabor, el aroma y la rapidez del envío. lo único que la bolsa se me terminó rápido porque tomo harto café jaja' ],
    [ 'name' => 'Catalina N.',   'text' => 'Me escribieron para coordinar la entrega y llegó puntual. la atención de primera y el café intenso pero sin amargar.' ],
    [ 'name' => 'Tomás B.',      'text' => 'el de Guatemala me voló la cabeza, súper aromático. lo tomo solo sin azúcar y queda perfecto, ya es parte de la rutina de la mañana' ],
    [ 'name' => 'Francisca E.',  'text' => 'Pedí para la oficina y a todos les gustó, ahora compramos seguido. el envío siempre llega cuando dicen, sin atrasos' ],
    [ 'name' => 'Joaquín R.',    'text' => 'probé varios y me quedo con el de Bolivia, buen cuerpo y no amarga. la verdad muy buena compra y mejor precio que otros' ],
];

// Fila que se desplaza hacia la derecha
$sc_reviews_right = [
    [ 'name' => 'Camila R.',     'text' => 'Grandes profesionales, dan un trato genial, con producto de mucha calidad, sabor y aroma. Recomendable al 100%.' ],
    [ 'name' => 'Diego A.',      'text' => 'muy buena atención, me asesoraron para elegir según la cafetera que tengo. el café fresco se siente en el aroma apenas abres la bolsa' ],
    [ 'name' => 'Javiera S.',    'text' => 'Un café delicioso y de gran calidad, tostado en su punto y a un precio razonable. La parte de la empresa y el transporte, excelente. Lo recomiendo 100%' ],
    [ 'name' => 'Cristóbal F.',  'text' => 'excelente relación precio calidad. probé el natural de Brasil y es suavecito, ideal para la mañana. el despacho llegó antes de lo previsto' ],
    [ 'name' => 'Ignacio T.',    'text' => 'Compré por recomendación de una amiga y ahora ando recomendando yo. la molienda para prensa francesa quedó perfecta' ],
    [ 'name' => 'Benjamín O.',   'text' => 'Primera vez que pido cafe de especialidad y no vuelvo atras. el de Peru tiene un dejo dulzon que me gusto harto, recomendado' ],
    [ 'name' => 'Josefa M.',     'text' => 'Llevo varios meses comprando y nunca me ha fallado, siempre fresco y rico. el repartidor además súper amable.' ],
    [ 'name' => 'Martina S.',    'text' => 'me encanta que venga molido al gusto, lo pedí para filtro y quedó tal cual. se nota la frescura, nada que ver con el de antes' ],
    [ 'name' => 'Nicolás P.',    'text' => 'Atención impecable, respondieron todas mis dudas por whatsapp rápido. el café riquísimo, lo recomiendo con los ojos cerrados' ],
    [ 'name' => 'Isidora C.',    'text' => 'regalo perfecto para mi hermano que es cafetero, quedó feliz con el pack. seguro vuelvo a comprar para las fiestas' ],
    [ 'name' => 'Rodrigo M.',    'text' => 'muy buenos, quiero probar todos. Y algo que añadir, el reparto y el repartidor de 10' ],
];

$sc_render_reviews = static function ( array $items, bool $hidden = false ) use ( $sc_stars ): string {
    $out = '';
    foreach ( $items as $r ) {
        $out .= '<article class="review-card"' . ( $hidden ? ' aria-hidden="true"' : '' ) . '>'
              . '<div class="review-card__head">'
              .   '<div class="review-card__stars" role="img" aria-label="5 de 5 estrellas">' . $sc_stars . '</div>'
              .   '<span class="review-card__name">' . esc_html( $r['name'] ) . '</span>'
              . '</div>'
              . '<p class="review-card__text">' . esc_html( $r['text'] ) . '</p>'
              . '</article>';
    }
    return $out;
};
?>

<section class="reviews" aria-label="Reseñas de clientes">
    <div class="container reviews__head">
        <span class="reviews__label">Reseñas</span>
        <h2 class="reviews__title">Lo que nos llena de orgullo</h2>
        <div class="reviews__rating" role="img" aria-label="Calificación 4.9 de 5 sobre más de 100 reseñas">
            <span class="reviews__rating-score">4.9</span>
            <span class="reviews__rating-stars" aria-hidden="true"><?php echo $sc_stars; // SVG estático ?></span>
            <span class="reviews__rating-count">(+100)</span>
        </div>
    </div>

    <div class="reviews__marquee reviews__marquee--left">
        <div class="reviews__track">
            <?php
            echo $sc_render_reviews( $sc_reviews_left );        // phpcs:ignore — HTML escapado en el render
            echo $sc_render_reviews( $sc_reviews_left, true );  // duplicado para el loop continuo
            ?>
        </div>
    </div>

    <div class="reviews__marquee reviews__marquee--right">
        <div class="reviews__track">
            <?php
            echo $sc_render_reviews( $sc_reviews_right );
            echo $sc_render_reviews( $sc_reviews_right, true );
            ?>
        </div>
    </div>
</section>
