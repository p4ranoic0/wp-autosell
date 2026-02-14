# Solución Implementada: Error 500 en WordPress con 512MB RAM

## 📋 Resumen del Problema

**Tu situación**:
- VPS con 512MB RAM y 1 CPU (recursos limitados)
- WordPress despliega correctamente
- Error 500 al acceder a `/wp-admin/install.php`
- Logs muestran: 4 workers PHP-FPM con 128M cada uno = 512MB solo para PHP

**El problema**: Con 128M por worker × 4 workers = 512MB, no queda memoria para el sistema operativo, Apache/Nginx, o cualquier otro proceso, causando el error 500.

## ✅ Cambios Implementados

### 1. Optimización de Memoria PHP
**Archivos modificados**: `php.ini`, `.user.ini`

```ini
# ANTES
memory_limit = 128M
upload_max_filesize = 64M
post_max_size = 64M

# AHORA
memory_limit = 96M          # 4 workers × 96M = 384MB (deja 128MB libres)
upload_max_filesize = 32M   # Suficiente para imágenes
post_max_size = 32M
```

### 2. Límites de Memoria WordPress
**Archivo modificado**: `wp-config.php`

```php
define( 'WP_MEMORY_LIMIT', '64M' );       // Uso normal de WordPress
define( 'WP_MAX_MEMORY_LIMIT', '96M' );   // Máximo en admin
```

### 3. Debug Habilitado
**Archivos modificados**: `php.ini`, `.user.ini`, `wp-config.php`

Ahora los errores PHP se muestran en Runtime Logs:
```ini
display_errors = On
display_startup_errors = On
error_reporting = E_ALL
log_errors = On
```

```php
define( 'WP_DEBUG_LOG', true );
define( 'WP_DEBUG_DISPLAY', false );
```

### 4. Health Check Mejorado
**Archivo modificado**: `.do/app.yaml`

```yaml
# Más tiempo para iniciar WordPress
initial_delay_seconds: 60   # antes: 30
timeout_seconds: 10         # antes: 5
failure_threshold: 5        # antes: 3
```

### 5. Herramienta de Diagnóstico
**Archivo nuevo**: `phpinfo.php`

Accede a `https://tu-app.ondigitalocean.app/phpinfo.php` para:
- Ver extensiones PHP cargadas
- Verificar conexión a base de datos
- Comprobar variables de entorno
- Revisar uso de memoria actual

⚠️ **ELIMINA este archivo después de usarlo por seguridad**

## 🎯 Resultados Esperados

### Distribución de Memoria (512MB total):
```
PHP-FPM (4 workers × 96M)  = 384MB
Sistema operativo          = ~80MB
Apache/Nginx               = ~30MB
Otros procesos             = ~18MB
--------------------------------
Total                      = 512MB ✅
```

### WordPress funcionando con:
- ✅ 64M para operación normal
- ✅ 96M para admin/instalación
- ✅ Uploads hasta 32M
- ✅ Sin errores de memoria

## 🚀 Próximos Pasos - LO QUE DEBES HACER

### 1. Hacer Pull de los Cambios (si trabajas en local)
```bash
git pull origin main
```

DigitalOcean detectará los cambios automáticamente si tienes deploy_on_push activado.

### 2. O Forzar un Rebuild en DigitalOcean
Si no se despliega automáticamente:
1. Ve a tu app en https://cloud.digitalocean.com/apps
2. **Actions** → **Force Rebuild and Deploy**
3. Espera 5-10 minutos

### 3. Monitorear el Deploy
Ve a **Activity** → **Build Logs** y verifica:
- ✅ `✓ WordPress installation complete!`
- ✅ `WordPress version: 6.9.1`

Luego ve a **Runtime Logs** y busca:
- ✅ `Starting php-fpm with 4 workers...`
- ✅ `Application ready for connections on port 8080`

### 4. Usar la Herramienta de Diagnóstico
```
https://tu-app.ondigitalocean.app/phpinfo.php
```

**Verifica**:
- [ ] PHP memory_limit = 96M
- [ ] Todas las extensiones PHP cargadas (mbstring, mysqli, curl, gd, xml, zip, openssl, json)
- [ ] Conexión a base de datos: ✓ Database Connection Successful
- [ ] Variables de entorno configuradas
- [ ] Archivos de WordPress presentes

Si algo falla aquí, sabrás exactamente qué está mal.

### 5. Acceder al Instalador de WordPress
```
https://tu-app.ondigitalocean.app/
```

Si ves el instalador de WordPress → **ÉXITO** ✅

Completa:
- Título del sitio
- Usuario admin
- Contraseña
- Email

### 6. Eliminar phpinfo.php (IMPORTANTE)
Después de verificar que todo funciona:

```bash
git rm phpinfo.php
git commit -m "Remove diagnostics file"
git push
```

O desde la interfaz de GitHub, elimina el archivo.

## 🐛 Si Sigues Viendo Error 500

### Opción A: Revisar Logs Ahora Detallados
Con `display_errors = On`, los Runtime Logs ahora muestran el error exacto:

```
# Ejemplo de errores que verás:
PHP Fatal error: Allowed memory size of X bytes exhausted
→ Aún falta memoria (reduce plugins o upgrade plan)

Connection refused: [2002] Connection refused
→ Problema de conexión a base de datos

Call to undefined function mysqli_connect
→ Extensión mysqli no cargada
```

### Opción B: Usar phpinfo.php
Accede a `/phpinfo.php` y verifica cada sección:
- ¿Extensiones PHP cargadas?
- ¿Conexión a DB exitosa?
- ¿Variables de entorno configuradas?
- ¿Archivos de WordPress presentes?

### Opción C: Verificar Variables de Entorno
En DigitalOcean App Platform:
1. Settings → Environment Variables
2. Verifica que TODAS estén configuradas:
   - DB_NAME
   - DB_USER
   - DB_PASSWORD (encrypted)
   - DB_HOST (con puerto, ej: `host:25060`)
   - DB_SSL (`REQUIRED` para DO Managed MySQL)
   - AUTH_KEY, SECURE_AUTH_KEY, LOGGED_IN_KEY, NONCE_KEY
   - AUTH_SALT, SECURE_AUTH_SALT, LOGGED_IN_SALT, NONCE_SALT

## 📊 Limitaciones con 512MB RAM

Con esta configuración optimizada, WordPress funcionará, pero ten en cuenta:

### ⚠️ Lo que SÍ puedes hacer:
- ✅ Instalar WordPress
- ✅ Usar temas ligeros
- ✅ Tener 3-5 plugins activos
- ✅ Subir imágenes hasta 32M
- ✅ Manejar tráfico moderado

### ⚠️ Lo que puede causar problemas:
- ❌ Plugins muy pesados (page builders, caching complejos)
- ❌ Muchos plugins simultáneos (>10)
- ❌ Tráfico muy alto
- ❌ Temas con muchas funcionalidades

### 💡 Si necesitas más recursos:

**Opción 1: Upgrade Plan DigitalOcean**
- **Basic (1GB RAM)** - $12/mes
  - Permite memory_limit de 128M
  - 6-8 workers
  - Mejor rendimiento

**Opción 2: Optimizar WordPress**
- Instalar WP Super Cache (plugin ligero de caché)
- Optimizar imágenes antes de subir
- Usar solo plugins esenciales
- Usar tema ligero (Twenty Twenty-Four, GeneratePress)

**Opción 3: CDN**
- DigitalOcean Spaces para archivos estáticos
- Cloudflare para caché global

## 📚 Documentación Completa

1. **[OPTIMIZACION_512MB.md](OPTIMIZACION_512MB.md)** - Detalles técnicos completos
2. **[DEPLOYMENT.md](DEPLOYMENT.md)** - Guía de despliegue paso a paso
3. **[SOLUCION_RAPIDA.md](SOLUCION_RAPIDA.md)** - Soluciones a errores comunes

## ✅ Checklist Final

Antes de reportar que sigue sin funcionar, verifica:

- [ ] Hice pull o force rebuild con los nuevos cambios
- [ ] Los Build Logs muestran "✓ WordPress installation complete!"
- [ ] Los Runtime Logs muestran "Application ready for connections"
- [ ] Accedí a `/phpinfo.php` y todo está verde (✓)
- [ ] Todas las variables de entorno están configuradas en App Platform
- [ ] La base de datos acepta conexiones remotas
- [ ] DB_HOST incluye el puerto (ejemplo: `:25060`)
- [ ] DB_SSL está en `REQUIRED` (para DO Managed MySQL)

Si después de verificar todo esto sigues con problemas:
1. Toma captura de Runtime Logs (mostrará el error PHP exacto)
2. Toma captura de `/phpinfo.php` (mostrará qué falta)
3. Comparte ambas capturas

---

**Fecha**: 2026-02-14
**Optimizado para**: VPS con 512MB RAM
**WordPress**: Compatible con versión 6.9.1+
