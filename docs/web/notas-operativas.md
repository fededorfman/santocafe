# Notas Operativas — SantoCafé
> Fuente: Google Drive · Última actualización: junio 2026

## Operaciones

| Tema | Detalle |
|---|---|
| **Moliendas disponibles** | Grano, Espresso, Italiana, Filtro |
| **Presentaciones** | 250gr y 1kg (únicamente) |
| **Entregas** | 24-48 horas hábiles — Santiago y Región Metropolitana |
| **Pasarela de pago** | Flow (integración automática con SII Chile) |
| **Proveedores** | 2 distribuidores · mínimo 6 kg por pedido · entrega en 48hs hábiles |
| **Envío flat rate** | $5.000 (Región Metropolitana) |
| **Envío gratis desde** | $50.000 (*monto definitivo pendiente de confirmar*) |

## Emails Automatizados Planificados

1. Confirmación de compra
2. Carrito abandonado
3. Confirmación de entrega
4. Reactivación (sin compras en 2 meses)
5. Cumpleaños
6. Promociones especiales

## Fechas Clave

- Black Friday, Cyber Monday, Navidad, Día del Padre
- Baja demanda: enero–febrero

## Atributos de Producto en el Sistema

País · Variedad · Altitud · Intensidad · Fecha de tueste · Nivel de tueste · Notas de cata · Proceso · Puntaje SCA

## Planes Futuros

Suscripciones mensuales · Blends propios · Venta B2B · Tostado propio · Cold brew

## Pendientes / Decisiones Abiertas

- [ ] Definir zonas de entrega y precios por zona (fuera de RM)
- [ ] Confirmar monto mínimo para envío gratis
- [ ] Definir política de garantía y devoluciones
- [ ] Cambiar dominio (actual `santocafespecialtycoffee.com` — 28 chars, en inglés)
- [ ] Evaluar pasarelas de pago alternativas a Flow
- [ ] Analizar webs de competidores

## Deploy y configuración de servidor (prod)

Estos pasos son de **infra/hosting** (no van en el tema ni en git). Se hacen una vez
en producción (Hostinger).

### 1. Cron real + apagar el WP-Cron por visitas

WordPress por defecto dispara sus tareas solo cuando alguien visita el sitio. En una
tienda con poco tráfico eso atrasa los emails automáticos y la limpieza de cupones.
Solución:

1. En `wp-config.php` (en `public_html`), antes de `/* That's all, stop editing! */`:

   ```php
   define( 'DISABLE_WP_CRON', true );
   ```

   Apaga el cron por visitas para que no corra dos veces (real + por visita).

2. hPanel → Avanzado → Trabajos cron. Crear uno cada **5 minutos** (`*/5 * * * *`):

   ```
   wget -q -O - "https://santocafe.cl/wp-cron.php?doing_wp_cron" >/dev/null 2>&1
   ```

3. Verificar en WooCommerce → Estado → Acciones programadas (Action Scheduler).

> `wp-config.php` vive en la raíz de WordPress, fuera del tema. No está en git ni en
> el zip (tiene credenciales y es propio de cada entorno), por eso este cambio se hace
> a mano en el servidor. En **local** NO conviene poner `DISABLE_WP_CRON` salvo que se
> arme un cron local: sin cron real, las tareas no correrían. Para probar a mano está
> el botón "Ejecutar escaneo ahora" en el panel de Emails automáticos.

### 2. Entregabilidad de correo (SPF + DKIM + DMARC)

Sin esto, los emails salen como spam (sobre todo de un dominio hacia sí mismo).

- **WP Mail SMTP → Settings → Mailer:** SMTP autenticado de Hostinger
  (`smtp.hostinger.com`, puerto 465 SSL, usuario una casilla real) + **Force From**
  = `no-reply@santocafe.cl`. Eso firma los correos con DKIM. Probar con "Email Test".
- **DNS (hPanel → Zona DNS):**
  - SPF (TXT `@`): `v=spf1 include:_spf.mail.hostinger.com ~all`
  - DKIM: lo genera Hostinger (Emails → dominio → DNS, registros `hostingermail-*._domainkey`).
  - DMARC (TXT `_dmarc`): `v=DMARC1; p=none; rua=mailto:dmarc@santocafe.cl` (luego subir a quarantine/reject).

### 3. Tras cada deploy del tema

1. Purgar **LiteSpeed Cache + OPcache**.
2. Ajustes → Enlaces permanentes → Guardar (regenera rutas de baja, reseña, recompra, llms.txt).
3. Cargar la **URL de reseña en Google** en WooCommerce → Emails automáticos (flujo de 5 estrellas).

### 4. Cómo se evita enviar un email dos veces

Dos capas:

- **Action Scheduler** corre cada tarea una sola vez por intervalo (toma un lock; el
  cron de 5 min solo le da oportunidades de ejecutar, no la corre 288 veces al día).
- **Marca de "ya enviado" por destinatario** en la base, que excluye a quien ya lo
  recibió aunque el job vuelva a correr:
  - Cumpleaños: `_sc_sent_cumpleanos` (uno por año)
  - Reposición / Reseña: `_sc_sent_reposicion_{pedido}` / `_sc_sent_resena_{pedido}` (uno por pedido)
  - Reactivación: `_sc_sent_reactivacion` (timestamp + período de enfriamiento)
  - Carrito abandonado: `_sc_ab_sent_a` (24 h) y `_sc_ab_sent_b` (7 días), por hash del carrito
    (si cambian el carrito vuelve a ser elegible)
