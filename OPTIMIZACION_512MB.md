# Optimización para Recursos Limitados (512MB RAM)

## 📊 Análisis del Problema

Según los logs de runtime:
- **RAM Disponible**: 512MB
- **PHP memory_limit original**: 128M
- **Número de workers**: 4
- **Problema**: Error 500 en `/wp-admin/install.php`

### Diagnóstico
Con 4 workers usando 128M cada uno, se necesitarían **512MB solo para PHP-FPM**, dejando 0 MB para:
- Sistema operativo
- Apache/Nginx
- MySQL (si está local)
- Procesos del sistema

**Esto causa agotamiento de memoria y errores 500.**

## ✅ Soluciones Implementadas

### 1. Reducción de Memory Limit PHP
**Antes**: 128M por worker
**Ahora**: 96M por worker

**Cálculo**: 4 workers × 96M = 384MB, dejando 128MB para el sistema y otros servicios.

**Archivos modificados**:
- `php.ini`: `memory_limit = 96M`
- `.user.ini`: `memory_limit = 96M`

### 2. Límites de Memoria WordPress
Configuración en `wp-config.php`:
```php
define( 'WP_MEMORY_LIMIT', '64M' );      // Memoria normal
define( 'WP_MAX_MEMORY_LIMIT', '96M' );  // Memoria máxima (admin)
```

Esto previene que WordPress consuma más memoria de la disponible.

### 3. Reducción de Tamaños de Upload
**Antes**: 64M upload_max_filesize / post_max_size
**Ahora**: 32M upload_max_filesize / post_max_size

Esto es más que suficiente para imágenes y previene uploads que consuman toda la memoria.

### 4. Mejora del Health Check
```yaml
health_check:
  initial_delay_seconds: 60  # Antes: 30
  timeout_seconds: 10        # Antes: 5
  failure_threshold: 5       # Antes: 3
```

Esto da más tiempo a WordPress para instalarse antes de marcarlo como fallido.

### 5. Debugging Habilitado
```ini
display_errors = On
display_startup_errors = On
error_reporting = E_ALL
```

Ahora los errores PHP se mostrarán en los Runtime Logs de DigitalOcean.

### 6. Debug Log de WordPress
```php
define( 'WP_DEBUG_LOG', true );
define( 'WP_DEBUG_DISPLAY', false );
```

Los errores se guardarán en `/tmp/php_errors.log` (accesible en los logs de DO).

## 🔧 Herramienta de Diagnóstico

Se ha creado `phpinfo.php` para diagnosticar problemas:

### Cómo usarlo:
1. Después del deploy, accede a: `https://tu-app.ondigitalocean.app/phpinfo.php`
2. Revisa:
   - ✅ Extensiones PHP cargadas
   - ✅ Conexión a base de datos
   - ✅ Variables de entorno configuradas
   - ✅ Archivos de WordPress presentes
   - ✅ Uso de memoria actual

### ⚠️ IMPORTANTE - Seguridad
**ELIMINA `phpinfo.php` después de diagnosticar** con:
```bash
git rm phpinfo.php
git commit -m "Remove diagnostics file"
git push
```

Este archivo expone información sensible del servidor.

## 📝 Próximos Pasos

### 1. Hacer Deploy
```bash
git pull
# DigitalOcean detectará los cambios automáticamente
# O fuerza un rebuild: Actions → Force Rebuild and Deploy
```

### 2. Monitorear los Logs
Ve a **Runtime Logs** en App Platform y busca:
- ✅ `Starting php-fpm with 4 workers...` - Confirmar que arranca
- ✅ `Application ready for connections` - Servidor listo
- ❌ Errores PHP mostrados (gracias a `display_errors = On`)

### 3. Acceder a phpinfo.php
```
https://tu-app.ondigitalocean.app/phpinfo.php
```

Verifica que:
- [ ] Todas las extensiones PHP estén cargadas
- [ ] `memory_limit = 96M`
- [ ] Conexión a base de datos exitosa
- [ ] Variables de entorno configuradas
- [ ] Archivos de WordPress presentes

### 4. Instalar WordPress
```
https://tu-app.ondigitalocean.app/
```

Si ves el instalador de WordPress → **ÉXITO** ✅

Si sigues viendo 500:
1. Revisa Runtime Logs - ahora verás el error específico
2. Revisa phpinfo.php - verás exactamente qué falta
3. Verifica variables de entorno en App Platform

### 5. Después de Instalar - Eliminar phpinfo.php
```bash
git rm phpinfo.php
git commit -m "Remove diagnostics file"
git push
```

## 🎯 Resultados Esperados

### Con 512MB RAM:
- ✅ 4 workers PHP-FPM usando ~384MB
- ✅ ~128MB libres para sistema/servicios
- ✅ WordPress funcionando dentro de límites de 64M/96M
- ✅ Uploads hasta 32M (suficiente para imágenes)
- ✅ Sin errores de memoria

### Limitaciones a Esperar:
- ⚠️ Plugins pesados pueden causar problemas
- ⚠️ Temas con muchas imágenes pueden ser lentos
- ⚠️ No instalar demasiados plugins simultáneamente

### Recomendaciones Futuras:
Si necesitas más rendimiento:
1. **Upgrade a Basic (1GB RAM)** - $12/mes
   - Permitiría memory_limit de 128M
   - 6-8 workers
   - Mejor rendimiento general

2. **Optimizar WordPress**:
   - Usar plugin de caché (WP Super Cache)
   - Optimizar imágenes antes de subir
   - Limitar número de plugins activos

3. **Usar CDN**:
   - DigitalOcean Spaces para archivos estáticos
   - Cloudflare para caché global

## 📊 Comparación Antes/Después

| Métrica | Antes | Ahora | Mejora |
|---------|-------|-------|--------|
| PHP memory_limit | 128M | 96M | ✅ Optimizado |
| Memoria total PHP | 512M | 384M | ✅ 128M libres |
| Upload max | 64M | 32M | ✅ Más razonable |
| Debug habilitado | ❌ | ✅ | ✅ Mejor diagnóstico |
| Health check timeout | 5s | 10s | ✅ Más tolerante |
| WordPress memory | Sin límite | 64M/96M | ✅ Controlado |

## 🐛 Troubleshooting

### Si el error 500 persiste:

1. **Revisa Runtime Logs** - ahora verás el error exacto:
   ```
   PHP Fatal error: Allowed memory size of X bytes exhausted
   → Necesitas más RAM o menos plugins
   
   Connection refused to database
   → Revisa variables DB_* en App Platform
   
   Call to undefined function
   → Falta una extensión PHP
   ```

2. **Accede a phpinfo.php** - verifica:
   - Extensiones PHP cargadas
   - Variables de entorno
   - Conexión a base de datos
   - Archivos de WordPress

3. **Si necesitas más memoria**:
   - Considera upgrade a plan superior
   - O reduce número de workers (requiere Procfile personalizado)

## 📞 Soporte

Si después de estos cambios sigues teniendo problemas:
1. Captura pantalla de Runtime Logs (con los errores visibles)
2. Captura pantalla de phpinfo.php
3. Verifica variables de entorno en App Platform
4. Contacta soporte técnico con esta información

---

**Autor**: Optimización automática para VPS con recursos limitados
**Fecha**: 2026-02-14
**Versión**: 1.0
