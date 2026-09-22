# Manual de Configuración SSEN - Parte 2
## Configuración de Datos y APIs

**Subfase:** 3.2  
**Capítulos:** 3-4 (Archivos de Entrada + Configuración de APIs)  
**Fecha:** 10 de enero de 2026  
**Proyecto:** Simple Search Engine Notification (SSEN)

---

## Capítulo 3: Configuración de Archivos de Entrada

El sistema acepta URLs desde dos formatos de archivo: **JSON** y **CSV**. Ambos formatos deben ubicarse en el directorio `core_index/data/`.

### 3.1 Formato JSON

#### Estructura del Archivo

**Ubicación:** `core_index/data/urls.json`

**Estructura básica:**
```json
{
  "urls": [
    {
      "loc": "https://ejemplo.com/pagina",
      "lastmod": "2026-01-10",
      "changefreq": "weekly",
      "priority": 0.8
    }
  ]
}
```

#### Descripción de Campos

| Campo | Tipo | Obligatorio | Descripción | Valores Permitidos |
|-------|------|-------------|-------------|-------------------|
| **loc** | string | ✅ Sí | URL completa de la página | URL válida con protocolo (http/https) |
| **lastmod** | string | ❌ No | Fecha de última modificación | Formato: YYYY-MM-DD |
| **changefreq** | string | ❌ No | Frecuencia de cambio | always, hourly, daily, weekly, monthly, yearly, never (por defecto: weekly) |
| **priority** | float | ❌ No | Prioridad de rastreo | 0.0 a 1.0 (por defecto: 0.8) |
| **notify_indexnow** | booleano | ❌ No | Indicador de notificación a IndexNow | 1 o true (por defecto: sí) |
| **notify_google_indexing** | booleano | ❌ No | Indicador de notificación a Google Indexing API | 1 o true (por defecto: sí) |

#### Ejemplo Completo

**Crear archivo urls.json:**
```bash
nano core_index/data/urls.json
```

**Contenido de ejemplo:**
```json
{
  "urls": [
    {
      "loc": "https://ejemplo.com/",
      "lastmod": "2026-01-10",
      "changefreq": "daily",
      "priority": 1.0
    },
    {
      "loc": "https://ejemplo.com/productos",
      "lastmod": "2026-01-09",
      "changefreq": "weekly",
      "priority": 0.9
    },
    {
      "loc": "https://ejemplo.com/servicios",
      "lastmod": "2026-01-09",
      "changefreq": "weekly",
      "priority": 0.9
    },
    {
      "loc": "https://ejemplo.com/blog",
      "lastmod": "2026-01-10",
      "changefreq": "daily",
      "priority": 0.8
    },
    {
      "loc": "https://ejemplo.com/blog/articulo-1",
      "lastmod": "2026-01-08",
      "changefreq": "monthly",
      "priority": 0.7
    },
    {
      "loc": "https://ejemplo.com/blog/articulo-2",
      "lastmod": "2026-01-07",
      "changefreq": "monthly",
      "priority": 0.7
    },
    {
      "loc": "https://ejemplo.com/sobre-nosotros",
      "lastmod": "2026-01-01",
      "changefreq": "monthly",
      "priority": 0.6
    },
    {
      "loc": "https://ejemplo.com/contacto",
      "lastmod": "2025-12-15",
      "changefreq": "yearly",
      "priority": 0.5
    }
  ]
}
```

#### Reglas de Prioridad (Best Practices)

| Tipo de Página | Priority Recomendada | Changefreq Sugerida |
|----------------|---------------------|-------------------|
| **Página principal** | 1.0 | daily |
| **Categorías principales** | 0.9 | weekly |
| **Páginas de productos/servicios** | 0.8 | weekly |
| **Artículos de blog** | 0.6-0.7 | monthly |
| **Páginas estáticas** | 0.5-0.6 | yearly |
| **Páginas de contacto** | 0.5 | yearly |
| **Páginas de archivo** | 0.3-0.4 | never |

#### Validación del Archivo JSON

**Verificar sintaxis JSON:**
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

**Contar URLs en el archivo:**
```bash
php -r "
\$data = json_decode(file_get_contents('core_index/data/urls.json'), true);
echo 'Total de URLs: ' . count(\$data['urls']) . PHP_EOL;
"
```

**Validar estructura completa:**
```bash
php -r "
\$data = json_decode(file_get_contents('core_index/data/urls.json'), true);
\$errors = 0;
foreach (\$data['urls'] as \$index => \$item) {
    if (empty(\$item['loc'])) {
        echo 'Error en índice ' . \$index . ': campo loc vacío' . PHP_EOL;
        \$errors++;
    }
    if (isset(\$item['priority']) && (\$item['priority'] < 0 || \$item['priority'] > 1)) {
        echo 'Warning en índice ' . \$index . ': priority fuera de rango (0-1)' . PHP_EOL;
    }
}
echo (\$errors === 0) ? '✓ Todas las URLs son válidas' : '✗ ' . \$errors . ' errores encontrados';
echo PHP_EOL;
"
```

### 3.2 Formato CSV

#### Estructura del Archivo

**Ubicación:** `core_index/data/urls.csv`

**Estructura básica:**
```csv
loc,lastmod,changefreq,priority
https://ejemplo.com/,2026-01-10,daily,1.0
https://ejemplo.com/productos,2026-01-09,weekly,0.9
```

#### Reglas del Formato CSV

1. **Primera fila:** Debe contener la cabecera con la columna `loc` (obligatoria)
2. **Separador:** Coma (`,`)
3. **Orden de columnas:** Debe coincidir con los encabezados de la cabecera
4. **`loc`:** Campo obligatorio, debe incluir protocolo
5. **Columnas admitidas:** `loc`, `lastmod`, `changefreq`, `priority`, `notify_indexnow`, `notify_google_indexing`; las columnas no reconocidas se ignoran
6. **Campos opcionales:** Pueden dejarse vacíos pero mantener las comas
7. **Sin comillas:** A menos que la URL contenga comas (caso raro)

#### Ejemplo Completo

**Crear archivo urls.csv:**
```bash
nano core_index/data/urls.csv
```

**Contenido de ejemplo:**
```csv
loc,lastmod,changefreq,priority
https://ejemplo.com/,2026-01-10,daily,1.0
https://ejemplo.com/productos,2026-01-09,weekly,0.9
https://ejemplo.com/servicios,2026-01-09,weekly,0.9
https://ejemplo.com/blog,2026-01-10,daily,0.8
https://ejemplo.com/blog/articulo-1,2026-01-08,monthly,0.7
https://ejemplo.com/blog/articulo-2,2026-01-07,monthly,0.7
https://ejemplo.com/sobre-nosotros,2026-01-01,monthly,0.6
https://ejemplo.com/contacto,2025-12-15,yearly,0.5
```

#### Ejemplo con Campos Opcionales Vacíos

```csv
loc,lastmod,changefreq,priority
https://ejemplo.com/,2026-01-10,daily,1.0
https://ejemplo.com/productos,2026-01-09,,
https://ejemplo.com/servicios,,,0.9
https://ejemplo.com/contacto,,,
```

**Notas:**
- Los campos vacíos toman los valores por defecto al generar el sitemap
- `priority` por defecto: 0.8
- `changefreq` por defecto: weekly
- `lastmod` por defecto: fecha actual del sistema
- `notify_indexnow` y `notify_google_indexing` por defecto: sí (se notifica a ambas APIs)

#### Validación del Archivo CSV

**Verificar estructura del CSV:**
```bash
php -r "
\$file = 'core_index/data/urls.csv';
if ((\$handle = fopen(\$file, 'r')) !== FALSE) {
    \$header = fgetcsv(\$handle);
    if (in_array('loc', \$header)) {
        echo '✓ Columna loc presente en la cabecera' . PHP_EOL;
    } else {
        echo '✗ Falta la columna loc en la cabecera' . PHP_EOL;
    }
    \$count = 0;
    while (fgetcsv(\$handle) !== FALSE) {
        \$count++;
    }
    echo 'Total de URLs: ' . \$count . PHP_EOL;
    fclose(\$handle);
}
"
```

**Contar líneas rápidamente:**
```bash
# Total de líneas (incluyendo encabezado)
wc -l core_index/data/urls.csv

# Total de URLs (sin encabezado)
tail -n +2 core_index/data/urls.csv | wc -l
```

### 3.3 Generación Automática de URLs

#### Opción A: Desde Base de Datos MySQL

**Caso de uso:** Sitios con contenido en base de datos (WordPress, Drupal, CMS custom)

**Script de generación:**
```bash
nano generate_urls_from_db.php
```

**Contenido del script:**
```php
<?php
/**
 * Generador de URLs desde Base de Datos MySQL
 * Genera archivo urls.json para SSEN
 */

// Configuración de conexión
$db_host = 'localhost';
$db_user = 'usuario';
$db_pass = 'password';
$db_name = 'nombre_base_datos';

// Conectar a base de datos
$mysqli = new mysqli($db_host, $db_user, $db_pass, $db_name);

if ($mysqli->connect_error) {
    die('Error de conexión: ' . $mysqli->connect_error);
}

echo "Conectado a base de datos: $db_name\n";

// Consulta SQL (ajustar según tu esquema)
$query = "
    SELECT 
        CONCAT('https://ejemplo.com/', slug) AS loc,
        priority,
        changefreq,
        DATE_FORMAT(updated_at, '%Y-%m-%d') AS lastmod
    FROM paginas
    WHERE status = 'published'
    ORDER BY priority DESC, updated_at DESC
";

$result = $mysqli->query($query);

if (!$result) {
    die('Error en consulta: ' . $mysqli->error);
}

// Construir array de URLs
$urls = [];
while ($row = $result->fetch_assoc()) {
    $urls[] = [
        'loc' => $row['loc'],
        'priority' => (float) $row['priority'],
        'changefreq' => $row['changefreq'],
        'lastmod' => $row['lastmod']
    ];
}

// Guardar en archivo JSON
$output_file = 'core_index/data/urls.json';
$json = json_encode(['urls' => $urls], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
file_put_contents($output_file, $json);

echo "✓ Generado: $output_file\n";
echo "✓ Total de URLs exportadas: " . count($urls) . "\n";

$mysqli->close();
?>
```

**Ejecutar script:**
```bash
php generate_urls_from_db.php
```

**Ejemplo para WordPress:**
```php
<?php
// Generador específico para WordPress
require_once 'wp-load.php';

$posts = get_posts([
    'post_type' => 'post',
    'post_status' => 'publish',
    'numberposts' => -1
]);

$urls = [];
foreach ($posts as $post) {
    $urls[] = [
        'loc' => get_permalink($post->ID),
        'priority' => 0.7,
        'changefreq' => 'monthly',
        'lastmod' => get_the_modified_date('Y-m-d', $post->ID)
    ];
}

file_put_contents(
    'core_index/data/urls.json',
    json_encode(['urls' => $urls], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
);

echo count($urls) . " URLs exportadas\n";
?>
```

#### Opción B: Desde Sistema de Archivos

**Caso de uso:** Sitios estáticos o con archivos PHP individuales

**Script de generación:**
```bash
nano generate_urls_from_files.php
```

**Contenido del script:**
```php
<?php
/**
 * Generador de URLs desde Sistema de Archivos
 * Escanea directorios y genera urls.json
 */

$base_url = 'https://ejemplo.com';
$directories = ['post/', 'seccion/', 'articulos/'];
$urls = [];

// Escanear cada directorio
foreach ($directories as $dir) {
    if (!is_dir($dir)) {
        echo "⚠ Directorio no existe: $dir\n";
        continue;
    }
    
    $files = glob($dir . '*.php');
    echo "Procesando $dir: " . count($files) . " archivos\n";
    
    foreach ($files as $file) {
        // Generar slug desde nombre de archivo
        $slug = basename($file, '.php');
        
        // Determinar priority según directorio
        $priority = 0.7;
        if ($dir === 'post/') $priority = 0.8;
        if ($dir === 'seccion/') $priority = 0.9;
        
        // Obtener fecha de modificación
        $lastmod = date('Y-m-d', filemtime($file));
        
        $urls[] = [
            'loc' => $base_url . '/' . $dir . $slug,
            'priority' => $priority,
            'changefreq' => 'monthly',
            'lastmod' => $lastmod
        ];
    }
}

// Agregar páginas estáticas manualmente
$static_pages = [
    ['loc' => $base_url . '/', 'priority' => 1.0, 'changefreq' => 'daily'],
    ['loc' => $base_url . '/index.php', 'priority' => 1.0, 'changefreq' => 'daily'],
    ['loc' => $base_url . '/contacto', 'priority' => 0.5, 'changefreq' => 'yearly']
];

$urls = array_merge($static_pages, $urls);

// Ordenar por priority
usort($urls, function($a, $b) {
    return $b['priority'] <=> $a['priority'];
});

// Guardar archivo
$output_file = 'core_index/data/urls.json';
file_put_contents(
    $output_file,
    json_encode(['urls' => $urls], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
);

echo "✓ Archivo generado: $output_file\n";
echo "✓ Total de URLs: " . count($urls) . "\n";
?>
```

**Ejecutar script:**
```bash
php generate_urls_from_files.php
```

#### Opción C: Desde Sitemap Existente

**Caso de uso:** Ya tienes un sitemap.xml y quieres convertirlo a JSON

**Script de conversión:**
```bash
nano convert_sitemap_to_json.php
```

**Contenido del script:**
```php
<?php
/**
 * Convertir sitemap.xml existente a urls.json
 */

$sitemap_file = 'sitemap.xml'; // Archivo de entrada
$output_file = 'core_index/data/urls.json';

if (!file_exists($sitemap_file)) {
    die("Error: $sitemap_file no encontrado\n");
}

// Parsear XML
$xml = simplexml_load_file($sitemap_file);
$urls = [];

foreach ($xml->url as $url) {
    $item = [
        'loc' => (string) $url->loc
    ];
    
    if (isset($url->priority)) {
        $item['priority'] = (float) $url->priority;
    }
    
    if (isset($url->changefreq)) {
        $item['changefreq'] = (string) $url->changefreq;
    }
    
    if (isset($url->lastmod)) {
        // Convertir fecha a formato YYYY-MM-DD
        $date = new DateTime((string) $url->lastmod);
        $item['lastmod'] = $date->format('Y-m-d');
    }
    
    $urls[] = $item;
}

// Guardar JSON
file_put_contents(
    $output_file,
    json_encode(['urls' => $urls], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
);

echo "✓ Convertido: $sitemap_file → $output_file\n";
echo "✓ Total de URLs: " . count($urls) . "\n";
?>
```

**Ejecutar conversión:**
```bash
php convert_sitemap_to_json.php
```

---

## Capítulo 4: Configuración de APIs

### 4.1 IndexNow API (Bing, Yandex, otros)

IndexNow es un protocolo abierto que permite notificar cambios de contenido a múltiples motores de búsqueda simultáneamente.

#### Paso 1: Generar Clave IndexNow

**Requisitos de la clave:**
- Entre 8 y 128 caracteres
- Solo caracteres alfanuméricos (0-9, a-z, A-Z) o guion
- Generada aleatoriamente (no usar claves predecibles)

**Método 1: Con la herramienta del sistema (Recomendado)**
```bash
# Generar la clave y el archivo público de verificación en la raíz del sitio
php core_index/tools/generate_indexnow_key.php

# Rotar una clave existente
php core_index/tools/generate_indexnow_key.php --force
```

**Método 2: Con OpenSSL**
```bash
# Generar clave de 32 caracteres hexadecimales
openssl rand -hex 16 > core_index/config/indexnow_key.txt

# Ver la clave generada
cat core_index/config/indexnow_key.txt
```

**Salida ejemplo:**
```
{clave}
```

**Método 3: Con /dev/urandom**
```bash
# Usando /dev/urandom (Linux/macOS)
head -c 16 /dev/urandom | xxd -p > core_index/config/indexnow_key.txt
```

**Método 4: Con PHP**
```bash
php -r "echo bin2hex(random_bytes(16)) . PHP_EOL;" > core_index/config/indexnow_key.txt
```

**Verificar formato de la clave:**
```bash
KEY=$(cat core_index/config/indexnow_key.txt)
if [[ $KEY =~ ^[A-Za-z0-9-]{8,128}$ ]]; then
    echo "✓ Clave válida: $KEY"
else
    echo "✗ Clave inválida (debe tener entre 8 y 128 caracteres alfanuméricos o guion)"
fi
```

#### Paso 2: Publicar Clave en Raíz del Sitio

IndexNow requiere que la clave sea accesible públicamente para verificar la autenticidad.

**Ubicación requerida:**
```
https://tu-dominio.com/{clave}.txt
```

**Ejemplo:**
```
https://ejemplo.com/{clave}.txt
```

**Método 1: Copiar archivo**
```bash
# Obtener la clave
KEY=$(cat core_index/config/indexnow_key.txt)

# Copiar a raíz del sitio web
cp core_index/config/indexnow_key.txt /var/www/html/${KEY}.txt

# Verificar
ls -l /var/www/html/${KEY}.txt
```

**Método 2: Crear enlace simbólico**
```bash
KEY=$(cat core_index/config/indexnow_key.txt)
ln -s $(pwd)/core_index/config/indexnow_key.txt /var/www/html/${KEY}.txt
```

**Método 3: Configurar en servidor web**

**Para Apache (.htaccess):**
```apache
# Redirigir {clave}.txt al archivo central
RewriteEngine On
RewriteRule ^[A-Za-z0-9-]{8,128}\.txt$ /core_index/config/indexnow_key.txt [L]
```

**Para Nginx (nginx.conf):**
```nginx
location ~ ^/[A-Za-z0-9-]{8,128}\.txt$ {
    alias /ruta/al/proyecto/core_index/config/indexnow_key.txt;
}
```

**Verificar accesibilidad pública:**
```bash
KEY=$(cat core_index/config/indexnow_key.txt)
BASE_URL="https://tu-dominio.com"

# Probar con curl
curl -I ${BASE_URL}/${KEY}.txt

# Verificar contenido
curl ${BASE_URL}/${KEY}.txt
```

**Respuesta esperada:**
```
HTTP/1.1 200 OK
Content-Type: text/plain

{clave}
```

**⚠️ IMPORTANTE:**
- El archivo debe responder con código HTTP 200
- El contenido debe ser exactamente la clave (sin espacios ni saltos de línea extra)
- Debe ser accesible públicamente sin autenticación

#### Paso 3: Probar Autenticación

**Crear script de prueba:**
```bash
nano test_indexnow.php
```

**Contenido:**
```php
<?php
require_once 'core_index/indexnow_auth.php';

echo "Probando autenticación IndexNow...\n";
echo str_repeat("=", 50) . "\n";

try {
    // Sin argumentos usa INDEXNOW_KEY_FILE de la configuración
    $credentials = getIndexNowCredentials();
    
    echo "✓ Clave cargada: " . $credentials['key'] . "\n";
    echo "✓ Ubicación de clave: " . $credentials['keyLocation'] . "\n";
    echo "✓ Host: " . $credentials['host'] . "\n";
    
    // Verificar accesibilidad de la clave
    $ch = curl_init($credentials['keyLocation']);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($http_code === 200) {
        echo "✓ Clave accesible públicamente (HTTP 200)\n";
        
        if (trim($response) === $credentials['key']) {
            echo "✓ Contenido de clave coincide\n";
            echo "\n✓ CONFIGURACIÓN INDEXNOW CORRECTA\n";
        } else {
            echo "✗ Contenido de clave no coincide\n";
        }
    } else {
        echo "✗ Clave NO accesible (HTTP $http_code)\n";
        echo "  Verifique que el archivo esté en la raíz del sitio\n";
    }
    
} catch (Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
}

echo str_repeat("=", 50) . "\n";
?>
```

**Ejecutar prueba:**
```bash
php test_indexnow.php
```

**Salida esperada:**
```
Probando autenticación IndexNow...
==================================================
✓ Clave cargada: {clave}
✓ Ubicación de clave: https://ejemplo.com/{clave}.txt
✓ Host: ejemplo.com
✓ Clave accesible públicamente (HTTP 200)
✓ Contenido de clave coincide

✓ CONFIGURACIÓN INDEXNOW CORRECTA
==================================================
```

### 4.2 Google Indexing API (Opcional)

Google Indexing API permite notificar a Google cambios en páginas que contienen datos estructurados `JobPosting` o `BroadcastEvent`. Notificar otros tipos de página queda fuera de sus condiciones de uso y no garantiza la indexación.

#### Paso 1: Crear Proyecto en Google Cloud Console

**Acceder a Google Cloud Console:**
```
https://console.cloud.google.com
```

**Crear nuevo proyecto:**
1. Hacer clic en el selector de proyectos (parte superior)
2. Hacer clic en **"Nuevo proyecto"**
3. **Nombre del proyecto:** `Indexing API - [Nombre de tu sitio]`
   - Ejemplo: `Indexing API - MiEmpresa`
4. **Organización:** Dejar por defecto (No organization)
5. Hacer clic en **"CREAR"**
6. Anotar el **Project ID** (se muestra debajo del nombre)

**Tiempo estimado:** 30-60 segundos para crear el proyecto

#### Paso 2: Habilitar Indexing API

**Dentro del proyecto creado:**

1. En el menú lateral: **APIs & Services → Library**
2. En el buscador: escribir `Indexing API`
3. Hacer clic en **"Indexing API"** (de Google)
4. Hacer clic en el botón **"ENABLE"** (Habilitar)
5. Esperar confirmación (5-10 segundos)

**Confirmación visual:**
- Aparecerá el dashboard de la API
- Estado: "API habilitada"

#### Paso 3: Crear Cuenta de Servicio

**Navegar a credenciales:**
1. **APIs & Services → Credentials**
2. Hacer clic en **"CREATE CREDENTIALS"**
3. Seleccionar **"Service Account"**

**Configurar cuenta de servicio:**

**Paso 3.1: Detalles de la cuenta**
- **Service account name:** `indexing-service-account`
- **Service account ID:** Se genera automáticamente
- **Description:** `Cuenta de servicio para IndexingAPI SSEN`
- Hacer clic en **"CREATE AND CONTINUE"**

**Paso 3.2: Permisos (Grant access)**
- **Role:** Seleccionar **"Owner"** (Propietario)
  - Alternativamente: Crear rol personalizado con permiso `indexing.urlNotifications.publish`
- Hacer clic en **"CONTINUE"**

**Paso 3.3: Grant users access (opcional)**
- Dejar vacío
- Hacer clic en **"DONE"**

#### Paso 4: Generar Clave JSON

**Desde la lista de cuentas de servicio:**

1. Localizar la cuenta recién creada: `indexing-service-account@...`
2. Hacer clic en el **email de la cuenta** (no en el nombre)
3. Ir a la pestaña **"KEYS"**
4. Hacer clic en **"ADD KEY" → "Create new key"**
5. Seleccionar tipo: **JSON**
6. Hacer clic en **"CREATE"**

**Resultado:**
- Se descarga automáticamente un archivo JSON
- Nombre ejemplo: `proyecto-123456-abc789.json`
- **⚠️ IMPORTANTE:** Este archivo contiene credenciales sensibles

**Contenido del archivo:**
```json
{
  "type": "service_account",
  "project_id": "proyecto-123456",
  "private_key_id": "abc123...",
  "private_key": "-----BEGIN PRIVATE KEY-----\n...\n-----END PRIVATE KEY-----\n",
  "client_email": "indexing-service-account@proyecto-123456.iam.gserviceaccount.com",
  "client_id": "123456789012345678901",
  "auth_uri": "https://accounts.google.com/o/oauth2/auth",
  "token_uri": "https://oauth2.googleapis.com/token",
  "auth_provider_x509_cert_url": "https://www.googleapis.com/oauth2/v1/certs",
  "client_x509_cert_url": "https://www.googleapis.com/robot/v1/metadata/x509/..."
}
```

#### Paso 5: Configurar Clave en el Sistema

**Renombrar y copiar archivo:**
```bash
# Crear el directorio privado fuera del documento raíz (constante PRIVATE_DIR)
mkdir -p ../ssen-private

# Mover y renombrar a credentials.json
mv ~/Downloads/proyecto-123456-abc789.json ../ssen-private/credentials.json

# Establecer permisos restrictivos (solo lectura para el propietario)
chmod 600 ../ssen-private/credentials.json

# Verificar ubicación
ls -la ../ssen-private/credentials.json
```

**Salida esperada:**
```
-rw------- 1 usuario grupo 2345 ene 10 14:30 ../ssen-private/credentials.json
```

**⚠️ SEGURIDAD:**
- **NO** subir `credentials.json` a repositorios públicos (Git)
- Verificar que `.gitignore` incluye este archivo
- Mantener permisos restrictivos (600)
- Backup en ubicación segura

**Verificar que no se versiona:**
El directorio privado queda fuera del repositorio y, además, `.gitignore` excluye los archivos `*credentials*.json`. La ruta efectiva la define la constante `GOOGLE_CREDENTIALS_PATH`.

#### Paso 6: Autorizar Cuenta en Google Search Console

La cuenta de servicio debe tener permisos en Search Console para notificar URLs.

**Paso 6.1: Extraer email de la cuenta de servicio**
```bash
php -r "
require 'core_index/config/config.php';
\$json = json_decode(file_get_contents(GOOGLE_CREDENTIALS_PATH), true);
echo 'Email de cuenta de servicio:' . PHP_EOL;
echo \$json['client_email'] . PHP_EOL;
"
```

**Salida ejemplo:**
```
Email de cuenta de servicio:
indexing-service-account@proyecto-123456.iam.gserviceaccount.com
```

**Paso 6.2: Agregar en Google Search Console**

1. Acceder a: https://search.google.com/search-console
2. Seleccionar tu propiedad (sitio web)
3. En el menú lateral: **Configuración → Usuarios y permisos**
4. Hacer clic en **"AGREGAR USUARIO"**
5. Pegar el email de la cuenta de servicio
6. Seleccionar permiso: **Propietario** (Owner)
7. Hacer clic en **"AGREGAR"**

**Confirmación:**
- El email aparecerá en la lista de usuarios
- Tipo: "Por invitación"
- Permiso: "Propietario"

**⚠️ IMPORTANTE:**
- Debe ser rol **Propietario** (no Editor ni Visualizador)
- Esperar 1-2 minutos para que los permisos se propaguen

#### Paso 7: Probar Autenticación

**Crear script de prueba:**
```bash
nano test_google_indexing.php
```

**Contenido:**
```php
<?php
require_once 'core_index/config/config.php';
require_once 'core_index/google_indexing_auth.php';

echo "Probando autenticación Google Indexing API...\n";
echo str_repeat("=", 50) . "\n";

try {
    $credentials_file = GOOGLE_CREDENTIALS_PATH;
    
    // Verificar que el archivo existe
    if (!file_exists($credentials_file)) {
        throw new Exception("Archivo credentials.json no encontrado");
    }
    
    echo "✓ Archivo credentials.json encontrado\n";
    
    // Cargar y verificar formato JSON
    $credentials = json_decode(file_get_contents($credentials_file), true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception("JSON inválido: " . json_last_error_msg());
    }
    
    echo "✓ JSON válido\n";
    echo "✓ Project ID: " . $credentials['project_id'] . "\n";
    echo "✓ Client Email: " . $credentials['client_email'] . "\n";
    
    // Intentar obtener token
    echo "\nSolicitando token de acceso...\n";
    $tokenResult = getGoogleIndexingToken($credentials_file);
    
    if ($tokenResult['success']) {
        echo "✓ Token obtenido exitosamente\n";
        echo "✓ Token (primeros 50 caracteres): " . substr($tokenResult['token'], 0, 50) . "...\n";
        echo "\n✓ CONFIGURACIÓN GOOGLE INDEXING API CORRECTA\n";
    } else {
        echo "✗ Error: " . $tokenResult['error'] . "\n";
    }
    
} catch (Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
    echo "\nVerifique:\n";
    echo "1. Que el archivo credentials.json sea válido\n";
    echo "2. Que la cuenta de servicio tenga permisos en Search Console\n";
    echo "3. Que Indexing API esté habilitada en el proyecto\n";
}

echo str_repeat("=", 50) . "\n";
?>
```

**Ejecutar prueba:**
```bash
php test_google_indexing.php
```

**Salida esperada (éxito):**
```
Probando autenticación Google Indexing API...
==================================================
✓ Archivo credentials.json encontrado
✓ JSON válido
✓ Project ID: proyecto-123456
✓ Client Email: indexing-service-account@proyecto-123456.iam.gserviceaccount.com

Solicitando token de acceso...
✓ Token obtenido exitosamente
✓ Token (primeros 50 caracteres): ya29.c.Kp8B9QdT...

✓ CONFIGURACIÓN GOOGLE INDEXING API CORRECTA
==================================================
```

---

## Resumen de la Subfase 3.2

### ✅ Tareas Completadas

1. **Capítulo 3: Configuración de Archivos de Entrada**
   - Formato JSON (estructura, validación, ejemplos)
   - Formato CSV (estructura, validación, ejemplos)
   - Generación automática desde BD, archivos y sitemap existente

2. **Capítulo 4: Configuración de APIs**
   - **IndexNow:** 3 pasos (generar clave, publicar, probar)
   - **Google Indexing API:** 7 pasos (proyecto, habilitar, cuenta, clave, configurar, autorizar, probar)

### 📋 Archivos Creados

- `core_index/data/urls.json` (o urls.csv)
- `core_index/config/indexnow_key.txt`
- `../ssen-private/credentials.json` (opcional, fuera del documento raíz)
- Scripts auxiliares de generación y prueba

### 🔗 Próximos Pasos

**Subfase 3.3: Operación y Diagnóstico**
- Métodos de ejecución (navegador, cron, CLI)
- Interpretación de resultados
- Solución de problemas comunes

---

**Fin de Subfase 3.2 - Configuración de Datos y APIs**
