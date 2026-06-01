# Especificaciones Web — SantoCafé
> Fuente: Google Drive · Última actualización: junio 2026

## 1. Información General

| Campo | Valor |
|---|---|
| **URL local** | http://santocafe.local/ |
| **CMS** | WordPress + WooCommerce |
| **Tipo** | E-commerce de café de especialidad |
| **País** | Chile |
| **Idioma** | Español (Chile) |
| **Moneda** | Peso chileno (CLP, formato $XX.000) |

---

## 2. Identidad de Marca

| Campo | Valor |
|---|---|
| **Nombre** | Santo Café |
| **Tagline** | "Un buen día es un buen café." |
| **Propuesta de valor** | "Café de especialidad, del origen a tu taza." |
| **Descripción corta** | "Los mejores cafés del mundo, 100% natural de origen. Envío gratis desde $50.000." |
| **Slogan footer** | "Un buen día es un buen café." |

---

## 3. Diseño

### 3.1 Paleta de Colores

| Variable CSS | Valor | Uso |
|---|---|---|
| `--bg-crema` | `#fcfaf7` | Fondo principal del cuerpo |
| `--text-oscuro` | `#1a1310` | Color de texto principal |
| `--dorado` | `#c5a059` | Acento principal (títulos, highlights) |
| `--dorado-hover` | `#b08d4a` | Hover del color dorado |
| `--tarjeta-bg` | `#ffffff` | Fondo de tarjetas de producto |
| `--borde-tarjeta` | `#e5e5e5` | Borde de tarjetas |
| Header bg | `#1a1310` | Fondo del header (oscuro café) |
| Botón primario bg | `#dfb33e` | Fondo botones principales |
| Botón primario color | `#1a1310` | Texto de botones |

> **Nota:** La paleta del backoffice (terracota `#8C6239`, marrón `#2C1D11`) es diferente a la de la web pública. La web usa dorado/oscuro; el backoffice usa terracota/beige.

### 3.2 Tipografía

| Rol | Fuente | Peso |
|---|---|---|
| Headings (H1–H3) | Playfair Display, serif | Variable |
| Cuerpo / UI | Inter, sans-serif | 400/500/600 |
| Base font size | 16px | — |

### 3.3 Formas y Bordes

- Botones: `border-radius: 30px` (píldora)
- Tarjetas: bordes redondeados suaves

### 3.4 Breakpoints

| Nombre | Ancho |
|---|---|
| Mobile | ≤ 600px |
| Tablet | 600px – 1024px |
| Desktop | ≥ 1024px |
| Máx. contenido | 1400px |

---

## 4. Estructura de Páginas

```
/ (Homepage)
├── Banner superior de envío gratis
├── Header fijo
├── Section: Hero
├── Section: Features (4 íconos)
├── Section: Catálogo de productos ("Nuestros Cafés")
└── Footer

/producto/[slug]/   → Detalle de producto
/carrito/           → Carrito
/pago/              → Checkout
/cuenta/            → Mi Cuenta
#nosotros           → Anchor a sección en home
#contacto           → Anchor a sección en home
```

---

## 5. Header

**Estructura:** Fijo (`position: fixed`), ~118px en desktop. Dos capas:

1. **Barra superior dorada** — "Te faltan $XX.XXX para envío gratis dentro de Chile." (dinámico, con botón ✕)
2. **Barra de nav principal** (fondo `#1a1310`):
   - Izquierda: Logo
   - Centro: Menú (Tienda · #nosotros · #contacto)
   - Derecha: Mi Cuenta (👤) + Carrito (🛒) con badge

**Mobile (≤ 1024px):** Hamburger (☰) + logo centrado + carrito. Drawer lateral desde la izquierda, fondo oscuro.

---

## 6. Hero Section

- Pantalla completa (100vh), imagen de fondo con overlay oscuro
- Título H1 (Playfair Display): "Un buen día" (blanco) / "es un buen café." (dorado)
- Subtítulo: "Café de especialidad, del origen a tu taza."
- CTA: "Comprar café →" (botón dorado sólido, píldora) → ancla a #catalogo
- Indicador de scroll animado al pie

---

## 7. Sección Features

Grid 1×4 desktop / 2×2 tablet / carousel mobile. Fondo `#1a1310`.

| Ícono | Título | Descripción |
|---|---|---|
| 🔥 | Tostado en pequeños lotes | Máxima frescura y perfiles definidos |
| 🚚 | Envíos Gratis | 24-48h desde $50.000 a todo el país |
| 🏅 | SCA 82 a 92 Puntos | Evaluados internacionalmente |
| ❤️ | Pasión Auténtica | Compromiso con la calidad |

---

## 8. Tarjeta de Producto

**Zona imagen:**
- Badge país/SCA (esquina sup. izq.): `🇧🇷 SCA 86`
- Botón ⓘ (esquina sup. der.)
- Badge "¡OFERTA!" (diagonal, dorado)

**Zona contenido:**
- Nombre (H3, Playfair Display, link)
- Notas de cata (truncadas)
- Barra de intensidad (5 segmentos, activos en naranja/dorado)
- Selector precio/formato: 250g | 1kg (pill, negro/seleccionado vs gris)
- "Molienda: Grano · editar en carrito"
- Precio activo en grande
- Botón "🛒 Añadir" (dorado, píldora)

---

## 9. Modal Informativo (ⓘ)

Overlay centrado al hacer click en ⓘ de la tarjeta.

**Zona superior (fondo claro):** Imagen + badge SCA + botón ✕  
**Zona inferior (fondo `#1a1310`):**
- Nombre + bandera + notas de cata
- 3 cards técnicas: ALTITUD · PROCESO · NOTAS
- Barras de perfil: Intensidad · Acidez · Cuerpo
- Selector formato (250g / 1kg)
- Precio en dorado + botón "Ficha" (outline) + botón "🛒 Añadir" (sólido)

---

## 10. Página de Detalle del Producto

URL: `/producto/[slug]/`

**Layout 2 columnas (desktop):**
- Izquierda: imagen grande + zoom
- Derecha: H1 · precio dorado · precio/taza · notas · badges · grid técnico

**Selectores:**
- FORMATO: 250g (~30 tazas) | 1kg (~120 tazas)
- MOLIENDA: ⊕ En Grano | ☕ Espresso | ⊟ Italiana | ∇ Filtro
- CANTIDAD: 1-20

**CTA:** "🛒 Agregar al carrito — $XXXXX" (ancho completo, dorado, píldora)

**Secciones adicionales:** Ficha del origen · tabla de datos · productos relacionados (3)

---

## 11. Carrito (/carrito/)

Por ítem: miniatura · nombre · precio · selector molienda inline · controles cantidad · precio total · eliminar  
Resumen: subtotal · envío ($5.000 Región Metropolitana) · IVA incluido · total  
CTA: "Finalizar compra →" (dorado, ancho completo)

---

## 12. Footer

Fondo `#1a1310`. Grid 4 columnas:

- **Col 1:** Logo + descripción + redes (Facebook, Instagram)
- **Col 2 — Tienda:** Nuestros Cafés · Blends · Especialidad de Origen
- **Col 3 — Preparación:** Espresso · Italiana · Filtro · Arábica · Ecológico
- **Col 4 — Empresa/Legal:** Nosotros · Contacto · Aviso legal · Privacidad · Cookies · Condiciones

Barra inferior: "© 2026 Santo Café · Café de especialidad en gran." | "Un buen día, un buen café." (dorado)

---

## 13. Notas de Implementación

1. **CMS:** WordPress + WooCommerce. Productos con atributos `molienda` (Grano/Espresso/Italiana/Filtro) y `peso` (250g/1kg).
2. **Precio 1kg:** ≈ precio_250g × 3.8
3. **Precio/taza:** 250g ÷ ~30 tazas | 1kg ÷ ~120 tazas
4. **SCA:** Atributo personalizado por producto
5. **Barras de perfil:** Atributos numéricos X/5 renderizados con CSS segmentado
6. **Banner envío gratis:** Dinámico — monto mínimo configurado en la BD (*pendiente definir*)
7. **Flat rate envío:** $5.000 (Región Metropolitana)
8. **IVA:** Incluido en total, mostrado como línea separada
9. **Integración de pago:** Flow (compatible con SII Chile)
