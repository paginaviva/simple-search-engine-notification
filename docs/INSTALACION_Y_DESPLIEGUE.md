# Instalación y despliegue de SSEN

Guía para desplegar el proyecto en un alojamiento con PHP 8.1 o superior (Apache, LiteSpeed o nginx) y, preferiblemente, cPanel o Hestia.

## 1. Requisitos

- PHP 8.1 o superior (probado hasta 8.4) con las extensiones `openssl`, `curl`, `json` y `simplexml`.
- Servidor web Apache, LiteSpeed o nginx. La autenticación de la interfaz web se aplica en la aplicación, por lo que **no depende de `.htaccess`**.
- Certificado HTTPS activo.
- Acceso de escritura al directorio del proyecto (para `sitemap.xml`) y al directorio privado de registros (`PRIVATE_DIR/logs`).

Compruebe el entorno en el servidor:

```bash
php core_index/tools/check_config.php
```

## 2. Qué se sube y qué no

Se sube el contenido del repositorio, con estas excepciones que nunca deben desplegarse tal cual desde el repositorio:

- `core_index/config/indexnow_key.txt` y `{clave}.txt` de la raíz: se generan en el servidor (paso 6).
- `../ssen-private/`: se crea fuera del documento raíz (pasos 4 y 7).
- `sitemap.xml` y `sitemap.old.xml`: se generan en la primera ejecución.

## 3. Permisos

- Los archivos PHP: `0644`.
- La raíz del proyecto: escribible por el usuario que ejecuta PHP (para crear `sitemap.xml`).
- `../ssen-private/`: `0700`, y su subdirectorio `logs/` escribible por el usuario que ejecuta PHP (el mismo del proceso web y del cron).
- Nunca `0777`.

## 4. Directorio privado (credenciales, registros y autenticación)

Todo lo sensible vive **fuera del documento raíz**:

```bash
mkdir -p /home/USUARIO/ssen-private/logs
chmod 700 /home/USUARIO/ssen-private
```

- Credenciales de Google (opcional): `ssen-private/credentials.json`, con `chmod 600`.
- Archivo de usuarios de la interfaz web: `ssen-private/.htpasswd` (paso 7).
- Registro: `ssen-private/logs/indexing_api_sitemap.log`.

Las rutas se derivan de la constante `PRIVATE_DIR` en `core_index/config/config.php`. Si no hay credenciales, la notificación a Google queda deshabilitada con una advertencia, sin bloquear el resto. Si el alojamiento aplica una restricción `open_basedir` (habitual en Hestia), ajuste `PRIVATE_DIR` a una ruta permitida, por ejemplo `dirname(ROOT_DIR) . '/private'`.

## 5. Configuración

Edite `core_index/config/config.php`:

```php
define('BASE_URL', 'https://su-dominio.com');   // sin barra final
define('INDEXNOW_HOST', 'su-dominio.com');       // sin protocolo
```

Si el dominio canónico lleva `www`, use el host con `www` en ambas constantes. Revise, si lo necesita, `WEB_RATE_LIMIT_SECONDS`, `GOOGLE_DAILY_QUOTA` y `LOG_MAX_SIZE`. Después ejecute la comprobación del paso 1.

## 6. Clave de IndexNow

```bash
php core_index/tools/generate_indexnow_key.php
```

La herramienta escribe:

- `core_index/config/indexnow_key.txt` (clave; no versionada).
- `{clave}.txt` en la raíz del sitio (archivo público de verificación).

Verifique que `https://su-dominio.com/{clave}.txt` devuelve exactamente la clave. Para rotarla, repita el comando con `--force`.

## 7. Protección de la interfaz web

La interfaz `core_index/sitemap_form_url.php` exige autenticación básica **a nivel de aplicación** (`core_index/auth_guard.php`), por lo que funciona igual en Apache, LiteSpeed y nginx.

1. Cree el archivo de usuarios fuera del documento raíz:

   ```bash
   htpasswd -B -c /home/USUARIO/ssen-private/.htpasswd operador
   chmod 600 /home/USUARIO/ssen-private/.htpasswd
   ```

   La ruta debe coincidir con la constante `AUTH_USERS_FILE` (por defecto, `PRIVATE_DIR/.htpasswd`).

2. Si su servidor lee `.htaccess` (Apache o LiteSpeed), los archivos incluidos deniegan además el acceso web a `config`, `data` y `tools`. En nginx se ignoran, pero esos directorios no contienen secretos: el registro está fuera del documento raíz y la clave de IndexNow es pública por diseño.

3. Acceda siempre por HTTPS.

## 8. Tarea programada (cron)

En cPanel, «Cron Jobs» (Tareas programadas), o en Hestia, «Cron». Use la ruta absoluta del intérprete y del proyecto:

```cron
0 3 * * * /usr/local/bin/php /home/USUARIO/public_html/core_index/sitemap_generator.php --source=json >> /home/USUARIO/ssen-private/logs/cron.log 2>&1
```

- El bloqueo del generador evita que dos ejecuciones se solapen; si hay una en curso, la segunda termina con código 3.
- La autenticación de la interfaz web no afecta al cron.

## 9. Primera ejecución y verificación

```bash
php core_index/sitemap_generator.php --source=json
```

Lista de verificación:

- [ ] `sitemap.xml` aparece en la raíz y es XML válido (`xmllint --noout sitemap.xml`).
- [ ] `ssen-private/logs/indexing_api_sitemap.log` contiene las líneas de la ejecución.
- [ ] IndexNow responde 200 o 202 (o 403 si el archivo de clave no es público).
- [ ] `https://su-dominio.com/{clave}.txt` devuelve la clave.
- [ ] Con credenciales de Google: no hay error 403 de propiedad en Search Console.
- [ ] La interfaz web pide usuario y contraseña (401 sin credenciales) y aplica el límite de frecuencia.

## 10. API de indexación de Google (opcional)

- Solo admite páginas con datos estructurados `JobPosting` o `BroadcastEvent`.
- `URL_DELETED` requiere que la URL devuelva 404 o 410; el proyecto lo comprueba antes de enviarla.
- Cuota predeterminada: 200 envíos por día y proyecto.
- La cuenta de servicio debe figurar como propietaria en Search Console.

## 11. Actualizaciones y reversión

- Antes de actualizar, copie `core_index/` y el sitemap actual.
- Tras actualizar, repita el paso 1 y ejecute las pruebas si dispone de PHP: `php tests/run.php`.
- Para revertir, restaure la copia y vuelva a ejecutar el generador.

## 12. Problemas frecuentes

| Síntoma | Causa probable | Solución |
|---|---|---|
| La interfaz web devuelve 500 | `AUTH_USERS_FILE` no existe o no es legible | Cree el archivo (paso 7) o ajuste la constante |
| La interfaz web devuelve 500 con avisos de `open_basedir` | `PRIVATE_DIR` está fuera de las rutas permitidas por PHP | Ajuste `PRIVATE_DIR` a una ruta permitida (por ejemplo, la carpeta `private` del sitio) |
| La interfaz web devuelve 401 de forma continua | Credenciales incorrectas o archivo de usuarios con formato no compatible | Regeneración con `htpasswd -B` |
| La interfaz web sigue abierta sin credenciales | `WEB_AUTH_ENABLED` está en falso | Actívelo en `core_index/config/config.php` |
| IndexNow responde 403 | El archivo `{clave}.txt` no es accesible o no coincide | Repita el paso 6 y compruebe la URL pública |
| IndexNow responde 422 | Las URLs no pertenecen al host o la clave no coincide | Revise `BASE_URL` e `INDEXNOW_HOST` (incluido `www`) |
| Google responde 403 | La cuenta de servicio no es propietaria en Search Console | Añádala como propietaria delegada |
| Google responde 429 | Cuota diaria agotada | Espere al día siguiente; revise `GOOGLE_DAILY_QUOTA` |
| No se escribe el registro | `ssen-private/logs/` sin permisos | Ajuste permisos (paso 3) |
| No se crea `sitemap.xml` | La raíz del proyecto no es escribible | Ajuste permisos (paso 3) |
| El sitemap no se actualiza | Ejecución en curso (código 3) o error de configuración (código 2) | Revise el registro y el mensaje de error |
