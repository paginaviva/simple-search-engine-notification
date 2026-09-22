# AGENTS.md — SSEN (Simple Search Engine Notification)

PHP puro, sin Composer y sin dependencias externas; solo extensiones nativas: `openssl`, `curl`, `json`, `simplexml`. Pruebas nativas incluidas.

## Qué código es canónico

- **Use `core_index/`**: sistema modular (`@package SSEN`). Puntos de entrada:
  - Web (protegido): `core_index/sitemap_form_url.php?source=json|csv`
  - Línea de comandos: `php core_index/sitemap_generator.php [--source=json|csv]`
  - Programático: `generateSitemap(array $urlsData)` en `core_index/sitemap_generator.php`
- **`gestion/` es legado local** (rastreador antiguo con dominio fijado) y **no se versiona** (véase `.gitignore`). No ampliarlo. El `config.php` de la raíz también es legado local y no se versiona.
- La protección del punto de entrada web se aplica en `core_index/auth_guard.php`: autenticación básica a nivel de aplicación, válida en Apache, LiteSpeed y nginx.

## Configuración (fuente única: `core_index/config/config.php`)

- `BASE_URL` sin barra final; `INDEXNOW_HOST` sin protocolo. `validateConfiguration()` devuelve `['valid' => bool, 'errors' => [], 'warnings' => []]`.
- La falta de credenciales de Google es una **advertencia**: la rama de Google queda deshabilitada, nunca bloquea.
- Rutas: `SITEMAP_PATH = ROOT_DIR/sitemap.xml`, `SITEMAP_OLD_PATH = ROOT_DIR/sitemap.old.xml`, `LOG_PATH = PRIVATE_DIR/logs/indexing_api_sitemap.log` (fuera del documento raíz), entradas en `core_index/data/urls.{json,csv}`.
- Credenciales de Google: `GOOGLE_CREDENTIALS_PATH` dentro de `PRIVATE_DIR`, **fuera del documento raíz** (por defecto `../ssen-private/credentials.json`, permisos 600). No versionar.
- Autenticación web: `AUTH_USERS_FILE` (por defecto `../ssen-private/.htpasswd`, permisos 600) y `WEB_AUTH_ENABLED`; se aplica en `core_index/auth_guard.php`.
- Clave de IndexNow: `core_index/config/indexnow_key.txt` (8 a 128 caracteres alfanuméricos o guion; `php core_index/tools/generate_indexnow_key.php`). El archivo público `{clave}.txt` debe quedar en la raíz del sitio. No versionar ninguno de los dos.

## Contrato de entrada

- JSON: `{"urls": [{"loc": "https://ejemplo.test/pagina"}]}`; la clave es **`loc`**.
- CSV: cabecera con la columna `loc`; columnas admitidas: `loc`, `lastmod`, `changefreq`, `priority`, `notify_indexnow`, `notify_google_indexing`.
- `generateSitemap()` valida `loc` (solo `http`/`https`), `lastmod` (ISO 8601), `changefreq` (lista blanca) y `priority` (0 a 1); omite entradas no válidas, aplica `MAX_URLS_PER_SITEMAP`, ordena por `priority DESC, lastmod DESC`, guarda copia, clasifica cambios y notifica solo lo cambiado.
- IndexNow recibe nuevas, actualizadas y eliminadas (sin tipos). Google recibe `URL_UPDATED` para nuevas y actualizadas, y `URL_DELETED` solo tras comprobar 404 o 410.

## Verificación

```bash
php core_index/tools/check_config.php     # configuración, extensiones y rutas
php tests/run.php                          # arnés de pruebas nativo
php -l core_index/sitemap_generator.php    # sintaxis (un archivo)
find core_index -name "*.php" -exec php -l {} \;
xmllint --noout sitemap.xml && echo "XML válido"
```

- Las pruebas se ejecutan con `php tests/run.php` y se han verificado en PHP 8.1 a 8.4 (recomendado 8.4).
- Si la interfaz de línea de comandos de PHP no está disponible en el entorno de desarrollo, ejecute las pruebas en el servidor o en un equipo con PHP.

## Reglas de seguridad

- Nunca versionar: `core_index/config/indexnow_key.txt`, `{clave}.txt` de la raíz, ni el contenido de `PRIVATE_DIR` (credenciales, archivo de usuarios y registros); tampoco sitemaps generados.
- La interfaz web exige autenticación básica a nivel de aplicación sobre HTTPS y tiene límite de frecuencia; el bloqueo de ejecución evita solapamientos con el cron.
- No reproducir claves ni credenciales en documentación, informes ni registros.
