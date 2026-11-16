# EscomEcommerce

Ecommerce pequeño construido como **proyecto de aprendizaje** con **Laravel 13, Livewire 3 y Alpine.js**, documentado paso a paso.

Incluye una **tienda pública** (catálogo, carrito y checkout con pago en Stripe), un **panel de administración** (CRUD de productos y reportes) y la integración de **dos servicios REST externos**: tipo de cambio USD/MXN y pasarela de pago.

> Autor: Escoffie Leví 
> 
> 
> ## Funcionalidades

### Tienda pública
- **Catálogo** con solo productos activos, vigentes y no eliminados, y filtros dinámicos sin recargar: búsqueda, categoría, rango de precio, disponibilidad y orden. Los filtros se guardan en la URL.
- **Precios en MXN y USD**: el USD se calcula con el tipo de cambio del día (cacheado en Redis). El selector de moneda se recuerda en el navegador.
- **Detalle de producto** con SKU, imagen, descripciones, precios y selector de cantidad que respeta el stock.
- **Carrito** en panel lateral: agregar, cambiar cantidades (con tope de stock) y eliminar productos.
- **Checkout / cotización**: productos, cantidades, subtotal, **IVA 16 %** y total en **MXN y USD**, y el correo del comprador antes de generar el pedido.
- **Pago** con tres modos intercambiables: simulador (`mock`), Stripe Checkout (`stripe`) o tarjeta dentro del sitio con Stripe Payment Element (`stripe_card`).
- **Clientes**: compra como invitado o con inicio de sesión con **Google** (la cuenta se crea sola), y la página **"Mis pedidos"**.

### Panel de administración
- **Login** solo para administradores (correo y contraseña), con límite de intentos.
- **CRUD de productos** en una sola pantalla: listado con búsqueda y paginación en tiempo real, alta y edición en modal con **validaciones en tiempo real**, activar/inactivar y **eliminación lógica** (*soft delete*).
- **Reportes**: pedidos realizados con **filtro por rango de fechas**, **gráfica de ventas por periodo** (día, semana o mes), **Top 3 de productos más vendidos** e indicadores (ventas, ticket promedio, unidades).
- Rutas protegidas con los middleware `auth` y `admin`, que también protegen las acciones de Livewire.
