# Backoffice — Especificaciones Funcionales
> Versión 1.0 — Mayo 2026

## Propósito

Centralizar, automatizar y optimizar las operaciones internas de Santo Café Specialty Coffee vinculadas a la venta a través de la plataforma digital.

---

## Roles de usuario

| Rol               | Descripción                                      | Acceso                        |
|-------------------|--------------------------------------------------|-------------------------------|
| Administrador     | Control total de la plataforma                   | Todos los módulos             |
| Gerente de Ops    | Foco en cadena de suministro y logística         | Dashboard, Pedidos, Entregas, Inventario |

> **Control de acceso:** RBAC (Role-Based Access Control). Sin registro público — el alta de usuarios es gestionada exclusivamente por administradores.

---

## Módulos

### 1. Autenticación
- Login passwordless mediante email
- Se envía un código de acceso temporal al email registrado
- Sin contraseñas almacenadas

### 2. Dashboard
- Vistas adaptadas según el rol del usuario
- Resumen de métricas clave operativas

### 3. Gestión de Pedidos
- Preparación de órdenes con diseño orientado a minimizar errores
- Vista detallada por pedido

### 4. Gestión de Entregas
- Administración de rutas de distribución local
- Integración con sistema de mapas

### 5. Inventario
- Pronóstico usando algoritmo de punto de reorden
- Gestión de materias primas y productos terminados

### 6. Gestión de Usuarios *(solo Admin)*
- Alta, baja y modificación de usuarios
- Asignación de roles

---

## Requerimientos técnicos

- **Diseño:** Responsive, optimizado para operaciones móviles en campo
- **Offline:** Capacidad de confirmar entregas sin conexión
- **Idioma:** Español exclusivamente
- **Acceso:** Cerrado, sin registro público

---

## Modelo de datos (tablas principales)

| Tabla          | Descripción                            |
|----------------|----------------------------------------|
| `users`        | Usuarios del sistema con roles         |
| `products`     | Catálogo de productos                  |
| `orders`       | Órdenes de venta                       |
| `order_details`| Detalle de productos por orden         |
| `stock_history`| Historial de movimientos de inventario |
