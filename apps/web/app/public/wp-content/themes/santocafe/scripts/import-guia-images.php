<?php
/**
 * Import guide images — Santo Café
 *
 * Uploads images from scripts/guide-images/ to the WordPress media library,
 * sets featured images, and inserts content images into post body via WP blocks.
 *
 * Usage: php import-guia-images.php
 * Safe to re-run: checks existing attachments by meta key before re-uploading.
 */
define( 'WP_USE_THEMES', false );
require __DIR__ . '/../../../../wp-load.php';

require_once ABSPATH . 'wp-admin/includes/image.php';
require_once ABSPATH . 'wp-admin/includes/file.php';
require_once ABSPATH . 'wp-admin/includes/media.php';

$images_dir = __DIR__ . '/guide-images/';

// ============================================================
// Helper: upload one image, avoid duplicates, return attachment ID
// ============================================================
function sc_import_image( string $src_path, int $parent_post_id, string $title, string $alt, string $caption ): int|WP_Error {
    // Duplicate check by a custom meta we store after upload
    $existing = get_posts( [
        'post_type'   => 'attachment',
        'post_status' => 'inherit',
        'numberposts' => 1,
        'meta_key'    => '_sc_import_src',
        'meta_value'  => basename( $src_path ),
    ] );
    if ( ! empty( $existing ) ) {
        echo "  [SKIP] already uploaded: " . basename( $src_path ) . " (#{$existing[0]->ID})\n";
        return $existing[0]->ID;
    }

    // media_handle_sideload treats tmp_name as a file to move/delete — copy first
    $tmp = sys_get_temp_dir() . '/' . uniqid( 'sc_', true ) . '_' . basename( $src_path );
    if ( ! copy( $src_path, $tmp ) ) {
        return new WP_Error( 'copy_failed', "Could not copy {$src_path} to {$tmp}" );
    }

    $file_array = [
        'name'     => basename( $src_path ),
        'tmp_name' => $tmp,
    ];

    $id = media_handle_sideload( $file_array, $parent_post_id, $title );
    if ( is_wp_error( $id ) ) {
        @unlink( $tmp );
        return $id;
    }

    // Alt text + caption
    update_post_meta( $id, '_wp_attachment_image_alt', $alt );
    wp_update_post( [
        'ID'           => $id,
        'post_excerpt' => $caption,
        'post_title'   => $title,
    ] );

    // Mark as imported so we can skip on re-run
    update_post_meta( $id, '_sc_import_src', basename( $src_path ) );

    echo "  [UPLOAD] {$title} (#{$id})\n";
    return $id;
}

// ============================================================
// Helper: insert a WP image block into post content after a heading
// ============================================================
function sc_insert_block_after_heading( string $content, string $heading_text, int $att_id, string $img_url, string $alt, string $caption ): string {
    $block = <<<BLOCK


<!-- wp:image {"id":{$att_id},"sizeSlug":"large","linkDestination":"none"} -->
<figure class="wp-block-image size-large"><img src="{$img_url}" alt="{$alt}" class="wp-image-{$att_id}"/><figcaption class="wp-element-caption">{$caption}</figcaption></figure>
<!-- /wp:image -->

BLOCK;

    // Match the heading block regardless of extra attributes
    $pattern = '/(<h2[^>]*>' . preg_quote( $heading_text, '/' ) . '<\/h2>\n<!-- \/wp:heading -->)/i';
    if ( preg_match( $pattern, $content ) ) {
        $content = preg_replace( $pattern, '$1' . $block, $content, 1 );
    } else {
        // Fallback: plain string match
        $needle  = "<h2>{$heading_text}</h2>\n<!-- /wp:heading -->";
        $content = str_replace( $needle, $needle . $block, $content );
    }
    return $content;
}

// ============================================================
// GUÍA 1 — Espresso
// ============================================================
echo "\n== Guía 1: Espresso ==\n";

$post = get_page_by_path( 'como-preparar-espresso', OBJECT, 'post' );
if ( ! $post ) {
    echo "ERROR: post 'como-preparar-espresso' not found\n";
} else {
    $pid = $post->ID;
    echo "Post #{$pid} — {$post->post_title}\n";

    $featured_id = sc_import_image(
        $images_dir . 'guia1-featured.png',
        $pid,
        'Espresso en taza — Santo Café',
        'Taza de espresso con crema color avellana sobre lino oscuro, cuchara dorada y granos de café',
        'Un espresso bien preparado tiene una crema color avellana y aroma intenso.'
    );

    $molienda_id = sc_import_image(
        $images_dir . 'guia1-molienda.png',
        $pid,
        'Portafiltro con molienda espresso — Santo Café',
        'Portafiltro con café molido fino listo para espresso junto a un molinillo de mano',
        'La molienda espresso es muy fina, similar a harina. Debe verse como polvo compacto entre los dedos.'
    );

    $extraccion_id = sc_import_image(
        $images_dir . 'guia1-extraccion.png',
        $pid,
        'Extracción de espresso en máquina — Santo Café',
        'Máquina de espresso extrayendo café en una taza blanca, fondo de cafetería',
        'El tiempo de extracción ideal es entre 25 y 35 segundos para una proporción 1:2.'
    );

    // Featured image
    if ( ! is_wp_error( $featured_id ) ) {
        set_post_thumbnail( $pid, $featured_id );
        echo "  [FEATURED] set thumbnail → #{$featured_id}\n";
    }

    // Insert content images
    $content = $post->post_content;
    $changed = false;

    if ( ! is_wp_error( $molienda_id ) ) {
        $url     = wp_get_attachment_image_url( $molienda_id, 'large' );
        $new     = sc_insert_block_after_heading(
            $content,
            'Molienda para espresso',
            $molienda_id, $url,
            'Portafiltro con café molido fino para espresso',
            'La molienda espresso es muy fina, similar a harina. Debe verse como polvo compacto entre los dedos.'
        );
        if ( $new !== $content ) { $content = $new; $changed = true; echo "  [CONTENT] imagen molienda insertada\n"; }
        else { echo "  [WARN] no se encontró el heading 'Molienda para espresso'\n"; }
    }

    if ( ! is_wp_error( $extraccion_id ) ) {
        $url     = wp_get_attachment_image_url( $extraccion_id, 'large' );
        $new     = sc_insert_block_after_heading(
            $content,
            'Pasos para preparar el espresso perfecto',
            $extraccion_id, $url,
            'Máquina de espresso extrayendo café en una taza blanca',
            'El tiempo de extracción ideal es entre 25 y 35 segundos para una proporción 1:2.'
        );
        if ( $new !== $content ) { $content = $new; $changed = true; echo "  [CONTENT] imagen extracción insertada\n"; }
        else { echo "  [WARN] no se encontró el heading 'Pasos para preparar el espresso perfecto'\n"; }
    }

    if ( $changed ) {
        wp_update_post( [ 'ID' => $pid, 'post_content' => $content ] );
        echo "  [SAVED] post content actualizado\n";
    }
}

// ============================================================
// GUÍA 2 — Italiana (moka)
// ============================================================
echo "\n== Guía 2: Italiana ==\n";

$post = get_page_by_path( 'como-preparar-cafe-en-italiana', OBJECT, 'post' );
if ( ! $post ) {
    echo "ERROR: post 'como-preparar-cafe-en-italiana' not found\n";
} else {
    $pid = $post->ID;
    echo "Post #{$pid} — {$post->post_title}\n";

    $featured_id = sc_import_image(
        $images_dir . 'guia2-featured.png',
        $pid,
        'Cafetera italiana en hornalla — Santo Café',
        'Cafetera moka plateada sobre hornalla con llama azul, vapor saliendo, fondo cálido',
        'La cafetera italiana es uno de los métodos más populares en hogares latinoamericanos.'
    );

    $armado_id = sc_import_image(
        $images_dir . 'guia2-armado.png',
        $pid,
        'Cafetera italiana desarmada con café molido — Santo Café',
        'Piezas de la cafetera moka desarmadas: caldera con agua, embudo con café molido, parte superior',
        'Llenás el embudo con molienda italiana — media-fina, sin apisonar y al ras.'
    );

    $servido_id = sc_import_image(
        $images_dir . 'guia2-servido.png',
        $pid,
        'Café de italiana siendo servido en taza — Santo Café',
        'Café oscuro y aromático siendo vertido desde una moka hacia una pequeña taza blanca',
        'Remové el café antes de servir para homogeneizar la concentración.'
    );

    if ( ! is_wp_error( $featured_id ) ) {
        set_post_thumbnail( $pid, $featured_id );
        echo "  [FEATURED] set thumbnail → #{$featured_id}\n";
    }

    $content = $post->post_content;
    $changed = false;

    if ( ! is_wp_error( $armado_id ) ) {
        $url = wp_get_attachment_image_url( $armado_id, 'large' );
        $new = sc_insert_block_after_heading(
            $content,
            'Cuánta agua poner en la cafetera italiana',
            $armado_id, $url,
            'Cafetera italiana desarmada mostrando el armado correcto',
            'Llenás el embudo con molienda italiana — media-fina, sin apisonar y al ras.'
        );
        if ( $new !== $content ) { $content = $new; $changed = true; echo "  [CONTENT] imagen armado insertada\n"; }
        else { echo "  [WARN] heading 'Cuánta agua poner...' no encontrado\n"; }
    }

    if ( ! is_wp_error( $servido_id ) ) {
        $url = wp_get_attachment_image_url( $servido_id, 'large' );
        $new = sc_insert_block_after_heading(
            $content,
            'Pasos para preparar café en italiana',
            $servido_id, $url,
            'Café de italiana siendo vertido desde la moka hacia una taza',
            'Remové el café antes de servir para homogeneizar la concentración.'
        );
        if ( $new !== $content ) { $content = $new; $changed = true; echo "  [CONTENT] imagen servido insertada\n"; }
        else { echo "  [WARN] heading 'Pasos para preparar café en italiana' no encontrado\n"; }
    }

    if ( $changed ) {
        wp_update_post( [ 'ID' => $pid, 'post_content' => $content ] );
        echo "  [SAVED] post content actualizado\n";
    }
}

// ============================================================
// GUÍA 3 — Filtro / V60
// ============================================================
echo "\n== Guía 3: Filtro / V60 ==\n";

$post = get_page_by_path( 'como-preparar-cafe-de-filtro', OBJECT, 'post' );
if ( ! $post ) {
    echo "ERROR: post 'como-preparar-cafe-de-filtro' not found\n";
} else {
    $pid = $post->ID;
    echo "Post #{$pid} — {$post->post_title}\n";

    $featured_id = sc_import_image(
        $images_dir . 'guia3-featured.png',
        $pid,
        'Preparación de café de filtro V60 — Santo Café',
        'Manos vertiendo agua en un V60 sobre una jarra de vidrio, luz de ventana de fondo',
        'El pour-over o filtro es el método favorito de los amantes del café de especialidad.'
    );

    $bloom_id = sc_import_image(
        $images_dir . 'guia3-bloom.png',
        $pid,
        'Bloom o pre-infusión en V60 — Santo Café',
        'Pre-infusión en V60: café molido empapándose con burbujas de CO2 visibles, luz matutina',
        'El bloom libera el CO₂ del café y prepara el lecho para una extracción pareja.'
    );

    $taza_id = sc_import_image(
        $images_dir . 'guia3-taza.png',
        $pid,
        'Café de filtro terminado en taza de vidrio — Santo Café',
        'Café de filtro claro y ámbar transparente en una taza de vidrio sobre mesa de madera',
        'Un buen filtro es claro, ámbar y transparente — sin turbidez ni sedimento.'
    );

    if ( ! is_wp_error( $featured_id ) ) {
        set_post_thumbnail( $pid, $featured_id );
        echo "  [FEATURED] set thumbnail → #{$featured_id}\n";
    }

    $content = $post->post_content;
    $changed = false;

    if ( ! is_wp_error( $bloom_id ) ) {
        $url = wp_get_attachment_image_url( $bloom_id, 'large' );
        $new = sc_insert_block_after_heading(
            $content,
            'Proporción y dosis',
            $bloom_id, $url,
            'Pre-infusión (bloom) en V60 con burbujas de CO2',
            'El bloom libera el CO₂ del café y prepara el lecho para una extracción pareja.'
        );
        if ( $new !== $content ) { $content = $new; $changed = true; echo "  [CONTENT] imagen bloom insertada\n"; }
        else { echo "  [WARN] heading 'Proporción y dosis' no encontrado\n"; }
    }

    if ( ! is_wp_error( $taza_id ) ) {
        $url = wp_get_attachment_image_url( $taza_id, 'large' );
        $new = sc_insert_block_after_heading(
            $content,
            'Pasos para preparar café de filtro',
            $taza_id, $url,
            'Café de filtro listo en taza de vidrio transparente',
            'Un buen filtro es claro, ámbar y transparente — sin turbidez ni sedimento.'
        );
        if ( $new !== $content ) { $content = $new; $changed = true; echo "  [CONTENT] imagen taza insertada\n"; }
        else { echo "  [WARN] heading 'Pasos para preparar café de filtro' no encontrado\n"; }
    }

    if ( $changed ) {
        wp_update_post( [ 'ID' => $pid, 'post_content' => $content ] );
        echo "  [SAVED] post content actualizado\n";
    }
}

// ============================================================
// GUÍA 4 — Qué es el café de especialidad / SCA
// ============================================================
echo "\n== Guía 4: Café de especialidad / SCA ==\n";

$post = get_page_by_path( 'que-es-el-cafe-de-especialidad', OBJECT, 'post' );
if ( ! $post ) {
    echo "ERROR: post 'que-es-el-cafe-de-especialidad' not found\n";
} else {
    $pid = $post->ID;
    echo "Post #{$pid} — {$post->post_title}\n";

    $featured_id = sc_import_image(
        $images_dir . 'guia4-featured.png',
        $pid,
        'Sesión de cata (cupping) de café especialidad — Santo Café',
        'Tazas de cata blancas con granos de café de especialidad, cuchara de cata, mesa de madera',
        'El cupping es el método estándar de evaluación sensorial de la Specialty Coffee Association.'
    );

    $origen_id = sc_import_image(
        $images_dir . 'guia4-origen.png',
        $pid,
        'Cerezas de café maduras en planta — Santo Café',
        'Cerezas rojas y amarillas de café siendo cosechadas a mano en una finca de montaña',
        'El café de especialidad comienza con una cosecha selectiva a mano, eligiendo solo las cerezas maduras.'
    );

    $granos_id = sc_import_image(
        $images_dir . 'guia4-granos.png',
        $pid,
        'Granos de café de especialidad tostados — Santo Café',
        'Macro de granos de café de especialidad tostados sobre plato blanco, tonos castaño y caoba',
        'Los granos de especialidad se evalúan por su color, densidad y ausencia de defectos.'
    );

    if ( ! is_wp_error( $featured_id ) ) {
        set_post_thumbnail( $pid, $featured_id );
        echo "  [FEATURED] set thumbnail → #{$featured_id}\n";
    }

    $content = $post->post_content;
    $changed = false;

    if ( ! is_wp_error( $origen_id ) ) {
        $url = wp_get_attachment_image_url( $origen_id, 'large' );
        $new = sc_insert_block_after_heading(
            $content,
            '¿Qué es el café de especialidad?',
            $origen_id, $url,
            'Cerezas maduras de café siendo cosechadas a mano en una finca de montaña',
            'El café de especialidad comienza con una cosecha selectiva a mano, eligiendo solo las cerezas maduras.'
        );
        if ( $new !== $content ) { $content = $new; $changed = true; echo "  [CONTENT] imagen origen insertada\n"; }
        else { echo "  [WARN] heading '¿Qué es el café de especialidad?' no encontrado\n"; }
    }

    if ( ! is_wp_error( $granos_id ) ) {
        $url = wp_get_attachment_image_url( $granos_id, 'large' );
        $new = sc_insert_block_after_heading(
            $content,
            '¿Qué significa el puntaje SCA?',
            $granos_id, $url,
            'Granos de café de especialidad tostados sobre plato blanco',
            'Los granos de especialidad se evalúan por su color, densidad y ausencia de defectos.'
        );
        if ( $new !== $content ) { $content = $new; $changed = true; echo "  [CONTENT] imagen granos insertada\n"; }
        else { echo "  [WARN] heading '¿Qué significa el puntaje SCA?' no encontrado\n"; }
    }

    if ( $changed ) {
        wp_update_post( [ 'ID' => $pid, 'post_content' => $content ] );
        echo "  [SAVED] post content actualizado\n";
    }
}

// ============================================================
// GUÍA 5 — Café lavado vs natural
// ============================================================
echo "\n== Guía 5: Lavado vs Natural ==\n";

$post = get_page_by_path( 'cafe-lavado-vs-natural', OBJECT, 'post' );
if ( ! $post ) {
    echo "ERROR: post 'cafe-lavado-vs-natural' not found\n";
} else {
    $pid = $post->ID;
    echo "Post #{$pid} — {$post->post_title}\n";

    $featured_id = sc_import_image(
        $images_dir . 'guia5-featured.png',
        $pid,
        'Comparación café lavado vs natural — Santo Café',
        'Dos cuencos con granos verdes de café: izquierda proceso lavado, derecha proceso natural, cereza entre ellos',
        'La diferencia entre proceso lavado y natural define completamente el perfil de sabor en la taza.'
    );

    $natural_id = sc_import_image(
        $images_dir . 'guia5-natural.png',
        $pid,
        'Cerezas de café secándose en camas elevadas — proceso natural',
        'Cerezas de café naranja y rojas secándose al sol en camas elevadas de madera, trabajador al fondo',
        'En el proceso natural, el fruto entero se seca al sol durante semanas antes de despulparlo.'
    );

    $lavado_id = sc_import_image(
        $images_dir . 'guia5-lavado.png',
        $pid,
        'Proceso lavado: granos en canal de agua — Santo Café',
        'Canal de lavado en beneficio de café con granos verdes sumergidos en agua clara, paisaje de montaña al fondo',
        'En el proceso lavado, el mucílago se elimina con agua antes del secado, dando una taza más limpia.'
    );

    if ( ! is_wp_error( $featured_id ) ) {
        set_post_thumbnail( $pid, $featured_id );
        echo "  [FEATURED] set thumbnail → #{$featured_id}\n";
    }

    $content = $post->post_content;
    $changed = false;

    if ( ! is_wp_error( $natural_id ) ) {
        $url = wp_get_attachment_image_url( $natural_id, 'large' );
        $new = sc_insert_block_after_heading(
            $content,
            'Proceso natural: cuerpo alto y notas frutales',
            $natural_id, $url,
            'Cerezas de café secándose en camas elevadas — proceso natural',
            'En el proceso natural, el fruto entero se seca al sol durante semanas antes de despulparlo.'
        );
        if ( $new !== $content ) { $content = $new; $changed = true; echo "  [CONTENT] imagen natural insertada\n"; }
        else { echo "  [WARN] heading 'Proceso natural...' no encontrado\n"; }
    }

    if ( ! is_wp_error( $lavado_id ) ) {
        $url = wp_get_attachment_image_url( $lavado_id, 'large' );
        $new = sc_insert_block_after_heading(
            $content,
            'Proceso lavado: taza limpia y ácida',
            $lavado_id, $url,
            'Canal de lavado de café con granos verdes sumergidos en agua',
            'En el proceso lavado, el mucílago se elimina con agua antes del secado, dando una taza más limpia.'
        );
        if ( $new !== $content ) { $content = $new; $changed = true; echo "  [CONTENT] imagen lavado insertada\n"; }
        else { echo "  [WARN] heading 'Proceso lavado...' no encontrado\n"; }
    }

    if ( $changed ) {
        wp_update_post( [ 'ID' => $pid, 'post_content' => $content ] );
        echo "  [SAVED] post content actualizado\n";
    }
}

echo "\n✅ Importación completada.\n";
echo "Revisá:\n";
echo "  http://santocafe.local/como-preparar-espresso/\n";
echo "  http://santocafe.local/como-preparar-cafe-en-italiana/\n";
echo "  http://santocafe.local/como-preparar-cafe-de-filtro/\n";
echo "  http://santocafe.local/que-es-el-cafe-de-especialidad/\n";
echo "  http://santocafe.local/cafe-lavado-vs-natural/\n";
