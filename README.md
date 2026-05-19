# Santo Café ☕

Sistema de gestión para **Santo Café Specialty Coffee** — una empresa argentina de venta de café de especialidad.

## Estructura del proyecto

```
santocafe/
├── apps/
│   ├── backoffice/    # Panel de administración interno (Next.js + React)
│   ├── api/           # API REST (NestJS + Node.js)
│   └── web/           # Sitio web público con WooCommerce (futuro)
├── packages/
│   └── shared/        # Tipos TypeScript y utilidades compartidas
├── docs/
│   ├── marca/         # Manual de marca y assets visuales
│   └── specs/         # Especificaciones funcionales del sistema
└── docker-compose.yml # Base de datos MySQL (desarrollo local)
```

## Stack tecnológico

| Capa        | Tecnología                  |
|-------------|------------------------------|
| Frontend    | Next.js 15, React, TypeScript |
| Backend     | NestJS, Node.js, TypeScript  |
| Base de datos | MySQL 8 (local)            |
| Web pública | WooCommerce / WordPress (futuro) |

## Módulos del backoffice

- **Autenticación** — Login passwordless por email (código temporal)
- **Dashboard** — Vistas adaptadas por rol (Admin / Operaciones)
- **Pedidos** — Preparación de órdenes con control de errores
- **Entregas** — Gestión de rutas con integración de mapas
- **Inventario** — Pronóstico de stock con algoritmo de punto de reorden
- **Usuarios** — Alta y gestión de usuarios (solo Admin)

## Roles

| Rol               | Descripción                                      |
|-------------------|--------------------------------------------------|
| Administrador     | Control total de la plataforma                   |
| Gerente de Ops    | Foco en cadena de suministro y logística         |

## Desarrollo local

### Requisitos
- Node.js 18+
- Docker y Docker Compose
- npm o pnpm

### Levantar la base de datos

```bash
docker-compose up -d
```

### Instalar dependencias y correr backoffice

```bash
cd apps/backoffice
npm install
npm run dev
```

### Instalar dependencias y correr API

```bash
cd apps/api
npm install
npm run start:dev
```

## Paleta de colores (marca)

| Nombre         | Hex       | Uso                              |
|----------------|-----------|----------------------------------|
| Terracota/Cobre | `#8C6239` | Acentos y logo principal        |
| Marrón Oscuro  | `#2C1D11` | Texto primario, fondos oscuros   |
| Beige Arena    | `#E6DCC3` | Fondos secundarios               |
| Blanco Crema   | `#FAF7F2` | Fondos de lectura y packaging    |

## Documentación

- [Manual de Marca](docs/marca/)
- [Especificaciones del Backoffice](docs/specs/)
