# Política de seguridad de SSEN

## Versiones admitidas

La versión admitida es la última publicada en la rama `main`. Las versiones anteriores no reciben correcciones.

## Gestión de secretos

- La clave de IndexNow se guarda en `core_index/config/indexnow_key.txt` y **no se versiona** (véase `.gitignore`). El archivo público `{clave}.txt` es, por diseño, accesible desde el sitio.
- Las credenciales de Google (`credentials.json`) se guardan **fuera del documento raíz** (constante `PRIVATE_DIR`, por defecto `../ssen-private/`), con permisos `600`.
- Nunca se versionan credenciales, registros ni sitemaps generados.

### Rotación

- **Clave de IndexNow**: ejecute `php core_index/tools/generate_indexnow_key.php --force`. La herramienta sustituye el archivo de clave, publica el nuevo `{clave}.txt` y retira el anterior. Tras rotar, compruebe que la URL pública devuelve la nueva clave.
- **Credenciales de Google**: cree una clave nueva en la cuenta de servicio, sustituya `credentials.json` y revoque la anterior en Google Cloud.

## Protección de la interfaz web

- La interfaz `core_index/sitemap_form_url.php` exige autenticación básica a nivel de aplicación (`core_index/auth_guard.php`, válida en Apache, LiteSpeed y nginx), aplica un límite de frecuencia y comparte bloqueo con el cron.
- Los directorios `config`, `logs`, `data` y `tools` deniegan el acceso web.
- El archivo de usuarios (`AUTH_USERS_FILE`, por defecto `../ssen-private/.htpasswd`) vive fuera del documento raíz, se crea con `htpasswd -B` y tiene permisos 600.

## Prácticas recomendadas

- Acceda siempre por HTTPS; la autenticación básica viaja sin cifrar.
- Revise periódicamente el registro `../ssen-private/logs/indexing_api_sitemap.log` (fuera del documento raíz).
- Mantenga PHP actualizado (PHP 8.1 alcanzó el fin de soporte el 31 de diciembre de 2025; se recomienda 8.2 o superior).
- Ejecute `php core_index/tools/check_config.php` tras cualquier cambio de configuración.

## Comunicación de incidencias

Comunique las vulnerabilidades mediante el canal privado del repositorio (avisos de seguridad) o directamente al mantenedor, sin abrir una incidencia pública. Incluya pasos de reproducción, versión afectada e impacto estimado. No incluya claves ni credenciales reales en la comunicación.

## Alcance

Quedan fuera del alcance los servicios de terceros (IndexNow, Google, el proveedor de alojamiento) y las modificaciones locales no publicadas en el repositorio.
