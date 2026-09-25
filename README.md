# 🛡️ Help Desk IT - Enterprise Incident Management System

Un sistema moderno, robusto y elegante de gestión de tickets e incidentes de TI desarrollado con **Laravel**, **PostgreSQL**, **Docker** y un frontend SPA nativo (Vanilla JS/CSS) diseñado con una estética futurista **Dark Glassmorphism**.

---

## 🌟 Características Principales

- 🔐 **Autenticación y Roles (Sanctum):** Control de acceso basado en roles (`admin`, `tecnico`, `usuario`).
- 🎫 **Gestión Completa de Incidentes (Tickets):** Creación, asignación a técnicos, cambio de estados en tiempo real, priorización y categorización.
- ⏱️ **Cálculo Automático de SLAs:** Medición y seguimiento de tiempos de vencimiento según la gravedad del incidente (Baja, Media, Alta, Crítica).
- 💬 **Colaboración e Historial:** Hilo interactivo de comentarios por ticket y registro auditado de cambios de estado.
- 📊 **Dashboard Ejecutivo & Métricas:** Indicadores clave de rendimiento (KPIs), resolución media y volumen de incidentes.
- 🎨 **Interfaz SPA Futurista:** Experiencia de usuario ultra rápida sin recarga de página, con animaciones fluidas, modo oscuro y vidrio pulido.
- 🐳 **Totalmente Dockerizado:** Entorno isolado con PHP 8.2-FPM, Nginx y PostgreSQL listo para ejecutar en un solo comando.

---

## 🛠️ Tecnología y Arquitectura

- **Backend:** Laravel 10 / PHP 8.2 (REST API, Eloquent ORM, Policies, Services Pattern)
- **Base de Datos:** PostgreSQL
- **Frontend:** Vanilla JavaScript (SPA hash-routing) + Custom CSS (Glassmorphism & CSS Variables)
- **Infraestructura:** Docker & Docker Compose (Nginx, PHP-FPM, PostgreSQL)
- **Testing:** PHPUnit / Laravel Feature Tests

---

## 🚀 Guía de Instalación y Despliegue

### Prerrequisitos
- [Docker Desktop](https://www.docker.com/products/docker-desktop/) instalado y ejecutándose.
- Git.

### Pasos de Inicio Rápido

1. **Clonar el repositorio:**
   ```bash
   git clone https://github.com/KevinShalom/HelpDesk-IT.git
   cd HelpDesk-IT
   ```

2. **Configurar variables de entorno:**
   Copia el archivo de ejemplo `.env.example` a `.env`:
   ```bash
   cp .env.example .env
   ```

3. **Levantar los contenedores de Docker:**
   ```bash
   docker-compose up -d --build
   ```

4. **Instalar dependencias de Composer:**
   ```bash
   docker-compose exec app composer install
   ```

5. **Generar la clave de la aplicación:**
   ```bash
   docker-compose exec app php artisan key:generate
   ```

6. **Ejecutar migraciones y datos de prueba (Seeders):**
   ```bash
   docker-compose exec app php artisan migrate:fresh --seed
   ```

7. **¡Listo! Accede a la aplicación:**
   Abre tu navegador en: [http://localhost:8080](http://localhost:8080)

---

## 🔑 Cuentas de Prueba Pre-configuradas

El seeder inicial genera usuarios para probar los distintos niveles de permiso (contraseña estándar: `password123`):

| Rol | Correo Electrónico | Contraseña | Permisos |
| :--- | :--- | :--- | :--- |
| **Administrador** | `admin@helpdesk.local` | `password123` | Control total, asignación de cualquier ticket y acceso a métricas. |
| **Técnico** | `tecnico@helpdesk.local` | `password123` | Gestión de tickets asignados, cambio de estados y respuesta. |
| **Usuario / Empleado** | `usuario@helpdesk.local` | `password123` | Creación de tickets propios y consulta de estado. |

---

## 🧪 Ejecución de Pruebas Automatizadas

Para validar que todos los endpoints y reglas de negocio funcionan correctamente:

```bash
docker-compose exec app php artisan test
```

---

## 📄 Licencia

Este proyecto es de código abierto bajo la licencia [MIT](LICENSE).
