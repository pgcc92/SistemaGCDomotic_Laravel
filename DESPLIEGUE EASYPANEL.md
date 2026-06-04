# Despliegue en EasyPanel

## Aplicación web

1. En el servicio `app-gcdomotic`, configura un volumen persistente para imágenes:

   ```text
   /data/gcdomotic-storage
   ```

2. Conserva estas variables de entorno:

   ```text
   APP_ENV=production
   APP_DEBUG=false
   APP_URL=https://app.gcdomotic.com
   CONFIG_STORE_DRIVER=file
   TENANT_MODE=single
   REMOTE_API_BASE_URL=https://api.gcdomotic.com
   REMOTE_API_KEY=<mismo valor de GC_API_TOKEN>
   GC_UPLOAD_ROOT=/data/gcdomotic-storage
   ```

3. Configura PostgreSQL con una sola definición `DB_*`. No declares después `DB_CONNECTION=sqlite`.

4. Si ya tienes imágenes guardadas antes del cambio, cópialas al volumen antes de reemplazar el contenedor:

   ```bash
   mkdir -p /data/gcdomotic-storage
   cp -a /var/www/html/storage/settings /data/gcdomotic-storage/ 2>/dev/null || true
   cp -a /var/www/html/storage/productos /data/gcdomotic-storage/ 2>/dev/null || true
   cp -a /var/www/html/storage/dispositivos /data/gcdomotic-storage/ 2>/dev/null || true
   ```

5. Vuelve a implementar el servicio. El volumen persistente evita que `settings`, `productos` y `dispositivos` se borren en cada deploy.

### Refrescar después de cada cambio

Cada deploy de la app debe recompilar assets para que Vite genere nombres nuevos con hash y el navegador descargue la versión nueva:

```bash
cd /var/www/html
git pull origin main
npm install
npm run build
php artisan optimize:clear
php artisan route:clear
php artisan config:clear
php artisan view:clear
```

Después reinicia el contenedor `app-gcdomotic`.

La app publica `/app-version` y las páginas HTML incluyen esa versión. Si un usuario tiene una página abierta y se sube un cambio nuevo, el navegador detecta el cambio y recarga la pantalla automáticamente.

Si EasyPanel despliega sin carpeta `.git`, configura esta variable en cada deploy:

```text
APP_VERSION=<commit-o-version-del-deploy>
```

## API

1. En el servicio `api-gcdomotic`, verifica:

   ```text
   APP_ENV=production
   APP_DEBUG=false
   APP_URL=https://api.gcdomotic.com
   GC_API_TOKEN=<token privado>
   DB_CONNECTION=pgsql
   ```

2. El valor `GC_API_TOKEN` debe coincidir exactamente con `REMOTE_API_KEY` de la aplicación web.

3. Vuelve a implementar el servicio y valida:

   ```bash
   curl -i -H "X-API-Key: $GC_API_TOKEN" https://api.gcdomotic.com/api/v1/health
   ```

Para refrescar el API después de cada cambio:

```bash
cd /var/www/html
git pull origin main
php artisan optimize:clear
php artisan route:clear
php artisan config:clear
```

Después reinicia el contenedor `api-gcdomotic`.

## Verificación

Dentro del contenedor de `app-gcdomotic`, ejecuta:

```bash
cd /var/www/html
php artisan optimize:clear
php -m | grep -i '^gd$'
mkdir -p "$GC_UPLOAD_ROOT"/{settings,productos,dispositivos}
chown -R www-data:www-data "$GC_UPLOAD_ROOT" 2>/dev/null || true
chmod -R 775 "$GC_UPLOAD_ROOT"
test -w "$GC_UPLOAD_ROOT/settings" && echo "settings OK"
test -w storage/app/tenants && echo "storage/app/tenants OK"
test -w "$GC_UPLOAD_ROOT/productos" && echo "productos OK"
test -w "$GC_UPLOAD_ROOT/dispositivos" && echo "dispositivos OK"
```

Las imágenes de configuración, productos e instalaciones se guardan bajo `GC_UPLOAD_ROOT`. En local, si no defines esa variable, se usa `storage/` del proyecto.
