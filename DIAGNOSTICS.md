# Guía de Diagnóstico de Deployment - WordPress en DigitalOcean

## 🔍 Problema: "No puede iniciar la instalación de WordPress"

Esta guía te ayudará a identificar y resolver problemas de deployment usando las nuevas herramientas de diagnóstico.

## 🆕 Nuevas Herramientas de Diagnóstico

### 1. **Startup Diagnostics** (Automático)
Se ejecuta automáticamente al inicio de la aplicación y muestra en los Runtime Logs:
- ✅ Estado del entorno PHP
- ✅ Extensiones PHP cargadas
- ✅ Archivos y directorios de WordPress
- ✅ Variables de entorno configuradas
- ✅ Conexión a la base de datos
- ✅ Permisos de archivos
- ✅ Versión de WordPress instalada

### 2. **Health Check Endpoint** (Manual)
Accede a través del navegador: `https://tu-app.ondigitalocean.app/health-check.php`

**Características:**
- 🎨 Interfaz visual clara con código de colores
- ✅ Verificación completa del sistema en tiempo real
- 🔍 Detalles técnicos de cada verificación
- 📊 Resumen de errores y advertencias
- 🔗 Enlaces rápidos a otras herramientas de diagnóstico

**Formato JSON:** Añade `?format=json` para obtener resultados en JSON
```bash
curl https://tu-app.ondigitalocean.app/health-check.php?format=json
```

### 3. **PHP Info** (Diagnóstico Detallado)
Accede a: `https://tu-app.ondigitalocean.app/phpinfo.php`

Muestra información completa sobre:
- Configuración de PHP
- Extensiones cargadas
- Test de conexión a base de datos
- Variables de entorno
- Archivos de WordPress

## 📋 Proceso de Diagnóstico Paso a Paso

### Paso 1: Revisar Build Logs

1. Ve a tu app en https://cloud.digitalocean.com/apps
2. Click en **Activity** → **Build Logs**
3. Busca estos indicadores de éxito:

```
✓ WordPress installation complete!
✓ All verification checks passed
✓ Build verification successful
```

**Si el build falla:**
- ❌ Revisa los errores específicos en el log
- ❌ Verifica que el script `build.sh` sea ejecutable
- ❌ Confirma que hay conexión a internet para descargar WordPress

### Paso 2: Revisar Runtime Logs (CLAVE)

1. En tu app, ve a **Runtime Logs** (no Build Logs)
2. Busca la sección de **Startup Diagnostics**:

```
==============================================
WordPress Startup Diagnostics
==============================================
→ Checking PHP... ✓ OK
→ Checking PHP Extensions...
  ✓ mysqli
  ✓ curl
  ✓ gd
  ...
→ Checking Database Connection...
  ✓ SUCCESS: Connected to MySQL 8.0.x
```

**Estados posibles:**

#### ✅ TODO OK
```
✓✓✓ ALL CHECKS PASSED ✓✓✓
WordPress should be ready to start!
```
➜ **Acción:** Accede a tu app. Si aún no funciona, usa el Health Check.

#### ⚠️ ADVERTENCIAS
```
⚠ MISSING: wp-content/uploads
⚠ wp-content is NOT writable
```
➜ **Acción:** No crítico, pero puede causar problemas al subir archivos.

#### ❌ ERRORES CRÍTICOS
```
✗ MISSING: wp-includes/
✗ MISSING: DB_NAME
✗ ERROR: Connection refused
```
➜ **Acción:** Sigue los pasos de troubleshooting más abajo.

### Paso 3: Usar Health Check Endpoint

Abre en tu navegador:
```
https://tu-app.ondigitalocean.app/health-check.php
```

**Interpreta los resultados:**

| Estado | Color | Significado | Acción |
|--------|-------|-------------|---------|
| ✓ OK | Verde | Sistema funcionando | Continuar a WordPress |
| ⚠ WARNING | Amarillo | Funciona pero con limitaciones | Revisar advertencias |
| ✗ ERROR | Rojo | No funcionará correctamente | Corregir errores listados |

### Paso 4: Revisar Detalles Específicos

El Health Check te mostrará exactamente qué está fallando:

#### Error de Base de Datos
```json
"database_connection": {
  "status": "error",
  "message": "Failed to connect: Connection refused",
  "details": {
    "host": "db.example.com:25060",
    "error_code": 2002
  }
}
```

**Soluciones:**
1. Verifica que `DB_HOST` incluya el puerto: `host:25060`
2. Confirma que la base de datos esté corriendo
3. Verifica que acepta conexiones desde DigitalOcean
4. Revisa credenciales (DB_USER, DB_PASSWORD)
5. Si es Managed MySQL, confirma que `DB_SSL=REQUIRED`

#### Archivos Faltantes
```json
"wordpress_files": {
  "status": "error",
  "message": "Missing files: wp-includes/version.php",
  "missing": ["wp-includes/version.php"]
}
```

**Soluciones:**
1. El build falló - revisa Build Logs
2. Force Rebuild desde DigitalOcean dashboard
3. Verifica que `build.sh` tenga permisos de ejecución

#### Variables de Entorno
```json
"environment_variables": {
  "status": "error",
  "message": "Missing: DB_NAME, AUTH_KEY",
  "missing": ["DB_NAME", "AUTH_KEY"]
}
```

**Soluciones:**
1. Ve a Settings → Environment Variables en DigitalOcean
2. Añade las variables faltantes (ver lista completa abajo)
3. Redeploy la aplicación

## 🔧 Soluciones a Errores Comunes

### Error 1: "Cannot connect to database"

**Síntomas:**
- Health Check muestra error de conexión
- Runtime Logs: `✗ ERROR: Connection refused`

**Solución:**
1. Verifica el formato de `DB_HOST`:
   ```
   # Correcto (con puerto)
   DB_HOST=db-mysql-nyc1-12345.ondigitalocean.com:25060
   
   # Incorrecto (sin puerto)
   DB_HOST=db-mysql-nyc1-12345.ondigitalocean.com
   ```

2. Verifica `DB_SSL`:
   ```
   # Para DigitalOcean Managed MySQL
   DB_SSL=REQUIRED
   ```

3. Test desde tu máquina local:
   ```bash
   mysql -h db-host -P 25060 -u user -p database
   ```

### Error 2: "Missing WordPress files"

**Síntomas:**
- Health Check muestra archivos faltantes
- Runtime Logs: `✗ MISSING: wp-includes/`

**Solución:**
1. Revisa Build Logs completos:
   ```
   → Downloading WordPress...
   ✓ Downloaded WordPress with curl
   ✓ Extraction verified successfully
   ```

2. Si el download falló:
   - Verifica conectividad a wordpress.org
   - Intenta Force Rebuild

3. Si la extracción falló:
   - El archivo descargado podría estar corrupto
   - Force Rebuild para reintentar

### Error 3: "Missing environment variables"

**Síntomas:**
- Health Check lista variables no configuradas
- Runtime Logs: `✗ NOT SET: DB_NAME`

**Solución:**
Configura TODAS estas variables en DigitalOcean:

#### Variables Obligatorias de Base de Datos:
```
DB_NAME=tu_base_de_datos
DB_USER=tu_usuario
DB_PASSWORD=tu_contraseña       # Type: SECRET
DB_HOST=host:puerto             # Ejemplo: db.ondigitalocean.com:25060
DB_SSL=REQUIRED                 # Para Managed MySQL
```

#### Variables Obligatorias de Seguridad (genera en https://api.wordpress.org/secret-key/1.1/salt/):
```
AUTH_KEY=valor_aleatorio_largo
SECURE_AUTH_KEY=valor_aleatorio_largo
LOGGED_IN_KEY=valor_aleatorio_largo
NONCE_KEY=valor_aleatorio_largo
AUTH_SALT=valor_aleatorio_largo
SECURE_AUTH_SALT=valor_aleatorio_largo
LOGGED_IN_SALT=valor_aleatorio_largo
NONCE_SALT=valor_aleatorio_largo
```

**Cómo configurarlas:**
1. DigitalOcean Dashboard → Tu App → Settings
2. Environment Variables → Edit
3. Add Variable para cada una
4. Marca las de tipo SECRET (DB_PASSWORD y todas las *_KEY/*_SALT)
5. Save y espera el redeploy automático

### Error 4: "WordPress installed pero error 500"

**Síntomas:**
- Health Check muestra todo OK
- Al acceder a la app: Error 500

**Solución:**
1. Revisa Runtime Logs inmediatamente después del error
2. Con `display_errors = On`, verás el error PHP exacto:
   ```
   PHP Fatal error: Allowed memory size exhausted
   → Problema de memoria
   
   PHP Fatal error: Call to undefined function
   → Extensión PHP faltante
   
   PHP Warning: mysqli::real_connect(): Connection timeout
   → Problema de red/base de datos
   ```

3. Para problemas de memoria (512MB RAM):
   - Ya está optimizado en este repo
   - Considera upgrade a 1GB RAM ($12/mes)
   - Revisa [OPTIMIZACION_512MB.md](OPTIMIZACION_512MB.md)

## 📊 Checklist de Verificación Completo

Usa este checklist antes de reportar problemas:

### Build Phase
- [ ] Build Logs muestran `✓ WordPress installation complete!`
- [ ] Build Logs muestran versión de WordPress instalada
- [ ] Build Logs muestran `✓ Build verification successful`
- [ ] No hay errores en Build Logs

### Runtime Phase
- [ ] Runtime Logs muestran `✓✓✓ ALL CHECKS PASSED ✓✓✓`
- [ ] Runtime Logs muestran `Application ready for connections`
- [ ] No hay errores PHP en Runtime Logs

### Health Check
- [ ] `/health-check.php` muestra badge verde "ALL SYSTEMS OK"
- [ ] PHP Version check: ✓ OK
- [ ] PHP Extensions check: ✓ OK
- [ ] WordPress Files check: ✓ OK
- [ ] WordPress Directories check: ✓ OK
- [ ] Environment Variables check: ✓ OK
- [ ] Database Connection check: ✓ OK
- [ ] File Permissions: ✓ OK o ⚠ WARNING (acceptable)
- [ ] Memory Configuration: ✓ OK

### Environment Variables (App Settings)
- [ ] DB_NAME está configurado
- [ ] DB_USER está configurado
- [ ] DB_PASSWORD está configurado (type: SECRET)
- [ ] DB_HOST está configurado (formato: `host:puerto`)
- [ ] DB_SSL está configurado (valor: `REQUIRED`)
- [ ] Todas las 8 claves de seguridad están configuradas (AUTH_KEY, SECURE_AUTH_KEY, etc.)

### Database
- [ ] Base de datos existe y está accesible
- [ ] Usuario tiene permisos completos
- [ ] Firewall permite conexiones desde DigitalOcean
- [ ] SSL/TLS está habilitado (si es Managed MySQL)

## 🚨 Cómo Reportar un Problema

Si después de seguir esta guía aún tienes problemas, incluye:

1. **URL del Health Check:**
   ```
   https://tu-app.ondigitalocean.app/health-check.php?format=json
   ```
   Copia y pega el JSON completo

2. **Runtime Logs:**
   - Últimas 50 líneas que incluyan el startup diagnostics
   - Cualquier error PHP visible

3. **Build Logs:**
   - Sección completa del build de WordPress
   - Desde "→ Downloading WordPress..." hasta "✓ Build verification successful"

4. **Configuración (sin exponer secretos):**
   ```
   Plan: Basic XXS (512MB RAM)
   Region: NYC
   PHP Version: X.X
   DB Type: Managed MySQL / External
   DB SSL: Enabled / Disabled
   ```

## 🎯 Próximos Pasos Después de Solucionar

Una vez que Health Check muestre todo en verde:

1. **Accede a WordPress:**
   ```
   https://tu-app.ondigitalocean.app/
   ```

2. **Completa la instalación:**
   - Elige idioma
   - Título del sitio
   - Usuario administrador
   - Contraseña segura
   - Email

3. **Elimina archivos de diagnóstico (IMPORTANTE):**
   ```bash
   git rm health-check.php phpinfo.php
   git commit -m "Remove diagnostic files after successful deployment"
   git push
   ```
   O elimínalos desde la interfaz de GitHub.

4. **Optimiza tu instalación:**
   - Instala solo plugins necesarios
   - Usa tema ligero (Twenty Twenty-Four)
   - Configura caché (WP Super Cache)
   - Optimiza imágenes antes de subir

## 📚 Documentación Relacionada

- **[SOLUCION_ERROR_500.md](SOLUCION_ERROR_500.md)** - Solución específica para Error 500
- **[OPTIMIZACION_512MB.md](OPTIMIZACION_512MB.md)** - Optimización para recursos limitados
- **[DEPLOYMENT.md](DEPLOYMENT.md)** - Guía completa de despliegue
- **[SOLUCION_RAPIDA.md](SOLUCION_RAPIDA.md)** - Soluciones rápidas a problemas comunes

---

**Última actualización:** 2026-02-14  
**Herramientas incluidas:** startup-diagnostics.sh, health-check.php, phpinfo.php
