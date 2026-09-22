# Manual de Configuración SSEN - Parte 3
## Operación y Diagnóstico

**Subfase:** 3.3  
**Capítulos:** 5-6 (Ejecución del Sistema + Solución de Problemas)  
**Fecha:** 10 de enero de 2026  
**Proyecto:** Simple Search Engine Notification (SSEN)

---

## Capítulo 5: Ejecución del Sistema

### 5.1 Ejecución Manual desde Navegador

La interfaz web permite ejecutar el sistema manualmente y visualizar resultados en tiempo real. Está protegida con autenticación básica a nivel de aplicación (`core_index/auth_guard.php`), aplica un límite de frecuencia (`WEB_RATE_LIMIT_SECONDS`, 60 segundos por defecto) y comparte el bloqueo de ejecución con el cron.

#### Opción A: Con archivo JSON

**URL de acceso:**
```
https://tu-dominio.com/core_index/sitemap_form_url.php?source=json
```

**Parámetros:**
- `source=json`: Indica que se cargará el archivo `urls.json`

**Ejemplo completo:**
```
https://ejemplo.com/core_index/sitemap_form_url.php?source=json
```

**Proceso ejecutado:**
1. Sistema carga `core_index/data/urls.json`
2. Valida formato y estructura
3. Genera `sitemap.xml`
4. Compara con `sitemap.old.xml`
5. Envía notificaciones a IndexNow
6. Envía notificaciones a Google Indexing API
7. Muestra página de resultados

#### Opción B: Con archivo CSV

**URL de acceso:**
```
https://tu-dominio.com/core_index/sitemap_form_url.php?source=csv
```

**Parámetros:**
- `source=csv`: Indica que se cargará el archivo `urls.csv`

**Ejemplo completo:**
```
https://ejemplo.com/core_index/sitemap_form_url.php?source=csv
```

**Proceso idéntico a JSON pero con archivo CSV**

#### Página de Resultados

Después de la ejecución, el sistema muestra una página de resultados con:

**Información del Sitemap:**
- Total de URLs procesadas
- Archivo generado: `sitemap.xml`
- Fecha y hora de generación

**Cambios Detectados:**
- 🆕 **URLs Nuevas:** No existían en sitemap anterior
- ✏️ **URLs Actualizadas:** Cambio en lastmod o priority
- ❌ **URLs Eliminadas:** Ya no están en la lista actual
- ℹ️ **Sin Cambios:** No hay diferencias

**Estado de Notificaciones:**
- **IndexNow:** Código HTTP y mensaje de respuesta
- **Google Indexing API:** Código HTTP y mensaje de respuesta

**Ejemplo visual de resultados:**
```
┌─────────────────────────────────────────────────────┐
│ Generación de Sitemap - Resultados                 │
├─────────────────────────────────────────────────────┤
│ ✓ Sitemap generado: sitemap.xml                    │
│ ✓ Total de URLs: 25                                │
│ ✓ Fecha: 2026-01-10 14:30:15                       │
├─────────────────────────────────────────────────────┤
│ Cambios Detectados:                                 │
│   • 3 URLs nuevas                                   │
│   • 2 URLs actualizadas                             │
│   • 1 URL eliminada                                 │
├─────────────────────────────────────────────────────┤
│ Notificaciones Enviadas:                            │
│   IndexNow: ✓ HTTP 200 (5 URLs notificadas)       │
│   Google: ✓ HTTP 200 (6 operaciones exitosas)     │
└─────────────────────────────────────────────────────┘
```

#### Protección de Acceso (Obligatoria)

La protección con autenticación es obligatoria: la interfaz escribe el sitemap y consume cuota de las APIs. La autenticación se aplica en `core_index/auth_guard.php` (a nivel de aplicación), por lo que funciona igual en Apache, LiteSpeed y nginx.

**Activar la autenticación:**
La constante `WEB_AUTH_ENABLED` de `core_index/config/config.php` activa la autenticación.

**Crear el archivo de usuarios fuera del documento raíz:**
```bash
htpasswd -B -c ../ssen-private/.htpasswd operador
```

La ruta se define en la constante `AUTH_USERS_FILE` (por defecto, `PRIVATE_DIR/.htpasswd`).

**Denegación adicional en Apache y LiteSpeed:**
Los archivos `.htaccess` incluidos solo añaden denegaciones de acceso en servidores que los leen (Apache y LiteSpeed); en nginx se ignoran. Como el registro y las credenciales están fuera del documento raíz, no hay secretos expuestos.

**Respuestas de la interfaz:**
- Sin archivo de usuarios: 500
- Sin credenciales o con credenciales incorrectas: 401
- Dentro del límite de frecuencia: 429 (indica los segundos de espera)
- Con una generación en curso: 409 (bloqueo compartido con el cron)

### 5.2 Ejecución Automatizada con Cron

Cron permite programar ejecuciones automáticas del sistema sin intervención manual.

#### Configuración Básica

**Abrir editor de cron:**
```bash
crontab -e
```

**Sintaxis de cron:**
```
* * * * * comando
│ │ │ │ │
│ │ │ │ └─── Día de la semana (0-7, 0 y 7 = Domingo)
│ │ │ └───── Mes (1-12)
│ │ └─────── Día del mes (1-31)
│ └───────── Hora (0-23)
└─────────── Minuto (0-59)
```

#### Ejemplo 1: Actualizar cada 6 horas

```bash
0 */6 * * * /usr/bin/php /ruta/completa/core_index/sitemap_generator.php > /dev/null 2>&1
```

**Explicación:**
- `0`: En el minuto 0
- `*/6`: Cada 6 horas
- `*`: Todos los días del mes
- `*`: Todos los meses
- `*`: Todos los días de la semana
- `> /dev/null 2>&1`: Descarta salida estándar y errores

**Horarios de ejecución:**
- 00:00, 06:00, 12:00, 18:00

#### Ejemplo 2: Ejecución diaria a las 3:00 AM

```bash
0 3 * * * /usr/bin/php /var/www/html/simple-search-engine-notification/core_index/sitemap_generator.php
```

**Recomendado para:**
- Sitios con actualizaciones diarias
- Blogs con publicaciones regulares
- Sitios corporativos con cambios moderados

#### Ejemplo 3: Cada hora

```bash
0 * * * * /usr/bin/php /ruta/completa/core_index/sitemap_generator.php
```

**Recomendado para:**
- Portales de noticias
- E-commerce con inventario muy dinámico
- Sitios con actualización continua

#### Ejemplo 4: Cada lunes a las 8:00 AM

```bash
0 8 * * 1 /usr/bin/php /ruta/completa/core_index/sitemap_generator.php
```

**Recomendado para:**
- Sitios con actualizaciones semanales
- Blogs con calendario de publicación semanal

#### Ejemplo 5: Cada 2 horas entre 8 AM y 6 PM

```bash
0 8-18/2 * * * /usr/bin/php /ruta/completa/core_index/sitemap_generator.php
```

**Horarios de ejecución:**
- 08:00, 10:00, 12:00, 14:00, 16:00, 18:00

**Recomendado para:**
- Optimizar horarios de trabajo
- Evitar ejecución durante la noche

#### Guardar Logs en Archivo

**Con registro de fecha y hora:**
```bash
0 */6 * * * /usr/bin/php /ruta/completa/core_index/sitemap_generator.php >> /var/log/ssen_cron.log 2>&1
```

**Con rotación diaria de logs:**
```bash
0 */6 * * * /usr/bin/php /ruta/completa/core_index/sitemap_generator.php >> /var/log/ssen_$(date +\%Y\%m\%d).log 2>&1
```

#### Verificar Tareas Programadas

**Listar cron jobs del usuario:**
```bash
crontab -l
```

**Ver logs de ejecución de cron (según sistema):**
```bash
# Ubuntu/Debian
grep CRON /var/log/syslog

# CentOS/RHEL
grep CRON /var/log/cron

# Ver últimas 20 líneas
tail -20 /var/log/syslog | grep CRON
```

#### Tabla de Frecuencias Recomendadas

| Tipo de Sitio | Frecuencia | Cron Expression | Justificación |
|---------------|-----------|-----------------|---------------|
| **Blog personal** | 1 vez al día | `0 3 * * *` | Publicaciones diarias o menos frecuentes |
| **Blog corporativo** | Cada 6 horas | `0 */6 * * *` | Múltiples publicaciones diarias |
| **Portal de noticias** | Cada hora | `0 * * * *` | Contenido en tiempo real |
| **E-commerce** | Cada 2 horas | `0 */2 * * *` | Inventario dinámico |
| **Sitio corporativo** | 1 vez al día | `0 3 * * *` | Cambios esporádicos |
| **Documentación** | 1 vez a la semana | `0 8 * * 1` | Actualizaciones semanales |
| **Sitio estático** | 1 vez al mes | `0 3 1 * *` | Cambios muy raros |

### 5.3 Ejecución desde Línea de Comandos

Método ideal para pruebas, depuración y ejecución manual controlada.

#### Comando Básico

```bash
# Navegar al directorio del proyecto
cd /ruta/al/proyecto

# Ejecutar generador con el archivo JSON (opción por defecto)
php core_index/sitemap_generator.php

# Ejecutar generador con el archivo CSV
php core_index/sitemap_generator.php --source=csv
```

**Opciones:**
- `--source=json|csv`: origen de datos (por defecto: json)
- `--help`: muestra el uso del comando

**Salida esperada:**
```
Sitemap generado: /ruta/al/proyecto/sitemap.xml
URLs: 25 | Nuevas: 3 | Actualizadas: 2 | Eliminadas: 1
IndexNow: URLs enviadas correctamente a IndexNow
Google: Google deshabilitado: no hay credenciales en /ruta/al/proyecto/../ssen-private/credentials.json
```

**Bloqueo y códigos de salida:**
El comando adquiere un bloqueo de ejecución compartido con la interfaz web; si ya hay una generación en marcha, termina sin hacer cambios.

| Código | Significado |
|--------|-------------|
| **0** | Ejecución correcta |
| **1** | Error de ejecución (carga de URLs o generación) |
| **2** | Error de configuración o de origen no admitido |
| **3** | Ya hay una ejecución en curso |

#### Ejecutar con Ruta Absoluta

```bash
/usr/bin/php /var/www/html/simple-search-engine-notification/core_index/sitemap_generator.php
```

**Ventaja:** Funciona desde cualquier directorio

#### Ejecutar en Segundo Plano

```bash
php core_index/sitemap_generator.php > /tmp/ssen_output.log 2>&1 &
```

**Explicación:**
- `> /tmp/ssen_output.log`: Redirige salida a archivo
- `2>&1`: Redirige errores a la misma salida
- `&`: Ejecuta en segundo plano

**Ver proceso en ejecución:**
```bash
ps aux | grep sitemap_generator
```

**Ver log en tiempo real:**
```bash
tail -f /tmp/ssen_output.log
```

#### Ejecutar con Timeout

```bash
timeout 300 php core_index/sitemap_generator.php
```

**Explicación:**
- Ejecuta el script con límite de 5 minutos (300 segundos)
- Si excede el tiempo, se termina automáticamente

**Recomendado para:**
- Evitar procesos colgados
- Limitar uso de recursos del servidor

#### Comprobar la Configuración

```bash
# Validar configuración, extensiones y rutas antes de generar
php core_index/tools/check_config.php
```

**Códigos de salida:** 0 si la configuración es correcta; 2 si hay errores de configuración o extensiones ausentes.

#### Script Wrapper para Ejecución

**Crear script de wrapper:**
```bash
nano run_ssen.sh
```

**Contenido:**
```bash
#!/bin/bash

# Script wrapper para SSEN
# Ejecuta sitemap_generator.php con logging y notificaciones

PROJECT_DIR="/var/www/html/simple-search-engine-notification"
LOG_DIR="/var/log/ssen"
LOG_FILE="$LOG_DIR/execution_$(date +%Y%m%d_%H%M%S).log"
EMAIL_NOTIFY="admin@ejemplo.com"

# Crear directorio de logs si no existe
mkdir -p "$LOG_DIR"

# Header del log
echo "======================================" >> "$LOG_FILE"
echo "SSEN - Ejecución iniciada" >> "$LOG_FILE"
echo "Fecha: $(date)" >> "$LOG_FILE"
echo "======================================" >> "$LOG_FILE"

# Ejecutar generador
cd "$PROJECT_DIR"
/usr/bin/php core_index/sitemap_generator.php >> "$LOG_FILE" 2>&1

# Verificar código de salida
EXIT_CODE=$?

if [ $EXIT_CODE -eq 0 ]; then
    echo "✓ Ejecución exitosa" >> "$LOG_FILE"
else
    echo "✗ Ejecución falló con código: $EXIT_CODE" >> "$LOG_FILE"
    
    # Enviar notificación por email (opcional)
    echo "Error en SSEN - Ver log: $LOG_FILE" | mail -s "SSEN Error" "$EMAIL_NOTIFY"
fi

echo "======================================" >> "$LOG_FILE"
echo "Ejecución finalizada" >> "$LOG_FILE"
echo "======================================" >> "$LOG_FILE"

# Limpiar logs antiguos (más de 30 días)
find "$LOG_DIR" -name "execution_*.log" -mtime +30 -delete

exit $EXIT_CODE
```

**Hacer ejecutable:**
```bash
chmod +x run_ssen.sh
```

**Ejecutar wrapper:**
```bash
./run_ssen.sh
```

**Usar en cron:**
```bash
0 */6 * * * /ruta/al/proyecto/run_ssen.sh
```

### 5.4 Interpretación de Resultados

#### Estados de URLs

La página de resultados clasifica las URLs en cuatro categorías:

##### 1. ✅ URLs Nuevas

**Descripción:**
URLs que no existían en el sitemap anterior (`sitemap.old.xml`).

**Acciones automáticas:**
- Agregadas al nuevo `sitemap.xml`
- Notificadas a IndexNow
- Notificadas a Google con tipo `URL_UPDATED`

**Ejemplo de log:**
```
[2026-01-10 14:30:15] Operation: UPDATED | Service: INDEXNOW | URL: https://ejemplo.com/nueva-pagina | HTTP: 200 | Status: SUCCESS | Message: URLs enviadas correctamente a IndexNow
[2026-01-10 14:30:18] Operation: UPDATED | Service: GOOGLE_INDEXING | URL: https://ejemplo.com/nueva-pagina | HTTP: 200 | Status: SUCCESS | Message: Notificación aceptada
```

**Cuándo ocurre:**
- Publicación de nuevo contenido
- Creación de nuevas páginas de productos
- Nuevas categorías o secciones

##### 2. ✏️ URLs Actualizadas

**Descripción:**
URLs que ya existían pero tienen cambios en `lastmod` o `priority`.

**Acciones automáticas:**
- Actualizadas en `sitemap.xml`
- Notificadas a IndexNow
- Notificadas a Google con tipo `URL_UPDATED`

**Ejemplo de log:**
```
[2026-01-10 14:30:16] Operation: UPDATED | Service: INDEXNOW | URL: https://ejemplo.com/articulo-modificado | HTTP: 200 | Status: SUCCESS | Message: URLs enviadas correctamente a IndexNow
[2026-01-10 14:30:19] Operation: UPDATED | Service: GOOGLE_INDEXING | URL: https://ejemplo.com/articulo-modificado | HTTP: 200 | Status: SUCCESS | Message: Notificación aceptada
```

**Cuándo ocurre:**
- Actualización de contenido existente
- Correcciones o mejoras en páginas
- Cambio de prioridad de indexación

##### 3. ❌ URLs Eliminadas

**Descripción:**
URLs que existían en `sitemap.old.xml` pero ya no están en la lista actual.

**Acciones automáticas:**
- Removidas del nuevo `sitemap.xml`
- Notificadas a IndexNow (el protocolo no distingue tipos: se envían junto con el resto)
- Notificadas a Google con tipo `URL_DELETED`, y solo si la URL devuelve 404 o 410; en caso contrario la eliminación se omite y queda registrada

**Ejemplo de log:**
```
[2026-01-10 14:30:20] Operation: DELETED | Service: GOOGLE_INDEXING | URL: https://ejemplo.com/pagina-eliminada | HTTP: 200 | Status: SUCCESS | Message: Notificación aceptada
```

**Cuándo ocurre:**
- Eliminación de contenido obsoleto
- Productos fuera de stock permanentemente
- Reestructuración del sitio

**⚠️ IMPORTANTE:**
- La notificación `URL_DELETED` solo está admitida para páginas con datos estructurados `JobPosting` o `BroadcastEvent`, y exige que la URL devuelva 404 o 410
- Las eliminaciones que no devuelven 404 ni 410 se omiten

##### 4. ℹ️ Sin Cambios

**Descripción:**
No hay diferencias entre `sitemap.xml` y `sitemap.old.xml`.

**Acciones automáticas:**
- Sitemap regenerado (mismas URLs)
- **NO** se envían notificaciones
- Logs no registran actividad de notificación

**Cuándo ocurre:**
- Ejecución sin cambios en contenido
- Ejecuciones consecutivas sin actualizar `urls.json`
- Sistema en estado estable

**Optimización:**
El sistema evita notificaciones innecesarias para conservar cuotas de API.

#### Códigos de Estado HTTP

##### IndexNow API

| Código | Significado | Acción Recomendada |
|--------|-------------|-------------------|
| **200** | Éxito | ✓ URLs recibidas y procesadas correctamente |
| **202** | Aceptado | ✓ URLs en cola de procesamiento |
| **400** | Bad Request | ✗ Verificar formato de URLs, corregir sintaxis |
| **403** | Forbidden | ✗ Clave inválida, verificar archivo en raíz del sitio |
| **422** | Unprocessable | ✗ URLs inválidas o duplicadas, validar lista |
| **429** | Too Many Requests | ⚠ Reducir frecuencia de notificaciones |
| **500** | Server Error | ⚠ Error del servidor IndexNow, reintentar más tarde |

**Ejemplo de respuesta exitosa:**
```json
{
  "status": "success",
  "urls_submitted": 5
}
```

##### Google Indexing API

| Código | Significado | Acción Recomendada |
|--------|-------------|-------------------|
| **200** | Éxito | ✓ URL notificada correctamente |
| **400** | Bad Request | ✗ Formato de solicitud inválido |
| **401** | Unauthorized | ✗ Token inválido o expirado, verificar credenciales |
| **403** | Forbidden | ✗ Sin permisos en Search Console, agregar cuenta como propietario |
| **429** | Too Many Requests | ⚠ Límite de cuota excedido, implementar delay mayor |
| **500** | Server Error | ⚠ Error de Google, reintentar más tarde |
| **503** | Service Unavailable | ⚠ Servicio temporalmente no disponible |

**Ejemplo de respuesta exitosa:**
```json
{
  "urlNotificationMetadata": {
    "url": "https://ejemplo.com/pagina",
    "latestUpdate": {
      "url": "https://ejemplo.com/pagina",
      "type": "URL_UPDATED",
      "notifyTime": "2026-01-10T14:30:18Z"
    }
  }
}
```

#### Registro del Sistema

El registro se encuentra fuera del documento raíz, en `../ssen-private/logs/indexing_api_sitemap.log` (`LOG_PATH`, dentro de `PRIVATE_DIR`):

```
[2026-01-10 14:30:15] Operation: UPDATED | Service: INDEXNOW | URL: https://ejemplo.com/pagina1 | HTTP: 200 | Status: SUCCESS | Message: URLs enviadas correctamente a IndexNow
[2026-01-10 14:30:18] Operation: UPDATED | Service: GOOGLE_INDEXING | URL: https://ejemplo.com/pagina1 | HTTP: 200 | Status: SUCCESS | Message: Notificación aceptada
[2026-01-10 14:30:20] Operation: DELETED | Service: GOOGLE_INDEXING | URL: https://ejemplo.com/pagina-eliminada | HTTP: 200 | Status: SUCCESS | Message: Notificación aceptada
```

**Formato:**
```
[YYYY-MM-DD HH:MM:SS] Operation: TIPO | Service: SERVICIO | URL: url | HTTP: código | Status: ESTADO | Message: mensaje
```

**Ver últimas notificaciones:**
```bash
# Últimas 20 líneas del registro
tail -20 ../ssen-private/logs/indexing_api_sitemap.log

# Buscar errores
grep -E "Status: (FAILED|ERROR)" ../ssen-private/logs/indexing_api_sitemap.log

# Contar notificaciones exitosas de hoy
grep "$(date +%Y-%m-%d)" ../ssen-private/logs/indexing_api_sitemap.log | grep -c "Status: SUCCESS"
```

---

## Capítulo 6: Solución de Problemas

### 6.1 Error: "IndexNow key not found"

#### Descripción del Error

```
Uncaught RuntimeException: No existe el archivo de clave de IndexNow: core_index/config/indexnow_key.txt
```

#### Causas

1. Archivo `indexnow_key.txt` no existe
2. Archivo existe pero está vacío
3. Permisos de lectura incorrectos
4. Ruta incorrecta en `config.php`

#### Solución

**Paso 1: Verificar existencia del archivo**
```bash
ls -la core_index/config/indexnow_key.txt
```

**Si no existe, generar clave:**
```bash
# Recomendado: genera la clave y el archivo de verificación en la raíz del sitio
php core_index/tools/generate_indexnow_key.php

# Alternativa con OpenSSL
openssl rand -hex 16 > core_index/config/indexnow_key.txt
```

**Paso 2: Verificar contenido**
```bash
cat core_index/config/indexnow_key.txt
```

**Debe mostrar una clave de entre 8 y 128 caracteres alfanuméricos o guion:**
```
{clave}
```

**Si está vacío, regenerar:**
```bash
php core_index/tools/generate_indexnow_key.php --force
```

**Paso 3: Verificar permisos**
```bash
chmod 644 core_index/config/indexnow_key.txt
```

**Paso 4: Publicar en raíz del sitio**
```bash
KEY=$(cat core_index/config/indexnow_key.txt)
cp core_index/config/indexnow_key.txt /var/www/html/${KEY}.txt

# Verificar accesibilidad
curl https://tu-dominio.com/${KEY}.txt
```

### 6.2 Error: "Google credentials file not found"

#### Descripción del Error

```
Google deshabilitado: no hay credenciales en ../ssen-private/credentials.json
```

#### Causas

1. Archivo `credentials.json` no descargado de Google Cloud
2. Archivo en ubicación incorrecta
3. Nombre de archivo incorrecto
4. Google Indexing API no configurada

#### Solución

**Paso 1: Verificar si el archivo existe**
```bash
ls -la ../ssen-private/credentials.json
```

**Paso 2: Si no existe, descargar desde Google Cloud Console**

1. Acceder a: https://console.cloud.google.com
2. Seleccionar proyecto
3. APIs & Services → Credentials
4. Localizar cuenta de servicio
5. Pestaña KEYS → ADD KEY → Create new key (JSON)
6. Descargar archivo

**Paso 3: Copiar a ubicación correcta**
```bash
# Crear el directorio privado fuera del documento raíz (constante PRIVATE_DIR)
mkdir -p ../ssen-private

# Renombrar y mover
mv ~/Downloads/proyecto-123456-abc789.json ../ssen-private/credentials.json

# Establecer permisos
chmod 600 ../ssen-private/credentials.json
```

**Paso 4: Validar formato JSON**
```bash
php -r "
\$json = file_get_contents('../ssen-private/credentials.json');
json_decode(\$json);
echo (json_last_error() === JSON_ERROR_NONE) ? '✓ JSON válido' : '✗ JSON inválido';
echo PHP_EOL;
"
```

**⚠️ NOTA:**
Google Indexing API es **opcional**. El sistema funciona con solo IndexNow.

### 6.3 Error: "Failed to get Google Indexing token"

#### Descripción del Error

```
No se encontró el archivo de cuenta de servicio en: ../ssen-private/credentials.json
```

#### Causas

1. Archivo `credentials.json` corrupto o inválido
2. Cuenta de servicio no autorizada en Search Console
3. Indexing API no habilitada en el proyecto
4. Clave privada mal formada
5. Permisos insuficientes

#### Diagnóstico

**Paso 1: Verificar validez del JSON**
```bash
php -r "
\$json = json_decode(file_get_contents('../ssen-private/credentials.json'), true);
if (json_last_error() !== JSON_ERROR_NONE) {
    echo '✗ JSON inválido: ' . json_last_error_msg() . PHP_EOL;
    exit(1);
}
echo '✓ JSON válido' . PHP_EOL;
echo 'Project ID: ' . \$json['project_id'] . PHP_EOL;
echo 'Client Email: ' . \$json['client_email'] . PHP_EOL;
"
```

**Paso 2: Probar generación de token**
```bash
php -r "
require 'core_index/config/config.php';
require 'core_index/google_indexing_auth.php';
\$resultado = getGoogleIndexingToken(GOOGLE_CREDENTIALS_PATH);
if (\$resultado['success']) {
    echo '✓ Token obtenido: ' . substr(\$resultado['token'], 0, 50) . '...' . PHP_EOL;
} else {
    echo '✗ Error: ' . \$resultado['error'] . PHP_EOL;
}
"
```

#### Solución

**Solución 1: Re-descargar credentials.json**

1. Google Cloud Console → Tu proyecto
2. APIs & Services → Credentials
3. Eliminar clave JSON existente
4. Crear nueva clave JSON
5. Descargar y reemplazar

**Solución 2: Verificar Indexing API habilitada**

```bash
# Acceder a:
https://console.cloud.google.com/apis/library/indexing.googleapis.com
```

1. Seleccionar tu proyecto
2. Verificar que dice "API habilitada"
3. Si no, hacer clic en "ENABLE"

**Solución 3: Verificar permisos en Search Console**

1. Extraer email de cuenta de servicio:
```bash
php -r "echo json_decode(file_get_contents('../ssen-private/credentials.json'))->client_email . PHP_EOL;"
```

2. Acceder a: https://search.google.com/search-console
3. Seleccionar propiedad
4. Configuración → Usuarios y permisos
5. Verificar que el email está listado como **Propietario**
6. Si no está, agregar con rol **Owner**

**Solución 4: Esperar propagación**

Los permisos pueden tardar 1-2 minutos en propagarse. Esperar y reintentar.

### 6.4 Error: "Directory not writable"

#### Descripción del Error

```
Warning: file_put_contents(/ruta/al/proyecto/sitemap.xml): Failed to open stream: Permission denied
```

#### Causas

1. Directorios sin permisos de escritura
2. Propietario incorrecto (no es el usuario que ejecuta PHP)
3. SELinux bloqueando escritura (CentOS/RHEL)

#### Solución

**Paso 1: Verificar permisos actuales**
```bash
ls -ld core_index/data ../ssen-private ../ssen-private/logs
```

**Salida esperada:**
```
drwxr-xr-x 2 www-data    www-data    4096 ene 10 14:30 core_index/data
drwx------ 2 USUARIO_PHP USUARIO_PHP 4096 ene 10 14:30 ../ssen-private
drwx------ 2 USUARIO_PHP USUARIO_PHP 4096 ene 10 14:30 ../ssen-private/logs
```

**Paso 2: Cambiar propietario**
```bash
# core_index/data: usuario del servidor web
# Apache: www-data (Ubuntu/Debian), apache (CentOS/RHEL)
sudo chown -R www-data:www-data core_index/data

# ../ssen-private: usuario que ejecuta PHP
# en cPanel/LiteSpeed suele ser el usuario del sitio o www-data; en Hestia, el propio usuario del sitio
sudo chown -R USUARIO_PHP:USUARIO_PHP ../ssen-private
```

**Paso 3: Establecer permisos**
```bash
chmod 755 core_index/data
chmod 700 ../ssen-private ../ssen-private/logs
```

**Paso 4: Verificar escritura**
```bash
# Probar escritura en data/
touch core_index/data/test.txt && rm core_index/data/test.txt && echo "✓ Data escribible"

# Probar escritura en el registro (fuera del documento raíz)
touch ../ssen-private/logs/test.txt && rm ../ssen-private/logs/test.txt && echo "✓ Logs escribible"
```

**Solución especial para SELinux (CentOS/RHEL):**
```bash
# Verificar estado de SELinux
sestatus

# Establecer contexto correcto
sudo chcon -R -t httpd_sys_rw_content_t core_index/data
sudo chcon -R -t httpd_sys_rw_content_t ../ssen-private/logs

# O deshabilitar temporalmente para pruebas
sudo setenforce 0
```

### 6.5 Error HTTP 403 en IndexNow

#### Descripción del Error

```
[2026-01-10 14:30:15] Operation: UPDATED | Service: INDEXNOW | URL: https://ejemplo.com/pagina | HTTP: 403 | Status: FAILED | Message: Error: clave no válida o no encontrada
```

#### Causas

1. Clave no publicada correctamente en raíz del sitio
2. Archivo de clave inaccesible (HTTP 404)
3. Contenido del archivo no coincide con la clave enviada
4. Dominio en `BASE_URL` no coincide con el dominio de las URLs

#### Diagnóstico

**Paso 1: Verificar clave local**
```bash
cat core_index/config/indexnow_key.txt
```

**Paso 2: Verificar clave publicada**
```bash
KEY=$(cat core_index/config/indexnow_key.txt)
curl https://tu-dominio.com/${KEY}.txt
```

**Deben ser idénticas**

**Paso 3: Verificar código HTTP**
```bash
KEY=$(cat core_index/config/indexnow_key.txt)
curl -I https://tu-dominio.com/${KEY}.txt
```

**Debe responder:**
```
HTTP/1.1 200 OK
Content-Type: text/plain
```

#### Solución

**Solución 1: Re-publicar clave**
```bash
KEY=$(cat core_index/config/indexnow_key.txt)

# Copiar a raíz del sitio
sudo cp core_index/config/indexnow_key.txt /var/www/html/${KEY}.txt

# Establecer permisos
sudo chmod 644 /var/www/html/${KEY}.txt
```

**Solución 2: Verificar BASE_URL**
```bash
php -r "require 'core_index/config/config.php'; echo BASE_URL . PHP_EOL;"
```

Debe coincidir exactamente con el dominio de las URLs:
- ✅ Correcto: `https://ejemplo.com` con URLs `https://ejemplo.com/...`
- ✗ Incorrecto: `https://ejemplo.com` con URLs `https://www.ejemplo.com/...`

**Solución 3: Limpiar cache del servidor**

Si usas Cloudflare o caché del servidor:
```bash
# Purgar cache de archivo específico
# Depende del sistema de caché usado
```

### 6.6 Error HTTP 403 en Google Indexing API

#### Descripción del Error

```
[2026-01-10 14:30:18] Operation: UPDATED | Service: GOOGLE_INDEXING | URL: https://ejemplo.com/pagina | HTTP: 403 | Status: FAILED | Message: User does not have sufficient permission
```

#### Causas

1. Cuenta de servicio no agregada en Search Console
2. Cuenta de servicio sin rol de **Propietario**
3. Propiedad no verificada en Search Console
4. Dominio de URLs no coincide con propiedad de Search Console

#### Solución

**Paso 1: Extraer email de cuenta de servicio**
```bash
php -r "
\$creds = json_decode(file_get_contents('../ssen-private/credentials.json'));
echo 'Email: ' . \$creds->client_email . PHP_EOL;
"
```

**Ejemplo de salida:**
```
Email: indexing-service-account@proyecto-123456.iam.gserviceaccount.com
```

**Paso 2: Verificar en Search Console**

1. Acceder a: https://search.google.com/search-console
2. Seleccionar la propiedad correcta
3. Configuración → Usuarios y permisos
4. Buscar el email de la cuenta de servicio

**Si no está listado:**
- Hacer clic en **"AGREGAR USUARIO"**
- Pegar el email
- Seleccionar permiso: **Propietario** (no Editor ni Visualizador)
- Hacer clic en **"AGREGAR"**

**Si está listado pero con rol incorrecto:**
- Cambiar a **Propietario**

**Paso 3: Esperar propagación**

Esperar 1-2 minutos y reintentar la notificación.

**Paso 4: Verificar dominio de las URLs**

Las URLs deben pertenecer a la propiedad verificada:
- Si la propiedad es `https://ejemplo.com`, las URLs deben empezar con `https://ejemplo.com/`
- Si la propiedad es de dominio (`sc-domain:ejemplo.com`), acepta http y https

### 6.7 Sitemap Generado Está Vacío

#### Descripción del Error

```
Sitemap generado pero contiene 0 URLs
```

O el archivo `sitemap.xml` solo contiene:
```xml
<?xml version="1.0" encoding="UTF-8"?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
</urlset>
```

#### Causas

1. Archivo de entrada (`urls.json` o `urls.csv`) vacío
2. Archivo de entrada con formato incorrecto
3. JSON con errores de sintaxis
4. CSV sin encabezados o con columnas incorrectas
5. Parámetro `source` incorrecto en la URL

#### Diagnóstico

**Paso 1: Verificar existencia del archivo**
```bash
ls -la core_index/data/urls.json
ls -la core_index/data/urls.csv
```

**Paso 2: Verificar contenido**
```bash
# Ver primeras 10 líneas
head -10 core_index/data/urls.json
head -10 core_index/data/urls.csv
```

**Paso 3: Contar URLs**

**Para JSON:**
```bash
php -r "
\$data = json_decode(file_get_contents('core_index/data/urls.json'), true);
echo 'Total URLs: ' . count(\$data['urls']) . PHP_EOL;
"
```

**Para CSV:**
```bash
# Total (sin encabezado)
tail -n +2 core_index/data/urls.csv | wc -l
```

**Paso 4: Validar formato**

**Para JSON:**
```bash
php -r "
\$json = file_get_contents('core_index/data/urls.json');
json_decode(\$json);
if (json_last_error() === JSON_ERROR_NONE) {
    echo '✓ JSON válido' . PHP_EOL;
} else {
    echo '✗ JSON inválido: ' . json_last_error_msg() . PHP_EOL;
}
"
```

**Para CSV:**
```bash
php -r "
\$file = 'core_index/data/urls.csv';
\$handle = fopen(\$file, 'r');
\$header = fgetcsv(\$handle);
if (in_array('loc', \$header)) {
    echo '✓ Columna loc presente en la cabecera' . PHP_EOL;
} else {
    echo '✗ Falta la columna loc en la cabecera' . PHP_EOL;
    print_r(\$header);
}
fclose(\$handle);
"
```

#### Solución

**Solución 1: Crear archivo de ejemplo**

**Para JSON:**
```bash
cat > core_index/data/urls.json << 'EOF'
{
  "urls": [
    {
      "loc": "https://tu-dominio.com/",
      "lastmod": "2026-01-10",
      "changefreq": "daily",
      "priority": 1.0
    },
    {
      "loc": "https://tu-dominio.com/pagina2",
      "lastmod": "2026-01-09",
      "changefreq": "weekly",
      "priority": 0.8
    }
  ]
}
EOF
```

**Para CSV:**
```bash
cat > core_index/data/urls.csv << 'EOF'
loc,lastmod,changefreq,priority
https://tu-dominio.com/,2026-01-10,daily,1.0
https://tu-dominio.com/pagina2,2026-01-09,weekly,0.8
EOF
```

**Solución 2: Regenerar desde contenido existente**

Ver scripts en Capítulo 3.3 (Generación Automática de URLs)

**Solución 3: Verificar parámetro source**

Asegurarse de usar el parámetro correcto en la URL:
- Para JSON: `?source=json`
- Para CSV: `?source=csv`

---

## Resumen de la Subfase 3.3

### ✅ Tareas Completadas

1. **Capítulo 5: Ejecución del Sistema**
   - 5.1 Ejecución manual desde navegador (2 opciones)
   - 5.2 Ejecución automatizada con cron (ejemplos de frecuencias)
   - 5.3 Ejecución desde línea de comandos (múltiples métodos)
   - 5.4 Interpretación de resultados (4 estados, códigos HTTP, logs)

2. **Capítulo 6: Solución de Problemas**
   - 6.1 Error: IndexNow key not found
   - 6.2 Error: Google credentials file not found
   - 6.3 Error: Failed to get Google Indexing token
   - 6.4 Error: Directory not writable
   - 6.5 Error HTTP 403 en IndexNow
   - 6.6 Error HTTP 403 en Google Indexing API
   - 6.7 Sitemap generado está vacío

### 📋 Herramientas Proporcionadas

- Scripts wrapper para ejecución automatizada
- Expresiones cron para diferentes frecuencias
- Comandos de diagnóstico
- Tablas de códigos de estado HTTP
- Comandos de verificación y solución

### 🔗 Próximos Pasos

**Subfase 3.4: Mantenimiento y Referencias**
- Rotación de logs
- Backup de credenciales
- Monitoreo de notificaciones
- Referencia rápida de comandos

---

**Fin de Subfase 3.3 - Operación y Diagnóstico**
