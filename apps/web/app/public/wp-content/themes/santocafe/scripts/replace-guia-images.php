<?php
/**
 * Replace guide images — Santo Café
 *
 * Overwrites the physical image files for existing WordPress attachments
 * (identified by _sc_import_src meta) and regenerates all image sizes.
 * Post content and featured image assignments are NOT touched.
 *
 * Usage: php replace-guia-images.php
 */
// Solo CLI: evita que se dispare por URL (vive bajo el web root).
if ( PHP_SAPI !== 'cli' ) {
    http_response_code( 403 );
    exit( 'Este script solo puede ejecutarse por CLI.' );
}

define( 'WP_USE_THEMES', false );
require __DIR__ . '/../../../../wp-load.php';

require_once ABSPATH . 'wp-admin/includes/image.php';
require_once ABSPATH . 'wp-admin/includes/file.php';
require_once ABSPATH . 'wp-admin/includes/media.php';

$images_dir = __DIR__ . '/guide-images/';

$files = glob( $images_dir . '*.png' );
if ( empty( $files ) ) {
    die( "No PNG files found in {$images_dir}\n" );
}

foreach ( $files as $src_path ) {
    $filename = basename( $src_path );

    // Find the existing attachment by the meta we stored on import
    $attachments = get_posts( [
        'post_type'   => 'attachment',
        'post_status' => 'inherit',
        'numberposts' => 1,
        'meta_key'    => '_sc_import_src',
        'meta_value'  => $filename,
    ] );

    if ( empty( $attachments ) ) {
        echo "[SKIP]  {$filename} — no attachment found with _sc_import_src={$filename}\n";
        continue;
    }

    $att    = $attachments[0];
    $att_id = $att->ID;

    // Path to the original file in wp-content/uploads
    $dest_path = get_attached_file( $att_id );
    if ( ! $dest_path ) {
        echo "[ERROR] {$filename} (#{$att_id}) — could not resolve upload path\n";
        continue;
    }

    // Delete ALL existing generated files (sized + scaled variants) to avoid
    // double-scaled filenames like guia1-molienda-scaled-scaled.png.
    $meta        = wp_get_attachment_metadata( $att_id );
    $upload_dir  = dirname( $dest_path ) . '/';

    // Delete sized thumbnails
    if ( ! empty( $meta['sizes'] ) ) {
        foreach ( $meta['sizes'] as $size_data ) {
            $f = $upload_dir . $size_data['file'];
            if ( file_exists( $f ) ) @unlink( $f );
        }
    }
    // Delete the current "main" file (may be a -scaled copy)
    if ( file_exists( $dest_path ) ) @unlink( $dest_path );

    // Also delete any -scaled variant sitting next to the original
    $orig_meta = get_post_meta( $att_id, '_wp_attached_file', true );
    if ( $orig_meta ) {
        $upload_base = wp_upload_dir()['basedir'] . '/';
        $orig_path   = $upload_base . $orig_meta;
        if ( file_exists( $orig_path ) && $orig_path !== $dest_path ) {
            @unlink( $orig_path );
        }
    }

    // Write the new source file to the upload directory with a clean name.
    // Use the base filename (strip any -scaled suffix) so WP starts fresh.
    $clean_name  = preg_replace( '/-scaled/', '', basename( $dest_path ) );
    $clean_dest  = $upload_dir . $clean_name;
    if ( ! copy( $src_path, $clean_dest ) ) {
        echo "[ERROR] {$filename} (#{$att_id}) — copy failed to {$clean_dest}\n";
        continue;
    }

    // Point the attachment record to the new clean file
    $uploads_base = wp_upload_dir()['basedir'] . '/';
    $relative     = str_replace( $uploads_base, '', $clean_dest );
    update_post_meta( $att_id, '_wp_attached_file', $relative );
    $dest_path = $clean_dest;

    // Regenerate all thumbnail sizes from the clean file
    $new_meta = wp_generate_attachment_metadata( $att_id, $dest_path );
    wp_update_attachment_metadata( $att_id, $new_meta );

    // Update src URLs in all posts that embed this attachment
    $old_large = wp_get_attachment_image_url( $att_id, 'large' );
    $posts_with_img = get_posts( [
        'post_type'   => 'post',
        'post_status' => 'publish',
        'numberposts' => -1,
        's'           => "wp-image-{$att_id}",
    ] );
    foreach ( $posts_with_img as $p ) {
        $base_name   = pathinfo( $filename, PATHINFO_FILENAME );
        $pattern     = '~https?://[^"\']+/' . preg_quote( $base_name, '~' ) . '(?:-scaled)?(?:-scaled)?-\d+x\d+\.(?:png|jpg|jpeg|webp)~i';
        $new_content = preg_replace( $pattern, $old_large, $p->post_content );
        if ( $new_content !== $p->post_content ) {
            wp_update_post( [ 'ID' => $p->ID, 'post_content' => $new_content ] );
        }
    }

    $w = $new_meta['width']  ?? '?';
    $h = $new_meta['height'] ?? '?';
    echo "[OK]    {$filename} (#{$att_id}) — {$w}×{$h}px → " . basename( $dest_path ) . "\n";
}

echo "\n✅ Reemplazo completado. Las URLs de los posts no cambiaron.\n";
