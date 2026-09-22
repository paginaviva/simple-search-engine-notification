# SSEN — Simple Search Engine Notification

Generador de sitemap XML y notificador de cambios a IndexNow y, opcionalmente, a la API de indexación de Google. PHP puro, sin dependencias externas y sin Composer.

- **Entrada**: archivos JSON o CSV con las URLs del sitio.
- **Salida**: `sitemap.xml` en la raíz del sitio, con copia de la versión anterior (`sitemap.old.xml`).
- **Notificación**: solo las URLs nuevas, actualizadas o eliminadas.
- **Ejecución**: interfaz web protegida, línea de comandos y tarea programada (cron).

## Requisitos

- PHP 8.1 o superior (recomendado 8.4) con las extensiones `openssl`, `curl`, `json` y `simplexml`.
- Servidor web Apache, LiteSpeed o nginx. La protección de la interfaz web es a nivel de aplicación y no depende de `.htaccess`.
- Acceso por HTTPS.
- Para la API de Google (opcional): una cuenta de servicio con un archivo `credentials.json` y permiso de propietario en Search Console.

## Inicio rápido

1. **Suba el proyecto** al servidor (por ejemplo, al documento raíz del sitio).
2. **Configure** `core_index/config/config.php`: `BASE_URL` (sin barra final) e `INDEXNOW_HOST` (sin protocolo).
3. **Genere la clave de IndexNow** y su archivo público de verificación:

   ```bash
   php core_index/tools/generate_indexnow_key.php
   ```

   El archivo `{clave}.txt` debe quedar accesible en `https://su-dominio/{clave}.txt`.
4. **Proteja la interfaz web**: cree el archivo de usuarios fuera del documento raíz con `htpasswd -B -c ../ssen-private/.htpasswd operador` (ruta de `AUTH_USERS_FILE`; véase la guía de instalación).
5. **Compruebe la configuración**:

   ```bash
   php core_index/tools/check_config.php
   ```
6. **Ejecute** la generación por línea de comandos o desde la interfaz web protegida.

La guía completa está en [`docs/INSTALACION_Y_DESPLIEGUE.md`](docs/INSTALACION_Y_DESPLIEGUE.md).

## Formatos de entrada

### JSON (`core_index/data/urls.json`)

```json
{
  "urls": [
    {
      "loc": "https://su-dominio.com/",
      "lastmod": "2026-01-10",
      "changefreq": "daily",
      "priority": 1.0,
      "notify_indexnow": true,
      "notify_google_indexing": true
    }
  ]
}
```

### CSV (`core_index/data/urls.csv`)

```csv
loc,lastmod,changefreq,priority,notify_indexnow,notify_google_indexing
https://su-dominio.com/,2026-01-10,daily,1.0,1,1
```

- `loc` es obligatorio y debe ser una dirección `http` o `https`.
- `lastmod` admite `AAAA-MM-DD` o fecha y hora ISO 8601; por defecto, la fecha actual.
- `changefreq` admite `always`, `hourly`, `daily`, `weekly`, `monthly`, `yearly` y `never`.
- `priority` admite valores de `0.0` a `1.0`; por defecto, `0.8`.
- Los indicadores de notificación admiten `true`/`false` en JSON y `1`/`true` en CSV.

Las entradas no válidas se omiten y quedan registradas como avisos.

## Uso

### Interfaz web (protegida)

```text
https://su-dominio.com/core_index/sitemap_form_url.php?source=json
https://su-dominio.com/core_index/sitemap_form_url.php?source=csv
```

La interfaz exige autenticación básica, aplica un límite de frecuencia y comparte bloqueo con el cron para evitar ejecuciones simultáneas.

### Línea de comandos

```bash
php core_index/sitemap_generator.php --source=json
php core_index/sitemap_generator.php --source=csv
php core_index/sitemap_generator.php --help
```

Códigos de salida: `0` correcto, `1` error de ejecución, `2` error de configuración, `3` ejecución en curso.

### Tarea programada (cron)

```cron
0 3 * * * /usr/local/bin/php /home/USUARIO/public_html/core_index/sitemap_generator.php --source=json >> /home/USUARIO/ssen-private/logs/cron.log 2>&1
```

Ajuste la ruta del intérprete (`ea-php81` en cPanel) y la del proyecto.

## Cómo funciona

1. Carga y valida las URLs de entrada.
2. Ordena por prioridad y fecha, y aplica el límite de entradas por sitemap.
3. Construye el XML con los cuatro campos escapados.
4. Lee el sitemap anterior, guarda una copia y escribe el nuevo.
5. Clasifica los cambios (nuevas, actualizadas y eliminadas).
6. Notifica a IndexNow (nuevas, actualizadas y eliminadas, en lotes de hasta 10.000) y, si está configurado, a Google.
7. Registra todo en `ssen-private/logs/indexing_api_sitemap.log` (fuera del documento raíz).

## Notificación a buscadores

### IndexNow

- Cubre Bing, Yandex, Seznam, Naver, Yep, Internet Archive y Amazonbot.
- **Google no participa en IndexNow.**
- Envío en lote de hasta 10.000 URLs por solicitud; el proyecto trocea automáticamente.
- No existe campo de tipo: las eliminadas se envían igual que las altas y actualizaciones.

### API de indexación de Google (opcional)

- Solo admite páginas con datos estructurados `JobPosting` o `BroadcastEvent`; **no es una notificación general**.
- `URL_DELETED` requiere que la URL devuelva 404 o 410; el proyecto lo comprueba antes de enviarla.
- Cuota predeterminada: 200 envíos por día y proyecto.
- Si la cuenta de servicio no es propietaria en Search Console, la API responde 403.
- Sin credenciales válidas, el sitemap se genera igualmente y la rama de Google queda deshabilitada.

## Configuración

La fuente única de verdad es `core_index/config/config.php`. Valores destacados:

| Constante | Descripción |
|---|---|
| `BASE_URL` | Dominio base, sin barra final |
| `INDEXNOW_HOST` | Host para IndexNow, sin protocolo |
| `INDEXNOW_KEY_FILE` | Ruta del archivo de clave (`core_index/config/indexnow_key.txt`) |
| `SITEMAP_PATH` / `SITEMAP_OLD_PATH` | Sitemap y copia anterior, en la raíz del sitio |
| `LOG_PATH` | Registro único fuera del documento raíz (`PRIVATE_DIR/logs`) |
| `AUTH_USERS_FILE` | Archivo de usuarios de la interfaz web (`PRIVATE_DIR/.htpasswd`) |
| `GOOGLE_CREDENTIALS_PATH` | Credenciales fuera del documento raíz (`PRIVATE_DIR`) |
| `MAX_URLS_PER_SITEMAP` | Límite de entradas (50.000, máximo del protocolo) |
| `GOOGLE_DAILY_QUOTA` | Cuota diaria de la API de Google |
| `WEB_RATE_LIMIT_SECONDS` | Segundos mínimos entre ejecuciones web |
| `LOG_ENABLED` / `LOG_MAX_SIZE` | Registro y rotación |

## Seguridad

- La clave de IndexNow y las credenciales de Google **no se versionan** (véase `.gitignore`).
- El archivo de clave se carga desde disco; las credenciales viven fuera del documento raíz.
- La interfaz web exige autenticación básica a nivel de aplicación (funciona en Apache, LiteSpeed y nginx), tiene límite de frecuencia y comparte bloqueo con el cron.
- El registro y las credenciales viven fuera del documento raíz; en servidores que leen `.htaccess` se deniega además el acceso a `config`, `data` y `tools`.
- Detalles y rotación de credenciales en [`SECURITY.md`](SECURITY.md).

## Pruebas

```bash
php tests/run.php
```

El arnés es nativo, sin dependencias. Ejecute `php tests/run.php`; el proyecto se ha verificado en PHP 8.1 a 8.4 (recomendado 8.4) y las propias pruebas validan el sitemap contra el esquema oficial.

## Documentación

- [`docs/INSTALACION_Y_DESPLIEGUE.md`](docs/INSTALACION_Y_DESPLIEGUE.md): instalación, despliegue, cron y verificación.
- [`SECURITY.md`](SECURITY.md): secretos, rotación y comunicación de incidencias.
- [`CHANGELOG.md`](CHANGELOG.md): historial de cambios.
- `manuales/`: manuales de referencia (fundamentos, configuración, operación y mantenimiento).

## Licencia

MIT. Véase [`LICENSE`](LICENSE).
