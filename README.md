# wp-autosell
Landing de autosell para potenciar las ventas

## 🚀 Despliegue en DigitalOcean App Platform

Este repositorio está configurado para ser desplegado en DigitalOcean App Platform con MySQL externo.

### ⚙️ Optimizado para Recursos Limitados

✅ **Este repositorio está optimizado para funcionar con 512MB de RAM**:
- PHP memory_limit: 96M (permite 4 workers sin agotar memoria)
- WordPress memory limits: 64M normal / 96M admin
- Upload límite: 32M (suficiente para imágenes)
- Health checks tolerantes durante instalación
- Debug habilitado para diagnóstico rápido

📖 **Documentación específica**: [OPTIMIZACION_512MB.md](OPTIMIZACION_512MB.md)

### ⚠️ Cambios Importantes

**Instalación Automática de WordPress**:
- El repositorio NO incluye los directorios de WordPress (`wp-includes/`, `wp-admin/`, `wp-content/`)
- Durante el deploy, el script `build.sh` descarga automáticamente WordPress y configura todo
- Las variables de entorno son **OBLIGATORIAS** para el funcionamiento correcto

### Requisitos previos

- Acceso a un servidor MySQL (puede ser de DigitalOcean Managed Database u otro proveedor)
- Credenciales del MySQL: host, puerto, nombre de base de datos, usuario y contraseña
- Cuenta en DigitalOcean con App Platform habilitado

### Requisitos de PHP

Este proyecto requiere **PHP 8.1+** con las siguientes extensiones (configuradas automáticamente):
- ✅ `mbstring` - Requerido para funciones de cadenas multibyte en WordPress
- ✅ `mysqli` - Conexión a MySQL
- ✅ `curl` - Comunicaciones HTTP
- ✅ `gd` - Procesamiento de imágenes
- ✅ `xml` - Procesamiento XML
- ✅ `zip` - Compresión de archivos
- ✅ `openssl` - Conexiones seguras
- ✅ `json` - Procesamiento JSON

**Nota**: Las extensiones se configuran automáticamente mediante `composer.json`, `.user.ini`, `php.ini`, `.htaccess` y `.do/app.yaml`. No requiere configuración manual.

**Verificación de extensiones**: Durante el build, el script automáticamente verifica que todas las extensiones estén disponibles y lista las extensiones cargadas al final del proceso.

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

- **Recursos Limitados (512MB RAM)**: El repositorio está optimizado para funcionar con recursos mínimos. Si experimentas errores 500, consulta [OPTIMIZACION_512MB.md](OPTIMIZACION_512MB.md)
- **Herramienta de Diagnóstico**: Incluye `phpinfo.php` para diagnosticar problemas. Accede a `/phpinfo.php` después del deploy y **elimínalo después de usar**.
- **Archivos multimedia**: App Platform tiene almacenamiento efímero. Para archivos subidos (imágenes, etc.), considera usar DigitalOcean Spaces con un plugin de WordPress.
- **Base de datos**: Asegúrate de que tu MySQL acepta conexiones desde App Platform.
- El archivo `wp-config.php` lee de variables de entorno, por lo que es seguro comitirlo al repositorio.

### 📚 Documentación

- **[DEPLOYMENT.md](DEPLOYMENT.md)** - Guía completa paso a paso para desplegar
- **[OPTIMIZACION_512MB.md](OPTIMIZACION_512MB.md)** - Optimización para recursos limitados
- **[SOLUCION_RAPIDA.md](SOLUCION_RAPIDA.md)** - Solución rápida a errores comunes
- **[BUILD.md](BUILD.md)** - Documentación técnica del proceso de build

### 🐛 Troubleshooting

#### Error 500 en /wp-admin/install.php

1. Accede a `https://tu-app.ondigitalocean.app/phpinfo.php` para diagnosticar
2. Revisa los Runtime Logs en DigitalOcean (ahora muestran errores PHP)
3. Verifica que todas las variables de entorno estén configuradas
4. Consulta [OPTIMIZACION_512MB.md](OPTIMIZACION_512MB.md) para más detalles

El repositorio ya está optimizado, pero si tienes muchos plugins o tráfico alto, considera:
- Upgrade a plan con 1GB RAM ($12/mes)
- Usar plugin de caché (WP Super Cache)
- Optimizar imágenes antes de subir
