# SysDocentes - Sistema de Gestión Docente

Sistema web para la gestión de docentes, horarios, asistencias y mantenimiento académico desarrollado con **Laravel 11**, **Inertia.js**, **Vue 3** y **PostgreSQL**.

## 🚀 Características

- ✅ Gestión completa de docentes
- ✅ Sistema de horarios (regulares y especiales)
- ✅ Control de asistencias
- ✅ Módulos de mantenimiento (áreas, grados, secciones, aulas, cursos)
- ✅ Sistema de roles y permisos
- ✅ Interfaz moderna con Vue 3 + Inertia.js
- ✅ Base de datos PostgreSQL con enums
- ✅ Factories para data de prueba

## 📋 Requisitos del Sistema

- **PHP**: 8.2 o superior
- **Composer**: 2.x
- **Node.js**: 18.x o superior
- **NPM**: 9.x o superior
- **PostgreSQL**: 13.x o superior
- **Git**

## 🛠️ Instalación

### 1. Clonar el repositorio

```bash
git clone <url-del-repositorio>
cd sysdocentes
```

### 2. Instalar dependencias de PHP

```bash
composer install
```

### 3. Instalar dependencias de Node.js

```bash
npm install
```

### 4. Configurar el archivo de entorno

Copia el archivo `.env.example` a `.env`:

```bash
cp .env.example .env
```

### 5. Configurar la base de datos

Edita el archivo `.env` con tus configuraciones de PostgreSQL:

```env
# Configuración de la aplicación
APP_NAME="SysDocentes"
APP_ENV=local
APP_KEY=
APP_DEBUG=true
APP_TIMEZONE=America/Lima
APP_URL=http://localhost:8000

# Configuración de la base de datos
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=sysdocentes
DB_USERNAME=tu_usuario_postgres
DB_PASSWORD=tu_password_postgres

# Configuración de cache y sesiones
CACHE_DRIVER=file
QUEUE_CONNECTION=sync
SESSION_DRIVER=file
SESSION_LIFETIME=120

# Configuración de mail (opcional)
MAIL_MAILER=log
MAIL_HOST=127.0.0.1
MAIL_PORT=2525
MAIL_USERNAME=null
MAIL_PASSWORD=null
MAIL_ENCRYPTION=null
MAIL_FROM_ADDRESS="hello@example.com"
MAIL_FROM_NAME="${APP_NAME}"

# Configuración de Inertia.js
VITE_APP_NAME="${APP_NAME}"
```

### 6. Generar la clave de la aplicación

```bash
php artisan key:generate
```

### 7. Crear la base de datos

Asegúrate de crear la base de datos en PostgreSQL:

```sql
CREATE DATABASE sysdocentes;
```

### 8. Ejecutar las migraciones

```bash
php artisan migrate
```

### 9. Ejecutar los seeders básicos

```bash
php artisan db:seed
```

Esto creará:
- Usuario administrador: `admin@gmail.com` / `123456`
- Roles y permisos básicos
- Módulos del sistema

### 10. Compilar los assets

```bash
npm run build
```

### 11. Iniciar el servidor de desarrollo

```bash
php artisan serve
```

El proyecto estará disponible en: `http://localhost:8000`

## 📊 Generar Data de Prueba

### Opción 1: Data completa con TestDataSeeder (Recomendado)

```bash
php artisan db:seed --class=TestDataSeeder
```

Esto generará:
- **195 Áreas académicas**
- **196 Grados** (Primaria y Secundaria)
- **211 Secciones**
- **205 Aulas** (regulares, laboratorios, etc.)
- **227 Cursos**
- **210 Docentes**
- **185 Horarios** (regulares y especiales)

### Opción 2: Data específica con factories

```bash
# Crear solo áreas
php artisan tinker
>>> App\Models\Area::factory(10)->create()

# Crear docentes con horarios
>>> $docente = App\Models\Docente::factory()->create()
>>> App\Models\Horario::factory(3)->create(['id_docente' => $docente->id])

# Crear horarios específicos
>>> App\Models\Horario::factory(50)->regular()->create()
>>> App\Models\Horario::factory(10)->especial()->create()
```

## 🔧 Comandos Útiles

### Desarrollo

```bash
# Iniciar servidor de desarrollo
php artisan serve

# Compilar assets en modo desarrollo
npm run dev

# Compilar assets para producción
npm run build

# Ejecutar tests
php artisan test
```

### Base de datos

```bash
# Crear nueva migración
php artisan make:migration create_tabla_ejemplo_table

# Ejecutar migraciones
php artisan migrate

# Revertir última migración
php artisan migrate:rollback

# Refrescar base de datos (drop all + migrate + seed)
php artisan migrate:fresh --seed

# Crear nuevo factory
php artisan make:factory NombreFactory

# Crear nuevo seeder
php artisan make:seeder NombreSeeder
```

### Cache y optimización

```bash
# Limpiar cache
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear

# Optimizar para producción
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

## 📁 Estructura del Proyecto

```
sysdocentes/
├── app/
│   ├── Http/
│   │   ├── Controllers/     # Controladores
│   │   ├── Middleware/      # Middlewares
│   │   └── Requests/        # Form requests
│   ├── Models/              # Modelos Eloquent
│   └── Providers/           # Service providers
├── database/
│   ├── factories/           # Factories para testing
│   ├── migrations/          # Migraciones de BD
│   └── seeders/             # Seeders para data inicial
├── public/                  # Assets públicos
├── resources/
│   ├── css/                 # Estilos CSS
│   ├── js/                  # Código Vue.js + Inertia
│   │   ├── components/      # Componentes Vue
│   │   ├── layouts/         # Layouts de la aplicación
│   │   └── pages/           # Páginas Vue
│   └── views/               # Vistas Blade
├── routes/                  # Definición de rutas
├── storage/                 # Archivos temporales
├── tests/                   # Tests
├── .env.example             # Archivo de configuración ejemplo
├── artisan                  # Consola de comandos de Laravel
├── composer.json            # Dependencias PHP
├── package.json             # Dependencias Node.js
└── vite.config.js           # Configuración de Vite
```

## 🔐 Usuarios por Defecto

Después de ejecutar los seeders básicos, tendrás estos usuarios:

| Email | Password | Rol |
|-------|----------|-----|
| `admin@gmail.com` | `123456` | ADMINISTRADOR |
| `admin1@gmail.com` | `123456` | ADMINISTRADOR |
| ... | ... | ADMINISTRADOR |

## 📚 Tecnologías Utilizadas

### Backend
- **Laravel 11**: Framework PHP
- **PostgreSQL**: Base de datos
- **Spatie Laravel Permission**: Sistema de roles y permisos
- **Inertia.js**: Comunicación entre Laravel y Vue

### Frontend
- **Vue 3**: Framework JavaScript
- **Inertia.js**: SPA sin JavaScript adicional
- **Tailwind CSS**: Framework CSS
- **Lucide Vue**: Iconos
- **Vite**: Bundler y dev server

### DevOps
- **Composer**: Gestor de dependencias PHP
- **NPM**: Gestor de dependencias Node.js
- **Git**: Control de versiones

## 🐛 Solución de Problemas

### Error de conexión a PostgreSQL
```bash
# Verificar que PostgreSQL esté corriendo
sudo systemctl status postgresql

# Verificar credenciales en .env
php artisan config:clear
```

### Error de permisos en storage/
```bash
chmod -R 755 storage/
chmod -R 755 bootstrap/cache/
```

### Error de módulos de Node.js
```bash
rm -rf node_modules package-lock.json
npm install
```

### Error de cache de configuración
```bash
php artisan config:clear
php artisan cache:clear
php artisan view:clear
```

## 🤝 Contribución

1. Fork el proyecto
2. Crea una rama para tu feature (`git checkout -b feature/nueva-funcionalidad`)
3. Commit tus cambios (`git commit -am 'Agrega nueva funcionalidad'`)
4. Push a la rama (`git push origin feature/nueva-funcionalidad`)
5. Abre un Pull Request

## 📄 Licencia

Este proyecto está bajo la Licencia MIT. Ver el archivo `LICENSE` para más detalles.

## 📞 Soporte

Para soporte técnico o preguntas:
- Crear un issue en el repositorio
- Contactar al equipo de desarrollo

---

**Desarrollado con ❤️ para la gestión eficiente de instituciones educativas**