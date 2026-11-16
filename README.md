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


## Requisitos

| Herramienta | Versión | Para qué |
|---|---|---|
| **PHP** | 8.4 o superior (extensiones `pdo_mysql`, `mbstring`, `bcmath`, `fileinfo`, `curl`) | Laravel 13 |
| **Composer** | 2.x | Dependencias de PHP |
| **Node.js** y **npm** | Node 20 o superior | Vite, Tailwind y Chart.js |
| **MySQL** | 5.7 o superior | Base de datos |
| **Redis** | 6 o superior | Caché del tipo de cambio |
| **Git** | — | Clonar el repositorio |

## Instalación

### 1. Clonar el repositorio
```bash
git clone https://github.com/leshcoff/ecomerce-leviescoffie.git
cd ecomerce-leviescoffie
```

### 2. Instalar dependencias
```bash
composer install
npm install
```

### 3. Crear el archivo de configuración
```bash
cp .env.example .env
php artisan key:generate
```

### 4. Crear la base de datos
```bash
-- crear la base de datos donde va correr el proyecto 
```

### 5. Verificar que Redis esté corriendo
```bash
redis-cli ping     # debe responder: PONG
```
> Si Redis no está disponible, la tienda sigue funcionando, pero muestra los precios **solo en MXN**.

### 6. Crear las tablas y los datos de prueba
```bash
php artisan migrate --seed
php artisan storage:link
```
### Accesos

| Rol | URL | Credenciales |
|---|---|---|
| **Administrador** | http://localhost:8000/login | `admin@correo.com` / `12345678` |
| **Cliente** | http://localhost:8000 → "Entrar con Google" | Tu cuenta de Google (requiere configurar Google; ver la sección de webservices) |
| **Invitado** | http://localhost:8000 | No requiere cuenta: puede comprar indicando su correo en el checkout |


### 7. Levantar el proyecto
En dos terminales:
```bash
# Terminal 1: backend
php artisan serve

# Terminal 2: frontend (Vite)
npm run dev
```
Abre **http://localhost:8000**.


## Configuración y webservices

El proyecto integra **dos servicios REST externos** (tipo de cambio y pagos) y, opcionalmente, **Google** para el login de clientes. Todo se configura en `.env`; después de cualquier cambio corre:

```bash
php artisan config:clear
```

### Resumen de variables

| Variable | Obligatoria | Valor por defecto | Descripción |
|---|---|---|---|
| `TIPO_CAMBIO_URL` | Sí | `https://open.er-api.com/v6/latest/USD` | Endpoint del tipo de cambio |
| `TIPO_CAMBIO_TTL` | Sí | `3600` | Segundos que el tipo de cambio vive en Redis |
| `REDIS_CLIENT` | Sí | `predis` | Cliente de Redis en PHP puro |
| `PAGO_DRIVER` | Sí | `mock` | `mock`, `stripe` o `stripe_card` |
| `STRIPE_KEY` | Solo con Stripe | — | Llave publicable (`pk_test_…`) |
| `STRIPE_SECRET` | Solo con Stripe | — | Llave secreta (`sk_test_…` o `rk_test_…`) |
| `GOOGLE_CLIENT_ID` | Solo para login de clientes | — | ID de cliente OAuth |
| `GOOGLE_CLIENT_SECRET` | Solo para login de clientes | — | Secreto del cliente OAuth |
| `GOOGLE_REDIRECT_URI` | Solo para login de clientes | `${APP_URL}/auth/google/callback` | URL de retorno |


### Webservice 1: tipo de cambio USD/MXN

| | |
|---|---|
| **Servicio** | [ExchangeRate-API, acceso abierto](https://www.exchangerate-api.com/docs/free) (`open.er-api.com`) |
| **Endpoint** | `GET https://open.er-api.com/v6/latest/USD` |
| **Credenciales** | **No requiere** API key |
| **Dato que se usa** | `rates.MXN` de la respuesta JSON |


**Configuración (`.env`):**
```dotenv
TIPO_CAMBIO_URL=https://open.er-api.com/v6/latest/USD
TIPO_CAMBIO_TTL=3600
REDIS_CLIENT=predis
```


### Webservice 2: pagos (Stripe sandbox o simulador)

El modo de pago se elige con `PAGO_DRIVER`:

| `PAGO_DRIVER` | Cómo paga el cliente                                                         | ¿Requiere cuenta? |
|---|------------------------------------------------------------------------------|---|
| `mock` | Formulario  **simulador bancario** (`app/Services/PagoSimuladoService.php`)  | No |
| `stripe` | Se redirige a la página de pago de **Stripe Checkout** y regresa a la tienda | Sí (Stripe en modo prueba) |
| `stripe_card` | Hace el cargo dentro del mismo formulario sin salir de la tienda             | Sí (Stripe en modo prueba) |


#### Cómo obtener las credenciales de prueba de Stripe
1. Crea una cuenta gratuita en **https://dashboard.stripe.com/register**. No necesitas activar pagos reales.
2. Activa el **Modo de prueba** (*Test mode*) arriba a la derecha.
3. Ve a **Developers → API keys**: https://dashboard.stripe.com/test/apikeys
4. Copia la **Publishable key** (`pk_test_…`) y la **Secret key** (`sk_test_…`).

**Configuración (`.env`):**
```dotenv
PAGO_DRIVER=stripe_card        # o stripe
STRIPE_KEY=pk_test_...
STRIPE_SECRET=sk_test_...
```

#### Tarjetas de prueba
**Para hacer las pruebas:

| Número | Resultado |
|---|---|
| `4242 4242 4242 4242` | Aprobada |
| `5555 5555 5555 4444` | Mastercard aprobada |
| `4000 0000 0000 0002` | Rechazada |
| `4000 0000 0000 9995` | Fondos insuficientes |


### Login de clientes con Google (opcional)

Sin esta configuración la tienda funciona igual: los clientes compran como invitados.

#### Cómo obtener las credenciales
1. Entra a **https://console.cloud.google.com** y crea un proyecto.
2. **APIs y servicios → Pantalla de consentimiento de OAuth**: tipo **Externo**, nombre de la app y correo de soporte.
3. En **Usuarios de prueba**, agrega los correos de Gmail que podrán iniciar sesión. Mientras la app esté en modo *Testing*, solo esos correos tienen acceso.
4. **APIs y servicios → Credenciales → Crear credenciales → ID de cliente de OAuth**:
    - Tipo: **Aplicación web**.
    - **URI de redireccionamiento autorizados**: `http://localhost:8000/auth/google/callback`
5. Copia el **ID de cliente** y el **Secreto**.

**Configuración (`.env`):**
```dotenv
GOOGLE_CLIENT_ID=xxxxxxxx.apps.googleusercontent.com
GOOGLE_CLIENT_SECRET=GOCSPX-...
GOOGLE_REDIRECT_URI="${APP_URL}/auth/google/callback"
```

> ⚠️ Google **no acepta** dominios locales como `.test` ni `http` en dominios que no sean `localhost`. Para probar el login usa `http://localhost:8000` (con `php artisan serve`), y que `APP_URL` y la URI registrada en Google coincidan exactamente, incluido el puerto.


