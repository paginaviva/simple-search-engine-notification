# Núcleo de SSEN (`core_index/`)

Sistema modular de generación de sitemap y notificación a buscadores. La documentación general está en el [`README.md`](../README.md) de la raíz y la guía de instalación en [`docs/INSTALACION_Y_DESPLIEGUE.md`](../docs/INSTALACION_Y_DESPLIEGUE.md).

## Mapa de módulos

| Archivo | Responsabilidad |
|---|---|
| `config/config.php` | Fuente única de verdad: rutas, límites, credenciales y `validateConfiguration()` |
| `urls_loader.php` | Carga y validación de entradas JSON y CSV |
| `sitemap_diff.php` | Lectura del sitemap anterior (con estado) y clasificación de cambios |
| `sitemap_generator.php` | Orquestador: genera el XML, guarda copia, clasifica y notifica; incluye la interfaz de línea de comandos |
| `indexnow_auth.php` | Lectura y validación de la clave de IndexNow |
| `indexnow_client.php` | Cliente HTTP de IndexNow con troceo en lotes de 10.000 |
| `google_indexing_auth.php` | Autenticación OAuth 2.0 con cuenta de servicio (JWT) |
| `google_indexing_client.php` | Cliente de la API de Google; comprueba 404/410 antes de `URL_DELETED` |
| `logger.php` | Registro único con bloqueo, rotación y saneado |
| `auth_guard.php` | Autenticación básica de la interfaz web (Apache, LiteSpeed y nginx) |
| `sitemap_form_url.php` | Interfaz web protegida (autenticación básica de la aplicación, límite de frecuencia y bloqueo) |
| `tools/check_config.php` | Comprobación de configuración, extensiones y rutas |
| `tools/generate_indexnow_key.php` | Generación y rotación de la clave de IndexNow y su archivo público |

## Flujo de ejecución

1. `urls_loader.php` carga las URLs desde `data/urls.json` o `data/urls.csv`.
2. `sitemap_generator.php` valida, ordena y limita las entradas, y construye el XML escapando los cuatro campos.
3. `sitemap_diff.php` lee el sitemap anterior y clasifica los cambios.
4. Se guarda la copia `sitemap.old.xml` y se escribe `sitemap.xml` en la raíz del sitio.
5. Se notifica a IndexNow (nuevas, actualizadas y eliminadas) y, si hay credenciales, a Google.
6. `logger.php` registra cada operación en `../ssen-private/logs/indexing_api_sitemap.log` (fuera del documento raíz).

## Contratos de entrada

```json
{
  "urls": [
    {
      "loc": "https://ejemplo.test/pagina",
      "lastmod": "2026-01-10",
      "changefreq": "weekly",
      "priority": 0.8,
      "notify_indexnow": true,
      "notify_google_indexing": true
    }
  ]
}
```

```csv
loc,lastmod,changefreq,priority,notify_indexnow,notify_google_indexing
https://ejemplo.test/pagina,2026-01-10,weekly,0.8,1,1
```

## Rutas relevantes

- Configuración: `config/config.php`
- Clave de IndexNow: `config/indexnow_key.txt` (no versionada); archivo público `{clave}.txt` en la raíz del sitio
- Registro: `../ssen-private/logs/indexing_api_sitemap.log`
- Sitemap: `sitemap.xml` y `sitemap.old.xml` en la raíz del sitio
- Credenciales de Google: fuera del documento raíz (`PRIVATE_DIR`, por defecto `../ssen-private/credentials.json`)
- Autenticación web: `../ssen-private/.htpasswd` (`AUTH_USERS_FILE`)

## Uso por línea de comandos

```bash
php core_index/sitemap_generator.php --source=json
php core_index/sitemap_generator.php --source=csv
php core_index/tools/check_config.php
php core_index/tools/generate_indexnow_key.php [--force]
```

Códigos de salida del generador: `0` correcto, `1` error de ejecución, `2` error de configuración, `3` ejecución en curso.
