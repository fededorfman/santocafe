# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

Santo Café is a specialty coffee management system for an Argentine company. It's a monorepo with three apps:
- `apps/backoffice` — Internal admin panel (Next.js 15, React 19, TypeScript, Tailwind CSS 4)
- `apps/api` — REST API (NestJS 11, TypeScript)
- `apps/web` — Public website with WooCommerce (future)
- `packages/shared` — Shared TypeScript types and utilities (not yet implemented)

## Branch Strategy

| Branch | Purpose |
|--------|---------|
| `main` | Source of truth — base for all long-lived branches |
| `prod` | Production-ready code only |
| `qa` | Staging / QA testing |
| `dev` | **Active development — always work here** |

**Flow:** feature branches off `dev` → PR into `dev` → promote to `qa` → promote to `prod`.  
Never commit directly to `prod` or `qa`.

## Development Commands

**Start everything (order matters):**
```bash
docker-compose up -d                    # MySQL 8 on :3306, Adminer on :8080
cd apps/api && npm run start:dev        # API on :3001
cd apps/backoffice && npm run dev       # Backoffice on :3000
```

**Backoffice (`apps/backoffice`):**
```bash
npm run dev       # Development server
npm run build     # Production build
npm run lint      # ESLint check
```

**API (`apps/api`):**
```bash
npm run start:dev    # Watch mode
npm run build        # Compile TypeScript
npm run lint         # ESLint with fix
npm run format       # Prettier
npm test             # Unit tests (Jest)
npm run test:watch   # Watch mode
npm run test:cov     # Coverage
npm run test:e2e     # E2E tests
```

## Architecture

**API:** NestJS with global prefix `/api/v1`, CORS enabled for backoffice. ValidationPipe with whitelist + auto-transform. Port 3001 (configured via `API_PORT` env var). Configured in `apps/api/src/main.ts`.

**Backoffice:** Next.js 15 App Router. Route groups: `(auth)` for login, `(dashboard)` for protected routes. Path alias `@/*` maps to project root. TypeScript strict mode.

**Database:** MySQL 8 via Docker Compose. Init scripts in `apps/api/db/init/`. Adminer available at `:8080`.

## Important: Next.js 15 Breaking Changes

This project uses Next.js 15 with React 19. APIs, conventions, and file structure **differ significantly** from earlier versions. Before writing any Next.js code, check `node_modules/next/dist/docs/` for the relevant guide. Heed deprecation notices.

## Environment Variables

Copy `.env.example` to `.env` before starting. Key variables:
- `API_PORT` — API port (default 3001)
- `BACKOFFICE_URL` — Used for CORS configuration in the API
- `NEXT_PUBLIC_API_URL` — API base URL for the frontend
- `JWT_SECRET` — Auth secret
- `SMTP_*` — Email config for passwordless auth

## Brand Colors

| Name | Hex | Usage |
|------|-----|-------|
| Terracota/Cobre | `#8C6239` | Accents, primary logo |
| Marrón Oscuro | `#2C1D11` | Primary text, dark backgrounds |
| Beige Arena | `#E6DCC3` | Secondary backgrounds |
| Blanco Crema | `#FAF7F2` | Reading backgrounds |

## Web Pública (apps/web)

WordPress + WooCommerce. País: Chile. Moneda: CLP.  
Spec completa: `docs/web/especificaciones-web.md`  
Notas operativas y pendientes: `docs/web/notas-operativas.md`

Paleta propia (distinta al backoffice): dorado `#dfb33e`, oscuro `#1a1310`, crema `#fcfaf7`.  
Tipografía: Playfair Display (headings) + Inter (cuerpo).  
Pasarela de pago: Flow (integración con SII Chile).

## Productos Reales

8 cafés de especialidad. Catálogo completo en `docs/productos/catalogo.csv`.

| SKU | Nombre | Origen | SCA | Proceso |
|-----|--------|--------|-----|---------|
| 1 | Macondo | Colombia | 84 | Lavado |
| 2 | Camino Inca | Perú | 83 | Lavado |
| 3 | Cielo Andino | Colombia | 85 | Lavado y Fermentado |
| 4 | Santo Yungas | Bolivia | 84 | Lavado |
| 5 | Santo Ouro Doce | Brasil | 83 | Natural |
| 6 | Santo Sereno | Guatemala | 85 | Lavado |
| 7 | Santo Equilibrio | Colombia | 84 | Lavado |
| 8 | Los Santos | Costa Rica | 84.75 | Lavado |

Presentaciones: 250gr y 1kg. Moliendas: Grano, Espresso, Italiana, Filtro.

## Logo

`assets/logos/logo.png` — fuente. Copiado a `apps/backoffice/public/logo.png`.

## Planned Modules

Auth (passwordless email), Dashboard (role-based: Admin / Ops Manager), Pedidos, Entregas (maps), Inventario (reorder point algorithm), Usuarios. Roles: Administrador (full control), Gerente de Ops (supply chain & logistics).
