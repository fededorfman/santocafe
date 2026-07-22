<?php
/**
 * Santo Café — CTA genérico al final de los artículos ("Explora nuestros
 * cafés de especialidad"). Se usa solo (guías sin productos recomendados)
 * o como último elemento de la grilla de productos recomendados (single.php).
 */
defined('ABSPATH') || exit;
?>
<div class="sc-article__cta">
    <span class="sc-article__cta-text">
        Explora nuestros cafés de especialidad
    </span>
    <a href="<?php echo esc_url( home_url( '/#catalogo' ) ); ?>" class="btn btn--primary">
        Ver cafés
    </a>
</div>
