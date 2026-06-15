# Santo Café — Templates de email

Cada email es un **HTML standalone** (sin CSS externo) listo para pegar en una
herramienta de envío (Mailchimp, Brevo, Klaviyo, Omnisend, MailPoet, etc.).

## Técnicas de compatibilidad aplicadas

- **Layout con tablas** (`role="presentation"`, `cellpadding/cellspacing=0`), no fl/grid.
- **Estilos inline** en celdas + un `<style>` en `<head>` solo para reset, fuentes y media queries.
- **Ancho fijo 600px** centrado, **una sola columna** (se apila bien en mobile).
- **Botones "bulletproof"**: `<a>` estilado + `<v:roundrect>` (VML) para Outlook/Windows.
- **Comentarios condicionales MSO** (`<!--[if mso]>`) para Outlook (fuentes y botones).
- **Preheader oculto** (texto de preview en la bandeja) con relleno invisible.
- **Imágenes**: `display:block`, `border:0`, `width/height`, `alt`, **URLs absolutas**.
- **Dark mode**: `color-scheme` + colores que sobreviven la inversión.
- `x-apple-disable-message-reformatting`, `format-detection`, fuentes web con fallback.

## Paleta y tipografía (igual al sitio)

| | Hex |
|---|---|
| Dorado | `#dfb33e` |
| Dorado oscuro (links/acento) | `#8a6d1f` |
| Oscuro (texto/header) | `#1a1310` |
| Texto cuerpo | `#3a2f27` |
| Texto suave | `#8a7d6b` |
| Crema (fondo claro) | `#fcfaf7` |
| Beige (fondo del email) | `#f3ece1` |

Fuentes: **Bricolage Grotesque** (títulos) + **Hanken Grotesk** (cuerpo) vía Google Fonts,
con fallback a Arial/Helvetica (la mayoría de los clientes usa el fallback, está OK).

## Placeholders a reemplazar

Vienen como `{{...}}` (cambialos por los merge-tags de tu herramienta):

- **Marca/links**: `{{LOGO_URL}}` (logo claro sobre fondo oscuro), `{{SITE_URL}}`,
  `{{SHOP_URL}}`, `{{ACCOUNT_URL}}`, `{{ORDERS_URL}}`, `{{UNSUBSCRIBE_URL}}`,
  `{{PRIVACY_URL}}`, `{{INSTAGRAM_URL}}`.
- **Persona/pedido**: `{{first_name}}`, `{{order_number}}`, `{{order_total}}`,
  `{{order_url}}`, `{{tracking_url}}`, `{{product_name}}`, `{{product_image}}`,
  `{{product_url}}`, `{{coupon_code}}`, `{{cart_url}}`, `{{review_url}}`,
  `{{reset_url}}`, `{{expiry_date}}`.

> El bloque de filas de productos en `03-confirmacion-pedido.html` está marcado
> con `<!-- ITEM:start --> ... <!-- ITEM:end -->` para que lo repitas por ítem.

## Datos legales (footer)

SANTO CAFÉ SPECIALTY COFFEE SPA · RUT 78.245.225-8 · San Pío X 2390 Of 803 ·
santocafespecialtycoffee@gmail.com · +56 9 5141 4791

## Lista

| # | Archivo | Tipo |
|---|---|---|
| 01 | bienvenida.html | Transaccional |
| 02 | recuperar-password.html | Transaccional |
| 03 | confirmacion-pedido.html | Transaccional |
| 04 | pedido-en-camino.html | Transaccional |
| 05 | pedido-entregado.html | Transaccional |
| 06 | pago-pendiente.html | Transaccional |
| 07 | pedido-cancelado.html | Transaccional |
| 08 | reembolso.html | Transaccional |
| 09 | solicitud-resena.html | Post-venta |
| 10 | reposicion.html | Retención |
| 11 | volvio-stock.html | Retención |
| 12 | guia-preparacion.html | Post-venta |
| 13 | reactivacion.html | Retención (win-back) |
| 14 | carrito-abandonado.html | Ciclo de vida |
| 15 | promo-3-meses.html | Ciclo de vida |
| 16 | newsletter.html | Marketing (genérico) |
| 17 | cumpleanos.html | Ciclo de vida |

## Probar antes de enviar

Pasá cada uno por **Litmus** o **Email on Acid** (o el preview de tu herramienta)
para ver Gmail, Apple Mail, Outlook (Win) y mobile. Mandá un test real a vos mismo.
