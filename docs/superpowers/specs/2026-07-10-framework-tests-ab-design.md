# Framework genérico de tests A/B (multi-test)

## Contexto y objetivo

Ya existe un primer test A/B (tarjeta compacta vs. tarjeta actual en el catálogo de
la home), implementado en `inc/ab-testing.php` con un panel dedicado en wp-admin
("Test A/B Catálogo") hardcodeado a ese único test, con split fijo 50/50 y sin
forma de activarlo/desactivarlo.

El objetivo de este proyecto es generalizar esa infraestructura para que:

- El panel de wp-admin pase a llamarse **"Tests A/B"** y liste **todos** los tests
  que existan, no solo uno.
- Cada test se pueda **activar/desactivar** con un checkbox, sin borrar código ni
  perder los datos ya juntados.
- El **% de tráfico** que recibe cada variante sea editable desde el panel (no fijo
  en el código), y soporte **más de 2 variantes** (A, B, C, ...).
- El test de la tarjeta de catálogo se migre a este framework nuevo, sin cambiar su
  comportamiento actual para el visitante.

Quién arma los tests nuevos sigue siendo Federico pidiéndolo y Claude escribiendo el
código de cada test (qué variantes existen, dónde se dispara la asignación, qué
cuenta como conversión). Lo que se generaliza es la infraestructura compartida:
selección ponderada, contadores, activo/inactivo y el panel de resultados.

## Alcance

**Incluye:**
- Refactor de `inc/ab-testing.php` en un motor reutilizable (registro de tests,
  asignación ponderada, contadores, panel multi-test).
- Migración del test de tarjeta de catálogo existente a este framework (mismo
  comportamiento visual, nueva forma de almacenamiento interno).
- Panel "Tests A/B" que lista todos los tests registrados, cada uno con: variantes,
  métricas, checkbox de activo/inactivo, % editable por variante, botón de reinicio
  de contadores propio.
- `dataLayer` push generalizado a "todos los tests activos en los que el visitante
  está enrolado", no solo el de la tarjeta.

**Fuera de alcance (explícitamente rechazado en el brainstorming):**
- Constructor de tests sin código (crear un test nuevo desde wp-admin). Los tests
  nuevos los sigue armando Claude en código, a pedido.
- Agregar/quitar variantes de un test existente desde el panel. La cantidad y
  definición de las variantes (qué es cada una, cómo se ve) se define en código; el
  panel solo edita el **%** de las variantes ya definidas.
- Migración de datos de los `wp_options` actuales (`sc_ab_views_control`, etc.) — se
  confirmó que no hay datos reales en producción todavía, así que se renombran
  directamente al nuevo esquema.

## Arquitectura

### Registro de un test (en código)

Cada test se declara una vez, en el momento de cargar el módulo, con una función de
registro:

```php
sc_ab_register_test( 'catalog_card', 'Tarjeta de Catálogo', [
    'control' => [ 'label' => 'Tarjeta actual', 'weight' => 50 ],
    'compact' => [ 'label' => 'Tarjeta chica',  'weight' => 50 ],
] );
```

- `catalog_card`: clave única del test (se usa en cookies, nombres de opciones y en
  el `dataLayer`).
- `'Tarjeta de Catálogo'`: nombre visible en el panel.
- El array de variantes: clave interna → `label` (texto visible) + `weight` (%
  *por defecto*, usado solo si todavía no hay un valor guardado en `wp_options` para
  ese test). Los pesos por defecto de un mismo test deben sumar 100 al registrarlo.

El registro solo guarda la *definición* del test en memoria (clave, nombre,
variantes posibles y su label). El resto de la lógica de ESE test — en qué página
se dispara la asignación, qué hook cuenta como conversión, qué template renderiza
cada variante — la sigue escribiendo Claude como hooks normales de WordPress,
llamando a las funciones genéricas de abajo con la clave del test.

### Funciones genéricas (parametrizadas por clave de test)

- `sc_ab_get_variant( string $test_key ): string` — devuelve la variante asignada al
  visitante actual para ese test. Si el test está inactivo, o el visitante es admin
  con `edit_posts`, devuelve siempre la primera variante registrada (la de
  "control"). Si no hay cookie todavía, devuelve la primera variante también (sin
  asignar ni contar — la asignación real ocurre en `sc_ab_maybe_assign_variant`).
- `sc_ab_maybe_assign_variant( string $test_key ): void` — si el visitante no tiene
  cookie para este test y el test está activo, sortea una variante según los pesos
  efectivos (ver abajo), la guarda en cookie 30 días, y suma 1 al contador de vistas
  de esa variante. Cada test sigue llamando a esta función desde su propio hook (por
  ejemplo, solo en `template_redirect` + `is_front_page()` para el test de la
  tarjeta) — el framework no decide "dónde" se dispara, eso es específico de cada
  test.
- `sc_ab_track_conversion( string $test_key ): void` — si el test está activo y el
  visitante todavía no tiene la cookie de "ya convertido" *para ese test*, suma 1 al
  contador de conversión de su variante y marca la cookie. Cada test decide en qué
  hook llamar a esto (para la tarjeta, sigue siendo `woocommerce_add_to_cart`).

### Selección ponderada

Reemplaza el `wp_rand(0,1)` fijo actual. Dado el test y sus variantes con pesos
efectivos (los guardados en `wp_options` si existen, si no los del registro en
código):

1. Sumar todos los pesos (`$total`).
2. Sortear un número entero `wp_rand(1, $total)`.
3. Recorrer las variantes en orden acumulando pesos hasta que el acumulado sea
   `>=` al número sorteado — esa es la variante elegida.

Esto funciona igual para 2 variantes que para 5, y es tolerante a que los pesos
guardados no sumen exactamente 100 (se normalizan solos al dividir por `$total`
real, no se asume 100).

### Activo / inactivo

- Guardado en `wp_options` como `sc_ab_active_{test_key}` (`'1'`/`'0'`, default
  `'1'` — un test recién registrado en código está activo por defecto).
- Test inactivo = pausado, no borrado: `sc_ab_get_variant()` siempre devuelve la
  primera variante, `sc_ab_maybe_assign_variant()` y `sc_ab_track_conversion()` no
  suman a ningún contador. Los datos ya juntados quedan intactos y visibles en el
  panel. Reactivar el test retoma la asignación normal sin perder histórico.

## Modelo de datos

**`wp_options`** (todas con `autoload = false`, igual que en la implementación
actual):

| Opción | Contenido |
|---|---|
| `sc_ab_active_{test}` | `'1'` o `'0'` |
| `sc_ab_weights_{test}` | array serializado `variante => peso_int`, ausente si nunca se editó desde el panel (se usan los defaults del código) |
| `sc_ab_views_{test}_{variante}` | contador de vistas |
| `sc_ab_conv_{test}_{variante}` | contador de conversiones |

**Cookies** (por test, así un visitante puede estar en varios tests a la vez sin
pisarse entre sí):

| Cookie | Contenido |
|---|---|
| `sc_ab_{test}` | variante asignada a este visitante para este test |
| `sc_ab_conv_{test}` | presente si ya convirtió en este test (dedup) |

Ejemplo concreto para el test actual: `sc_ab_catalog_card`, `sc_ab_conv_catalog_card`,
`sc_ab_views_catalog_card_control`, `sc_ab_views_catalog_card_compact`, etc.

## Migración del test existente

El test de la tarjeta de catálogo se re-registra con `sc_ab_register_test()` usando
la clave `catalog_card` (la misma que ya usa el push a `dataLayer` desde antes), con
sus dos variantes actuales (`control` 50%, `compact` 50%) como default. El
comportamiento para el visitante no cambia en nada — mismo cookie de 30 días, mismos
templates, misma exclusión de admins. Lo único que cambia es el nombre interno de
los `wp_options` (de `sc_ab_views_control` a `sc_ab_views_catalog_card_control`,
etc.) y que ahora aparece dentro del panel multi-test en vez de tener su propia
página dedicada.

## Panel de wp-admin: "Tests A/B"

Nueva página en `WooCommerce > Tests A/B` (slug `sc-ab-tests`, reemplaza a
`sc-ab-catalog`), que recorre todos los tests registrados y muestra una caja por
cada uno:

```
Tarjeta de Catálogo                    [✓] Activo

 Variante         Vistas   Agregaron   Conversión   %
 Tarjeta actual     120       18          15.0%    [50]
 Tarjeta chica      115       22          19.1%    [50]

 [Guardar cambios]              [Reiniciar contadores]
```

- **Checkbox "Activo"** + **% por variante** (`<input type="number" min="0"
  max="100">`) van en un mismo formulario ("Guardar cambios"). Al enviarlo: se
  valida que la suma de los % sea exactamente 100; si no, se rechaza el guardado
  completo (no se aplica ningún cambio) y se muestra un aviso de error explicando
  que deben sumar 100, conservando los valores anteriores. Si suma 100, se guardan
  el estado de activo y los pesos nuevos en sus respectivas opciones.
- **"Reiniciar contadores"** es un formulario aparte, específico de ese test — solo
  borra `sc_ab_views_{test}_*` y `sc_ab_conv_{test}_*` de ese test puntual, no afecta
  a los demás tests de la lista.
- Cada test tiene su propio nonce (`sc_ab_save_{test}`, `sc_ab_reset_{test}`) para no
  mezclar acciones entre tests distintos en la misma pantalla.
- Si no hay ningún test registrado todavía (no debería pasar en la práctica, pero es
  el estado inicial antes de que exista el primero), se muestra un mensaje simple
  tipo "Todavía no hay tests A/B configurados."

## `dataLayer` (GTM) generalizado

El hook `wp_head` que empuja al `dataLayer` deja de estar atado al test de la
tarjeta. En su lugar, recorre **todos los tests registrados y activos**, y por cada
uno en el que el visitante tenga una variante asignada (cookie válida), pushea un
evento:

```js
dataLayer.push({ event: 'sc_ab_ready', ab_test: 'catalog_card', ab_variant: 'compact' });
```

(uno por test — si en el futuro un visitante está enrolado en 2 tests a la vez, se
pushean 2 eventos). Sigue respetando `SC_DISABLE_ANALYTICS` como hoy.

## Testing

- Verificar que `sc_ab_register_test()` con pesos que no suman 100 en el código sea
  detectable (al menos vía comentario/aviso en desarrollo; no es necesario un hard
  error en producción ya que es responsabilidad de quien escribe el test).
- Verificar selección ponderada: con pesos 90/10 sobre una muestra grande de
  sorteos, la proporción observada debe acercarse a 90/10 (no exactamente, pero
  claramente distinta de 50/50).
- Verificar que desactivar un test hace que `sc_ab_get_variant()` devuelva siempre
  la primera variante, y que ni las vistas ni las conversiones sigan sumando,
  incluso para visitantes que ya tenían cookie de una variante distinta a la
  primera.
- Verificar que reactivar un test retoma la asignación normal y que los contadores
  previos siguen intactos.
- Verificar el guardado de %: sumar 100 guarda bien; sumar 90 o 110 rechaza el
  guardado y no modifica `wp_options`.
- Verificar que "Reiniciar contadores" de un test no afecta los contadores de otro
  test (una vez que exista un segundo test para probarlo; con uno solo, verificar
  al menos que no se borra `sc_ab_active_*` ni `sc_ab_weights_*`, solo los
  contadores).
- Verificar en el navegador que el test de la tarjeta de catálogo se sigue viendo
  exactamente igual que antes (control y compact) tras la migración.
- Verificar que el panel "Tests A/B" muestra correctamente el test migrado, con su
  nombre, variantes, checkbox de activo y campos de % editables.
