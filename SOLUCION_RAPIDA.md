# 🔧 Solución Rápida: Error "Failed to open stream: No such file or directory"

## ❌ El Error

```
PHP Warning: require(/workspace/wp-includes/version.php): Failed to open stream: No such file or directory in /workspace/wp-settings.php on line 34
PHP Fatal error: Uncaught Error: Failed opening required '/workspace/wp-includes/version.php'
```

## ✅ Solución

Este error ocurre porque **faltan las variables de entorno** en DigitalOcean App Platform.

### Pasos para Solucionarlo:

1. **Ve a tu aplicación en DigitalOcean App Platform**
   - Abre https://cloud.digitalocean.com/apps
   - Selecciona tu aplicación `wp-autosell`

2. **Configura las Variables de Entorno**
   - Ve a **Settings** → **Components** → `web` → **Environment Variables**
   - Haz clic en **Edit** o **Add Variable**
   
3. **Agrega las siguientes variables OBLIGATORIAS:**

   | Variable | Valor | Encrypt |
   |----------|-------|---------|
   | `DB_NAME` | Nombre de tu base de datos MySQL | No |
   | `DB_USER` | Usuario de MySQL | No |
   | `DB_PASSWORD` | Contraseña de MySQL | ✅ Sí |
   | `DB_HOST` | Host de MySQL con puerto (ej: `host:25060`) | No |
   | `DB_SSL` | `REQUIRED` o `true` para DigitalOcean Managed MySQL | No |

4. **Agrega las Claves de Seguridad de WordPress**
   
   Genera claves únicas en: https://api.wordpress.org/secret-key/1.1/salt/
   
   Copia y pega los valores para cada una de estas variables (marca todas como **Encrypted**):
   - `AUTH_KEY`
   - `SECURE_AUTH_KEY`
   - `LOGGED_IN_KEY`
   - `NONCE_KEY`
   - `AUTH_SALT`
   - `SECURE_AUTH_SALT`
   - `LOGGED_IN_SALT`
   - `NONCE_SALT`

5. **Guarda y Despliega de Nuevo**
   - Haz clic en **Save**
   - Ve a **Actions** → **Force Rebuild and Deploy**
   - Espera 5-10 minutos

## ¿Por Qué Ocurre Este Error?

El repositorio NO incluye los archivos de WordPress (`wp-includes/`, etc.) porque se descargan automáticamente durante el despliegue mediante el script `build.sh`. 

**Pero para que esto funcione, necesitas configurar las variables de entorno primero.**

## ¿Sigue Sin Funcionar?

Si después de configurar las variables de entorno el error persiste:

1. Revisa los **Build Logs** en App Platform
2. Verifica que el script `build.sh` se ejecutó correctamente
3. Busca mensajes como "✓ WordPress installation complete!"
4. Si el script falló, intenta hacer un nuevo deploy

## Documentación Completa

Para más información, consulta:
- [DEPLOYMENT.md](DEPLOYMENT.md) - Guía completa de despliegue
- [BUILD.md](BUILD.md) - Documentación técnica del proceso de build