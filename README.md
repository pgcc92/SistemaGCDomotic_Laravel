## GC Domotic Dashboard (Base SaaS)

Base profesional en Laravel (Blade + Tailwind + Alpine) lista para:

- UI tipo dashboard responsive.
- Personalización visual por tenant (nombre, colores, tipografía, logos).
- Capa de servicios para consumir una “BD remota” vía APIs (no asume Postgres local).
- Preparación para subdominio (modo multi-tenant) en cPanel.

### Requisitos

- PHP (objetivo: 8.2). En este workspace se generó con Laravel 10 para compatibilidad con PHP 8.1/8.2.
- Node.js + npm (para Vite/Tailwind).

### Configuración local rápida

1) Copia variables de entorno:

`cp .env.example .env`

2) Genera key y dependencias:

`composer install`

3) Si usas SQLite local (recomendado para el dashboard):

`touch database/database.sqlite && php artisan migrate`

3) Frontend:

`npm install && npm run dev`

4) Levanta servidor:

`php artisan serve`

### Variables `.env` relevantes

- `CONFIG_STORE_DRIVER=file|api` (por defecto `file` guarda branding por tenant en `storage/app/tenants/<tenant>.json`)
- `TENANT_MODE=single|subdomain`
- `TENANT_BASE_DOMAIN=midominio.com` (solo si `TENANT_MODE=subdomain`)
- `REMOTE_API_BASE_URL=http://127.0.0.1:8001` (API local) o `https://api.midominio.com`
- `REMOTE_API_KEY=...` (service key, el mismo que `GC_API_TOKEN` de la Data API)

### Rutas base

- `GET /dashboard` (requiere login)
- `GET /configuracion` + `POST /configuracion` (config visual por tenant)
- Módulos (preview): `/clientes`, `/tickets`, `/ventas`, `/productos`, `/comisiones`, `/sucursales`, `/auditoria`, etc.

### Notas cPanel / subdominio

- Apuntar el subdominio a la carpeta `public/`.
- Configurar `.htaccess` (Laravel estándar) y permisos de `storage/` y `bootstrap/cache/`.
- Usar `php artisan storage:link` si se habilita storage público.

### Arquitectura (Ruta A)

- Dashboard (este proyecto) autentica usuarios contra la Data API y consume recursos por HTTP.
- Data API (`services/gc-data-api`) es el único componente que se conecta a PostgreSQL remoto.

## About Laravel

Laravel is a web application framework with expressive, elegant syntax. We believe development must be an enjoyable and creative experience to be truly fulfilling. Laravel takes the pain out of development by easing common tasks used in many web projects, such as:

- [Simple, fast routing engine](https://laravel.com/docs/routing).
- [Powerful dependency injection container](https://laravel.com/docs/container).
- Multiple back-ends for [session](https://laravel.com/docs/session) and [cache](https://laravel.com/docs/cache) storage.
- Expressive, intuitive [database ORM](https://laravel.com/docs/eloquent).
- Database agnostic [schema migrations](https://laravel.com/docs/migrations).
- [Robust background job processing](https://laravel.com/docs/queues).
- [Real-time event broadcasting](https://laravel.com/docs/broadcasting).

Laravel is accessible, powerful, and provides tools required for large, robust applications.

## Learning Laravel

Laravel has the most extensive and thorough [documentation](https://laravel.com/docs) and video tutorial library of all modern web application frameworks, making it a breeze to get started with the framework.

You may also try the [Laravel Bootcamp](https://bootcamp.laravel.com), where you will be guided through building a modern Laravel application from scratch.

If you don't feel like reading, [Laracasts](https://laracasts.com) can help. Laracasts contains thousands of video tutorials on a range of topics including Laravel, modern PHP, unit testing, and JavaScript. Boost your skills by digging into our comprehensive video library.

## Laravel Sponsors

We would like to extend our thanks to the following sponsors for funding Laravel development. If you are interested in becoming a sponsor, please visit the [Laravel Partners program](https://partners.laravel.com).

### Premium Partners

- **[Vehikl](https://vehikl.com/)**
- **[Tighten Co.](https://tighten.co)**
- **[WebReinvent](https://webreinvent.com/)**
- **[Kirschbaum Development Group](https://kirschbaumdevelopment.com)**
- **[64 Robots](https://64robots.com)**
- **[Curotec](https://www.curotec.com/services/technologies/laravel/)**
- **[Cyber-Duck](https://cyber-duck.co.uk)**
- **[DevSquad](https://devsquad.com/hire-laravel-developers)**
- **[Jump24](https://jump24.co.uk)**
- **[Redberry](https://redberry.international/laravel/)**
- **[Active Logic](https://activelogic.com)**
- **[byte5](https://byte5.de)**
- **[OP.GG](https://op.gg)**

## Contributing

Thank you for considering contributing to the Laravel framework! The contribution guide can be found in the [Laravel documentation](https://laravel.com/docs/contributions).

## Code of Conduct

In order to ensure that the Laravel community is welcoming to all, please review and abide by the [Code of Conduct](https://laravel.com/docs/contributions#code-of-conduct).

## Security Vulnerabilities

If you discover a security vulnerability within Laravel, please send an e-mail to Taylor Otwell via [taylor@laravel.com](mailto:taylor@laravel.com). All security vulnerabilities will be promptly addressed.

## License

The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
