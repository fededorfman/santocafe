# Productos recomendados en guías

## Contexto y objetivo

Las guías de preparación (posts bajo la categoría "Guías", plantilla `single.php`)
hoy terminan con un CTA genérico ("Explora nuestros cafés de especialidad → Ver
cafés"). Tres de las cinco guías existentes ya recomiendan productos específicos
dentro del propio texto del artículo:

| Guía (post ID) | Productos mencionados en el texto |
|---|---|
| Cómo preparar espresso en casa (69) | Santo Ouro Doce (32), Santo Sereno (35), Los Santos (41) |
| Cómo preparar café en cafetera italiana / moka (70) | Macondo (20), Santo Yungas (29) |
| Cómo preparar café de filtro / V60 (71) | Cielo Andino (26), Camino Inca (23), Santo Equilibrio (38) |

Las otras dos guías ("Qué es el café de especialidad y por qué importa el puntaje
SCA" y "Café lavado vs natural: diferencias de sabor y proceso") no recomiendan
productos puntuales — son explicativas, no de método de preparación.

El objetivo es mostrar esos productos como una sección de "recomendados" al final
de la guía, con la tarjeta compacta (la misma variante `compact` construida para el
test A/B del catálogo), en vez de dejar la recomendación solo como texto plano
dentro del artículo.

## Alcance

- Nuevo campo en el editor de posts: **"Productos recomendados"**, lista de
  checkboxes con los 8 cafés del catálogo. Guardado como meta del post
  (`_sc_related_products`, array de IDs de producto).
- Sin buscador ni autocompletado — son 8 productos fijos, una lista de checkboxes
  alcanza.
- Se configuran manualmente los 3 posts existentes (69, 70, 71) con los productos
  de la tabla de arriba. Los posts 72 y 73 quedan sin marcar.
- En `single.php`, si el post tiene al menos un producto marcado (válido y
  visible), se agrega una sección nueva **justo arriba** del CTA genérico actual,
  con un título y una grilla de tarjetas compactas.
- Si el post no tiene productos marcados (o ninguno de los marcados sigue siendo
  válido/visible — por ejemplo, si se borra un producto más adelante), la sección
  no se muestra y el CTA genérico sigue exactamente igual que hoy.
- Fuera de alcance: selección automática por contenido/categoría, edición de las
  otras 2 guías, cualquier cambio al CTA genérico existente más allá de moverlo
  después de la nueva sección.

## Campo "Productos recomendados"

Nuevo meta box en la pantalla de edición de post (`add_meta_box`, contexto
`normal`, visible para el post type `post`), título "Productos recomendados",
con un checkbox por cada producto publicado de WooCommerce (`nombre — origen`,
usando los mismos helpers que ya existen: `sc_get_product_meta($id, 'pais')`).
Al guardar el post, se procesa igual que los demás meta fields del tema —
`sanitize`/`absint` en cada ID, verificación de nonce y capability, guardado
como array serializado en `_sc_related_products`.

## Render en `single.php`

Justo antes del bloque `<div class="sc-article__cta">` actual:

```php
<?php
$sc_related_product_ids = get_post_meta( get_the_ID(), '_sc_related_products', true );
$sc_related_product_ids = is_array( $sc_related_product_ids ) ? $sc_related_product_ids : [];
?>
<?php if ( ! empty( $sc_related_product_ids ) ) : ?>
<section class="sc-article__products">
    <h2 class="sc-article__products-title">Recomendados para esta preparación</h2>
    <div class="catalog-section__grid catalog-section__grid--compact sc-article__products-grid">
        <?php
        global $product;
        foreach ( $sc_related_product_ids as $sc_rp_id ) {
            $product = wc_get_product( $sc_rp_id );
            if ( $product && $product->is_visible() ) {
                get_template_part( 'template-parts/product/card-compact' );
            }
        }
        $product = null; // esta página no es de producto — no dejar $product global apuntando al último recomendado
        ?>
    </div>
</section>
<?php endif; ?>
```

Reutiliza `template-parts/product/card-compact.php` (mismo template del test A/B,
sin cambios) y las clases `.catalog-section__grid` / `.catalog-section__grid--compact`
ya existentes (misma grilla y el breakpoint de 2 columnas en mobile) — no hace
falta CSS de grilla nuevo. Se agrega únicamente el CSS del título de la sección
(`.sc-article__products-title`) y un margen/spacing para `.sc-article__products`,
siguiendo el estilo de `.sc-article__related-title` ya existente en el mismo
archivo de estilos.

## Testing

- Verificar que el meta box aparece en la pantalla de edición de cualquier post,
  con los 8 productos listados y sus checkboxes.
- Guardar un post con 2 productos tildados, recargar la pantalla de edición y
  confirmar que los checkboxes siguen marcados (persistencia).
- Verificar en el navegador que las guías 69, 70 y 71 muestran la sección nueva
  con sus productos correspondientes (tarjeta compacta), y que las guías 72 y 73
  siguen mostrando solo el CTA genérico, sin la sección nueva.
- Verificar que la grilla se ve en 2 columnas en mobile (mismo comportamiento que
  el catálogo de la home).
- Verificar que si se desmarcan todos los productos de una guía, la sección
  desaparece y el CTA genérico queda igual que antes.
