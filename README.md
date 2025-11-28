# Sistema Laravel - Gestión de Órdenes de Trabajo

Sistema moderno de gestión empresarial construido con **Laravel 11 + Inertia.js + Vue 3 + Vuetify 3**.

## 🚀 Características

- ✅ **SPA (Single Page Application)** con Inertia.js
- ✅ **Vue 3** con Composition API
- ✅ **Vuetify 3** con Material Design
- ✅ **Modo Oscuro** por defecto (con toggle)
- ✅ **SSR (Server-Side Rendering)** configurado
- ✅ **Responsive Design** para móviles, tablets y desktop
- ✅ **Autenticación** con Laravel
- ✅ **Control de Acceso** basado en roles
- ✅ **PostgreSQL** como base de datos

## 📋 Módulos Implementados

### Frontend (Vue 3 + Vuetify)
- ✅ Dashboard con estadísticas
- ✅ Login
- ✅ Órdenes de Trabajo (Index, Edit)
- ✅ Usuarios (Index)
- ✅ Productos (Index)
- ✅ Cotizaciones (Index)
- ✅ Planes de Pago (Index)

### Pendientes
- ⏳ Formularios de creación/edición para todos los módulos
- ⏳ Servicios (CRUD completo)
- ⏳ Ventas (CRUD completo)
- ⏳ Recibos (CRUD completo)
- ⏳ Controladores de Laravel
- ⏳ Modelos Eloquent

## 🛠️ Tecnologías

- **Backend:** Laravel 11
- **Frontend:** Vue 3 (Composition API)
- **UI Framework:** Vuetify 3
- **SPA Library:** Inertia.js
- **Build Tool:** Vite
- **Database:** PostgreSQL
- **Icons:** Material Design Icons (@mdi/font)
- **SSR:** @vue/server-renderer

## 📦 Instalación Rápida

```bash
# 1. Instalar dependencias
composer install
npm install

# 2. Configurar entorno
copy .env.example .env
php artisan key:generate

# 3. Compilar assets
npm run build

# 4. Ejecutar servidor
php artisan serve
```

**Ver [SETUP_GUIDE.md](SETUP_GUIDE.md) para instrucciones detalladas.**

## 🔧 Desarrollo

**Terminal 1 - Vite:**
```bash
npm run dev
```

**Terminal 2 - Laravel:**
```bash
php artisan serve
```

**Acceder:** http://localhost:8000

## 🗄️ Base de Datos

PostgreSQL en `mail.tecnoweb.org.bo:5432/db_grupo08sa`

## 🔐 Roles

- **Administrador**: Acceso completo
- **Técnico**: Órdenes de trabajo
- **Secretaria**: Ventas y cotizaciones
- **Cliente**: Datos propios

## 📖 Documentación

- **[SETUP_GUIDE.md](SETUP_GUIDE.md)** - Guía completa de configuración
- **[walkthrough.md](.gemini/.../walkthrough.md)** - Documentación del proyecto

## 🚧 Estado

**Versión:** 1.0.0-alpha  
**Frontend:** ✅ Completo  
**Backend:** ⏳ En desarrollo

---

**Grupo 08 SA** - 2025
