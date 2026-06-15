<?php
defined( 'ABSPATH' ) || exit;

/**
 * Google Tag Manager — contenedor GTM-5B5VNR7L.
 *
 * El snippet de <script> va lo más arriba posible en <head> (wp_head, prioridad 1)
 * y el <noscript> justo después de la etiqueta <body> (wp_body_open). Solo en el
 * front-end (estos hooks no corren en wp-admin).
 */

const SC_GTM_ID = 'GTM-5B5VNR7L';

// 1) <head> — lo antes posible.
add_action( 'wp_head', function (): void {
    ?>
<!-- Google Tag Manager -->
<script>(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':
new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],
j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src=
'https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);
})(window,document,'script','dataLayer','<?php echo esc_js( SC_GTM_ID ); ?>');</script>
<!-- End Google Tag Manager -->
    <?php
}, 1 );

// 2) Justo después de <body>.
add_action( 'wp_body_open', function (): void {
    ?>
<!-- Google Tag Manager (noscript) -->
<noscript><iframe src="https://www.googletagmanager.com/ns.html?id=<?php echo esc_attr( SC_GTM_ID ); ?>"
height="0" width="0" style="display:none;visibility:hidden"></iframe></noscript>
<!-- End Google Tag Manager (noscript) -->
    <?php
} );
