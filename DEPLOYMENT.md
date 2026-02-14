# Guía de Despliegue en DigitalOcean App Platform

Esta guía te llevará paso a paso para desplegar WordPress en DigitalOcean App Platform con MySQL externo.

## ⚠️ NOTA IMPORTANTE: Recursos Limitados (512MB RAM)

Si tienes un VPS o plan con **512MB de RAM**, consulta primero:
👉 **[OPTIMIZACION_512MB.md](OPTIMIZACION_512MB.md)** - Configuración optimizada para recursos limitados

Este repositorio ya está optimizado para funcionar con 512MB RAM:
- ✅ PHP memory_limit reducido a 96M (4 workers × 96M = 384MB)
- ✅ WordPress memory_limit en 64M/96M
- ✅ Upload limitado a 32M
- ✅ Health checks más tolerantes
- ✅ Debug habilitado para diagnóstico

## 🚀 Inicio Rápido

**Lo que necesitas hacer OBLIGATORIAMENTE:**

1. ✅ **Crear la app en DigitalOcean** y conectar este repositorio
2. ✅ **Configurar las variables de entorno** (DB_NAME, DB_USER, DB_PASSWORD, DB_HOST) + claves de seguridad
3. ✅ **Desplegar** - el script automático descargará e instalará WordPress

**Lo que NO necesitas hacer:**
- ❌ NO subir WordPress manualmente
- ❌ NO configurar wp-config.php (ya está configurado)
- ❌ NO instalar dependencias manualmente

---

## ⚠️ Importante: Instalación Automática de WordPress

Este repositorio incluye un **script de construcción automático** (`build.sh`) que:
- ✅ Descarga la última versión de WordPress durante el despliegue
- ✅ Instala los directorios necesarios (`wp-includes/`, `wp-admin/`, `wp-content/`)
- ✅ Mantiene la configuración personalizada (`wp-config.php`) del repositorio

**No necesitas subir WordPress manualmente al repositorio** - todo se configura automáticamente durante el deploy en DigitalOcean.

## Paso 1: Preparación (antes de empezar)

Asegúrate de tener:
- ✅ Acceso al MySQL (host, puerto, db, user, pass)
- ✅ MySQL configurado para aceptar conexiones desde Internet
- ✅ Este repositorio ya está en GitHub

## Paso 2: Crear la App en DigitalOcean

1. Entra a DigitalOcean → **Create** → **Apps**
2. Selecciona **GitHub** y conecta este repositorio
3. Elige la rama `main` o `master`
4. DigitalOcean detectará automáticamente PHP (por `index.php`)
5. Selecciona el plan:
   - **Recomendado**: Basic (1 vCPU / 512MB RAM) - $5/mes
   - Para más tráfico: Professional (1 vCPU / 1GB RAM) - $12/mes

## Paso 3: Configurar Variables de Entorno ⚠️ **OBLIGATORIO**

**IMPORTANTE**: Las variables de entorno son **OBLIGATORIAS** para que WordPress funcione. Sin ellas, la aplicación fallará con errores como:
- `Failed to open stream: No such file or directory in wp-settings.php`
- `Error estableciendo conexión con la base de datos`

En **Settings** → tu componente web → **Environment Variables**, agrega:

### Variables Obligatorias (REQUERIDAS)

| Variable | Valor de Ejemplo | Descripción | Encrypt |
|----------|------------------|-------------|---------|
| `DB_NAME` | `wordpress_db` | Nombre de tu base de datos | No |
| `DB_USER` | `wp_user` | Usuario de MySQL | No |
| `DB_PASSWORD` | `tu_contraseña_segura` | Contraseña de MySQL | **Sí** ✅ |
| `DB_HOST` | `tu-host.db.ondigitalocean.com:25060` | Host y puerto de MySQL | No |
| `DB_PREFIX` | `wp_` | Prefijo de tablas (opcional) | No |
| `DB_SSL` | `REQUIRED` | SSL/TLS para DigitalOcean Managed MySQL | No |
| `WP_DEBUG` | `false` | Debug mode (false en producción) | No |

### Variables de Seguridad (Altamente Recomendadas)

Genera valores únicos en: https://api.wordpress.org/secret-key/1.1/salt/

Copia y pega los valores generados para:

| Variable | Encrypt |
|----------|---------|
| `AUTH_KEY` | **Sí** ✅ |
| `SECURE_AUTH_KEY` | **Sí** ✅ |
| `LOGGED_IN_KEY` | **Sí** ✅ |
| `NONCE_KEY` | **Sí** ✅ |
| `AUTH_SALT` | **Sí** ✅ |
| `SECURE_AUTH_SALT` | **Sí** ✅ |
| `LOGGED_IN_SALT` | **Sí** ✅ |
| `NONCE_SALT` | **Sí** ✅ |

**Importante**: Marca como **Encrypted** todas las contraseñas y keys sensibles.

## Paso 3.5: Verificar Configuración de Build (Automático)

El repositorio incluye el archivo `.do/app.yaml` que configura automáticamente:
- ✅ El comando de build: `bash build.sh` (descarga e instala WordPress)
- ✅ Las variables de entorno necesarias (incluyendo DB_SSL)
- ✅ La configuración de PHP y puerto HTTP

**Mejoras recientes en el script de build**:
- ✅ Logging detallado de cada paso del proceso
- ✅ Verificación automática de la descarga y extracción
- ✅ Detección de la versión de WordPress instalada
- ✅ Mensajes de error claros cuando algo falla
- ✅ Validación post-instalación de archivos críticos

**No necesitas hacer nada aquí** - DigitalOcean lo detecta automáticamente. Si tienes problemas, verifica que el archivo `.do/app.yaml` existe en el repositorio.

## Paso 4: Deploy

1. Haz clic en **Save** para guardar las variables
2. La app se desplegará automáticamente
3. Espera 5-10 minutos para el primer deploy

## Paso 5: Instalar WordPress

1. Cuando termine el deploy, abre la URL que te da App Platform
   - Ejemplo: `https://tu-app-xxxxx.ondigitalocean.app`
2. Verás el instalador de WordPress
3. Completa la información:
   - Título del sitio
   - Usuario admin
   - Email
4. ¡Listo! Accede a `/wp-admin`

## Paso 6: (Opcional) Configurar Dominio Personalizado

### Opción A: Dominio completo
Si quieres `tudominio.com`:
1. Ve a **Settings** → **Domains**
2. Agrega tu dominio
3. Configura los DNS según las instrucciones

### Opción B: Subdirectorio `/landing`
Si tu dominio principal está en otro servidor (Angular/Java) y quieres `tudominio.com/landing`:

Configura un **reverse proxy** en tu Nginx actual:

```nginx
location /landing {
    proxy_pass https://tu-app-xxxxx.ondigitalocean.app;
    proxy_set_header Host $host;
    proxy_set_header X-Real-IP $remote_addr;
    proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
    proxy_set_header X-Forwarded-Proto $scheme;
}
```

## Notas Importantes

### Archivos Subidos (Media)
⚠️ App Platform tiene **almacenamiento efímero**. Los archivos subidos se pueden perder en cada deploy.

**Solución**: Usar DigitalOcean Spaces (compatible con S3)
- Crea un Space en DigitalOcean
- Instala el plugin **WP Offload Media Lite** en WordPress
- Configura las credenciales del Space

### Permisos de MySQL
Asegúrate de que tu MySQL:
- Acepta conexiones desde App Platform
- El usuario tiene todos los permisos necesarios:
```sql
GRANT ALL PRIVILEGES ON wordpress_db.* TO 'wp_user'@'%';
FLUSH PRIVILEGES;
```

### Debugging
Si algo falla:
1. **Revisa los Build Logs primero** en App Platform (Activity → Build Logs)
   - El script mejorado ahora muestra exactamente dónde falla
   - Busca mensajes con ✗ (error) o ⚠ (advertencia)
2. Revisa los **Runtime Logs** en App Platform si la app inicia pero no funciona
3. Habilita temporalmente `WP_DEBUG=true` para ver errores de WordPress
4. Verifica que **todas** las variables de entorno están configuradas (incluyendo DB_SSL)

## Troubleshooting Común

### Error 500 en /wp-admin/install.php

**Síntomas**:
```
GET /wp-admin/install.php HTTP/1.1" 500 2647
```

**Causas Comunes**:
1. **Memoria insuficiente** (especialmente con 512MB RAM)
2. Error de conexión a base de datos
3. Variables de entorno mal configuradas
4. Extensiones PHP faltantes

**Solución**:
1. **Verifica que tienes la configuración optimizada** (ya incluida en este repo):
   - PHP memory_limit: 96M (ver `php.ini`, `.user.ini`)
   - WordPress memory limits: 64M/96M (ver `wp-config.php`)
   - Lee [OPTIMIZACION_512MB.md](OPTIMIZACION_512MB.md) para más detalles

2. **Usa la herramienta de diagnóstico**:
   - Accede a `https://tu-app.ondigitalocean.app/phpinfo.php`
   - Verifica extensiones PHP, conexión DB, archivos WordPress
   - ⚠️ ELIMINA este archivo después de diagnosticar

3. **Revisa Runtime Logs** (ahora con errores visibles):
   - Los errores PHP se muestran gracias a `display_errors = On`
   - Busca "PHP Fatal error" o "PHP Warning"
   - Identifica si es memoria, DB, o extensiones

4. **Verifica variables de entorno**:
   - Settings → Environment Variables
   - Asegúrate de que DB_NAME, DB_USER, DB_PASSWORD, DB_HOST, DB_SSL estén configurados

5. **Si es problema de memoria**:
   - Los valores ya están optimizados para 512MB
   - Considera upgrade a plan con 1GB RAM ($12/mes)
   - O reduce plugins/temas hasta mínimo necesario

### "Failed to open stream: No such file or directory in wp-settings.php" o "Failed opening required '/workspace/wp-includes/version.php'"

**Causa**: Este error ocurre cuando:
1. Las variables de entorno **NO** están configuradas (paso 3) - especialmente DB_SSL
2. El script de construcción (`build.sh`) no pudo descargar WordPress
3. Problema durante el deploy en DigitalOcean

**Solución**:
1. **Verifica que TODAS las variables de entorno obligatorias estén configuradas** en App Platform (Settings → Environment Variables)
   - Especialmente importante: `DB_SSL` (debe ser `REQUIRED` para DigitalOcean Managed MySQL)
2. **Revisa los Build Logs** en App Platform (Activity → Latest Deployment → Build Logs)
   - El script mejorado te dirá exactamente qué falló:
     - "Failed to download WordPress" → Problema de conectividad
     - "File is too small" → Descarga incompleta o corrupta
     - "Failed to extract" → Archivo corrupto
     - "VERIFICATION FAILED" → Indica qué archivos faltan
3. Si el script muestra que la descarga fue exitosa pero faltan archivos, revisa permisos
4. Intenta hacer un **nuevo deploy** desde App Platform (Actions → Force Rebuild and Deploy)

### "Error estableciendo conexión con la base de datos"
- Verifica `DB_HOST`, `DB_USER`, `DB_PASSWORD`, `DB_NAME` **y `DB_SSL`**
- Asegúrate de que MySQL acepta conexiones remotas
- Verifica el puerto en `DB_HOST` (ejemplo: `:25060`)

### La página se ve sin estilos / redirige mal
- Verifica que el dominio esté bien configurado en WordPress
- El código ya incluye soporte para HTTPS detrás de proxy

### "PHP version too old"
- App Platform usa PHP 8.x por defecto
- WordPress requiere PHP 7.4+
- Debería funcionar sin cambios

### Error: Call to undefined function wp_is_valid_utf8()

**Causa**: PHP mbstring extension no está instalado/habilitado.

**Solución**: Este error está automáticamente prevenido mediante las siguientes configuraciones en el repositorio:

1. ✅ `composer.json` - Especifica extensiones PHP requeridas (incluyendo ext-json)
2. ✅ `.user.ini` - Carga extensiones PHP al inicio
3. ✅ `php.ini` - Configuración alternativa de extensiones
4. ✅ `.do/app.yaml` - Configuración optimizada de DigitalOcean App Platform
5. ✅ `.htaccess` - Configuración de mbstring para Apache/mod_php
6. ✅ `build.sh` - Verifica extensiones durante el build

**Comandos de verificación de extensiones PHP**:

```bash
# Listar todas las extensiones cargadas
php -m

# Verificar mbstring específicamente
php -m | grep mbstring

# Ver información detallada de mbstring
php --ri mbstring

# Ver toda la configuración PHP
php -i | grep mbstring
```

**Si aún ves este error después de desplegar**:

1. Verifica los **Build Logs** en App Platform:
   - Busca mensajes sobre instalación de extensiones PHP
   - Verifica que `composer.json` fue procesado correctamente
   - Busca "✓ mbstring is available" en los logs del build
   
2. Verifica los **Runtime Logs**:
   - Busca mensajes sobre carga de extensiones
   - Verifica si hay errores relacionados con PHP
 
3. Fuerza un rebuild:
   - En App Platform: Actions → Force Rebuild and Deploy
   
4. Si el problema persiste:
   - Contacta soporte de DigitalOcean
   - Proporciona los build logs y runtime logs
   - Menciona que composer.json especifica ext-mbstring como requerimiento

**Nota**: Las extensiones PHP requeridas por WordPress (`mbstring`, `mysqli`, `curl`, `gd`, `xml`, `zip`, `openssl`, `json`) están configuradas en múltiples archivos para máxima compatibilidad con el buildpack de DigitalOcean.

## ¿Necesitas ayuda?
Revisa los logs en App Platform o contacta al equipo de desarrollo.
