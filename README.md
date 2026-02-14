# wp-autosell
Landing de autosell para potenciar las ventas

## Despliegue en DigitalOcean App Platform

Este repositorio está configurado para ser desplegado en DigitalOcean App Platform con MySQL externo.

### ⚠️ Cambios Importantes

**Instalación Automática de WordPress**:
- El repositorio NO incluye los directorios de WordPress (`wp-includes/`, `wp-admin/`, `wp-content/`)
- Durante el deploy, el script `build.sh` descarga automáticamente WordPress y configura todo
- Las variables de entorno son **OBLIGATORIAS** para el funcionamiento correcto

### Requisitos previos

- Acceso a un servidor MySQL (puede ser de DigitalOcean Managed Database u otro proveedor)
- Credenciales del MySQL: host, puerto, nombre de base de datos, usuario y contraseña
- Cuenta en DigitalOcean con App Platform habilitado

### Configuración

#### 1. Variables de entorno requeridas

Configura las siguientes variables de entorno en DigitalOcean App Platform (Settings → Environment Variables):

**Variables de Base de Datos (obligatorias):**
- `DB_NAME` - Nombre de la base de datos
- `DB_USER` - Usuario de la base de datos
- `DB_PASSWORD` - Contraseña (marcar como "Encrypt")
- `DB_HOST` - Host del MySQL **incluyendo puerto** (ejemplo: `db-mysql-nyc3-xxxxx.db.ondigitalocean.com:25060`)
- `DB_PREFIX` - Prefijo de tablas (opcional, default: `wp_`)
- `DB_SSL` - Establecer a `REQUIRED` o `true` para DigitalOcean Managed MySQL

**Variables de Seguridad (recomendadas):**
- `AUTH_KEY`, `SECURE_AUTH_KEY`, `LOGGED_IN_KEY`, `NONCE_KEY`
- `AUTH_SALT`, `SECURE_AUTH_SALT`, `LOGGED_IN_SALT`, `NONCE_SALT`

Puedes generar valores únicos en: https://api.wordpress.org/secret-key/1.1/salt/

**Variables de Configuración:**
- `WP_DEBUG` - Establece en `true` para desarrollo, `false` para producción

#### 2. Despliegue

1. Crea una nueva App en DigitalOcean App Platform
2. Conecta este repositorio de GitHub
3. App Platform detectará automáticamente PHP por el archivo `index.php`
4. Configura las variables de entorno según se indica arriba
5. Despliega la aplicación

#### 3. Instalación de WordPress

Una vez desplegada la app:
1. Visita la URL proporcionada por App Platform
2. Completa el instalador de WordPress
3. Accede al panel de administración en `/wp-admin`

### Características de seguridad

- ✅ Credenciales almacenadas como variables de entorno (no en el código)
- ✅ Soporte para HTTPS detrás de proxy (App Platform)
- ✅ Archivo `.gitignore` configurado para evitar commits accidentales de datos sensibles

### Notas importantes

- **Archivos multimedia**: App Platform tiene almacenamiento efímero. Para archivos subidos (imágenes, etc.), considera usar DigitalOcean Spaces con un plugin de WordPress.
- **Base de datos**: Asegúrate de que tu MySQL acepta conexiones desde App Platform.
- El archivo `wp-config.php` lee de variables de entorno, por lo que es seguro comitirlo al repositorio.
