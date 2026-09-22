# Historial de cambios

El formato sigue, de manera simplificada, las convenciones de «Keep a Changelog». El versionado es semántico.

## [1.1.0] — 2026-09-21

Remediación derivada de la auditoría del 2026-09-21: corrección de las tres severidades críticas, de las siete altas y de la deuda técnica media y baja.

### Añadido

- Arnés de pruebas nativo (`tests/run.php`) con 30 casos de configuración, diferencias, carga de entradas, generador, registro, autenticación, IndexNow y HTTP; verificado en PHP 8.1 a 8.4.
- Herramientas de línea de comandos: `core_index/tools/check_config.php` y `core_index/tools/generate_indexnow_key.php`.
- Módulo `core_index/urls_loader.php` con validación de los contratos JSON y CSV.
- Bloque principal de línea de comandos en `core_index/sitemap_generator.php`, con bloqueo de ejecución y códigos de salida.
- Protección de la interfaz web con autenticación básica a nivel de aplicación, límite de frecuencia y bloqueo compartido con el cron.
- Guía `docs/INSTALACION_Y_DESPLIEGUE.md` y política `SECURITY.md`.
- Registro con rotación por tamaño, bloqueo de escritura y saneado de todos los campos.
- Autenticación básica a nivel de aplicación (`core_index/auth_guard.php`), válida en Apache, LiteSpeed y nginx.
- Guardas de interfaz: las herramientas y el arnés de pruebas rechazan la ejecución web.

### Cambiado

- `ROOT_DIR` se calcula con `dirname(__DIR__, 2)`; las rutas derivadas apuntan a la raíz del proyecto.
- El registro y las credenciales pasan a un directorio privado fuera del documento raíz (`PRIVATE_DIR`).
- `validateConfiguration()` devuelve errores y advertencias; la falta de credenciales de Google ya no bloquea.
- La interfaz web elimina la dependencia del CDN de Bootstrap y su estado refleja el resultado real.
- Los manuales y la documentación se alinean con el contrato de entrada real (`loc`), las rutas efectivas y los límites de las API.

### Corregido

- El núcleo canónico arranca: rutas base, sitemap, copia y registro coherentes.
- El sitemap escapa los cuatro campos y valida `loc`, `lastmod`, `changefreq` y `priority`; se aplica `MAX_URLS_PER_SITEMAP`.
- Google recibe `URL_UPDATED` para nuevas y actualizadas, y `URL_DELETED` solo tras comprobar 404 o 410.
- IndexNow trocea en lotes de hasta 10.000 URLs y usa las constantes de tiempo de espera.
- Se distinguen ausencia y corrupción del sitemap anterior, evitando renotificaciones masivas.
- Se aplican la cuota diaria de Google y la lista blanca del parámetro `source`.
- Retirada de `openssl_free_key()` (obsoleto desde PHP 8.0).
- Aviso de obsolescencia de `fgetcsv()` en PHP 8.4 (parámetro `escape` explícito).

### Seguridad

- Clave de IndexNow rotada; la clave y su archivo público dejan de versionarse.
- `.gitignore` con secretos, credenciales, registros y sitemaps generados.
- Registro, credenciales y autenticación fuera del documento raíz; en Apache y LiteSpeed se deniega además el acceso a `config`, `data` y `tools`.

## [1.0.0] — 2026-01-10

### Añadido

- Versión inicial: nueve módulos del núcleo (`core_index/`), interfaz web, generación de sitemap, diferencias, clientes de IndexNow y de la API de indexación de Google, registro y configuración centralizada.
