# Despliegue en EasyPanel

## Aplicación web

1. En el servicio `app-gcdomotic`, configura un volumen persistente:

   ```text
   /var/www/html/storage
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
   ```

3. Configura PostgreSQL con una sola definición `DB_*`. No declares después `DB_CONNECTION=sqlite`.

4. Vuelve a implementar el servicio. El contenedor crea las carpetas requeridas, corrige permisos de escritura y ejecuta migraciones automáticamente.

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

## Verificación

Dentro del contenedor de `app-gcdomotic`, ejecuta:

```bash
cd /var/www/html
php artisan optimize:clear
php -m | grep -i '^gd$'
test -w storage/settings && echo "storage/settings OK"
test -w storage/app/tenants && echo "storage/app/tenants OK"
test -w storage/productos && echo "storage/productos OK"
test -w storage/dispositivos && echo "storage/dispositivos OK"
```

Las imágenes de configuración, productos e instalaciones se guardan bajo `storage/`. El volumen persistente evita perderlas al volver a implementar el servicio.
