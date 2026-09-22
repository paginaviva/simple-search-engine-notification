# Manual de Configuración SSEN - Parte 1
## Fundamentos y Preparación Inicial

**Subfase:** 3.1  
**Capítulos:** 1-2 (Prerrequisitos + Instalación)  
**Fecha:** 10 de enero de 2026  
**Proyecto:** Simple Search Engine Notification (SSEN)

---

## Capítulo 1: Prerrequisitos

### 1.1 Requisitos del Servidor

#### Versión de PHP
- **Mínimo requerido:** PHP 8.1
- **Recomendado:** PHP 8.2 o superior
- **Soporte:** PHP 8.1 alcanzó el fin de soporte el 31 de diciembre de 2025; se recomienda 8.2 o superior

**Verificar versión instalada:**
```bash
php -v
```

**Salida esperada:**
```
PHP 8.1.2 (cli) (built: Jan 12 2022 08:23:45)
Copyright (c) The PHP Group
Zend Engine v4.1.2, Copyright (c) Zend Technologies
```

#### Servidor Web
El sistema es compatible con los siguientes servidores web:

| Servidor | Versión Mínima | Notas |
|----------|----------------|-------|
| **Apache** | 2.4+ | Autenticación de la aplicación; denegación adicional con .htaccess |
| **Nginx** | 1.18+ | Compatible mediante la autenticación de la aplicación (ignora .htaccess) |
| **LiteSpeed** | 5.4+ | Autenticación de la aplicación; denegación adicional con .htaccess |

**Verificar servidor web activo:**
```bash
# Para Apache
apache2 -v
# o
httpd -v

# Para Nginx
nginx -v

# Para LiteSpeed
/usr/local/lsws/bin/lshttpd -v
```

#### Extensiones PHP Requeridas

El sistema requiere las siguientes extensiones PHP nativas:

**1. OpenSSL**
- **Propósito:** Autenticación OAuth 2.0 para Google Indexing API
- **Uso:** Firma de tokens JWT, generación de claves criptográficas

**2. cURL**
- **Propósito:** Comunicación HTTP con APIs externas
- **Uso:** Envío de notificaciones a IndexNow y Google

**3. JSON**
- **Propósito:** Procesamiento de archivos de entrada y respuestas de APIs
- **Uso:** Parseo de urls.json, responses de APIs

**4. SimpleXML**
- **Propósito:** Generación y parseo de archivos XML
- **Uso:** Creación de sitemap.xml, comparación de sitemaps

**Verificar extensiones instaladas:**
```bash
# Verificar todas las extensiones requeridas
php -m | grep -E 'openssl|curl|json|simplexml'
```

**Salida esperada:**
```
curl
json
openssl
SimpleXML
```

**Verificar extensión individual:**
```bash
php -r "echo extension_loaded('openssl') ? 'OpenSSL: OK' : 'OpenSSL: NO INSTALADO';"
php -r "echo extension_loaded('curl') ? 'cURL: OK' : 'cURL: NO INSTALADO';"
php -r "echo extension_loaded('json') ? 'JSON: OK' : 'JSON: NO INSTALADO';"
php -r "echo extension_loaded('simplexml') ? 'SimpleXML: OK' : 'SimpleXML: NO INSTALADO';"
```

**Instalar extensiones faltantes (según sistema operativo):**

**Ubuntu/Debian:**
```bash
sudo apt update
sudo apt install php-cli php-openssl php-curl php-json php-xml
```

**CentOS/RHEL:**
```bash
sudo yum install php-cli php-openssl php-curl php-json php-xml
```

**macOS (con Homebrew):**
```bash
brew install php
# Las extensiones vienen incluidas por defecto
```

#### Sistema Operativo
Compatible con:
- Linux (Ubuntu, Debian, CentOS, RHEL, Fedora)
- Windows (con PHP instalado)
- macOS

### 1.2 Permisos de Archivos

#### Permisos de Lectura
Todos los archivos PHP del sistema deben ser legibles por el usuario del servidor web:

```bash
# Establecer permisos de lectura para archivos PHP
find core_index -name "*.php" -exec chmod 644 {} \;
```

#### Permisos de Escritura
Los siguientes directorios requieren permisos de escritura:

**1. Directorio `core_index/data/`**
- **Propósito:** Almacenar los archivos de entrada (urls.json, urls.csv)
- **Archivos almacenados:** urls.json, urls.csv

**2. Directorio `../ssen-private/logs/` (constante `LOG_DIR`, dentro de `PRIVATE_DIR`)**
- **Propósito:** Registrar operaciones de notificación
- **Archivo escrito:** indexing_api_sitemap.log (`LOG_PATH`)
- **Nota:** El registro vive fuera del documento raíz; el directorio `core_index/logs/` ya no existe

**3. Directorio raíz del sitio (`ROOT_DIR`)**
- **Propósito:** Almacenar los sitemaps generados
- **Archivos escritos:** sitemap.xml, sitemap.old.xml

**Establecer permisos correctos:**
```bash
# Cambiar al directorio del proyecto
cd /ruta/al/proyecto

# Establecer propietario de los datos (reemplazar www-data con usuario de tu servidor)
sudo chown -R www-data:www-data core_index/data

# Establecer permisos de directorios
chmod 755 core_index/data

# Crear el directorio privado y su subdirectorio de registros (fuera del documento raíz)
mkdir -p ../ssen-private/logs

# Propietario: usuario que ejecuta PHP (en cPanel/LiteSpeed suele ser el usuario del sitio o www-data; en Hestia, el propio usuario del sitio)
sudo chown -R USUARIO_PHP:USUARIO_PHP ../ssen-private

# Permisos restrictivos del directorio privado y de su subdirectorio de registros
chmod 700 ../ssen-private ../ssen-private/logs

# Establecer permisos de archivos existentes
chmod 644 core_index/data/*.{json,csv,xml,txt} 2>/dev/null || true
```

**Verificar permisos:**
```bash
# Verificar que los directorios son escribibles
[ -w core_index/data ] && echo "core_index/data: ESCRIBIBLE" || echo "core_index/data: NO ESCRIBIBLE"
[ -w ../ssen-private/logs ] && echo "../ssen-private/logs: ESCRIBIBLE" || echo "../ssen-private/logs: NO ESCRIBIBLE"
```

**Salida esperada:**
```
core_index/data: ESCRIBIBLE
../ssen-private/logs: ESCRIBIBLE
```

#### Tabla de Permisos Recomendados

| Tipo | Ruta | Permiso | Propietario | Descripción |
|------|------|---------|-------------|-------------|
| Directorio | `core_index/` | 755 | www-data | Directorio raíz del sistema |
| Directorio | `core_index/config/` | 755 | www-data | Configuración |
| Directorio | `core_index/data/` | 755 | www-data | Datos (lectura/escritura) |
| Directorio | `../ssen-private/` | 700 | usuario que ejecuta PHP | Directorio privado (fuera del documento raíz) |
| Directorio | `../ssen-private/logs/` | 700 | usuario que ejecuta PHP | Registro (escritura) |
| Archivo PHP | `*.php` | 644 | www-data | Scripts ejecutables |
| Archivo JSON | `*.json` | 644 | www-data | Archivos de configuración |
| Archivo CSV | `*.csv` | 644 | www-data | Archivos de entrada |
| Archivo TXT | `*.txt` | 644 | www-data | Claves |

### 1.3 Cuentas y Credenciales Necesarias

#### IndexNow API (Obligatorio)

**¿Qué es IndexNow?**
Protocolo abierto que permite notificar a motores de búsqueda sobre cambios en contenido de manera instantánea.

**Motores compatibles:**
- Bing (Microsoft)
- Yandex
- Seznam.cz
- Naver (en implementación)

**Requisitos:**
- Clave API de entre 8 y 128 caracteres alfanuméricos o guion (generada por el usuario)
- Archivo de verificación publicado en raíz del sitio web
- Sin registro previo necesario
- Sin límite de uso documentado
- **Gratuito**

**Generación de clave:**
Se realizará en el Capítulo 4 (Configuración de APIs)

#### Google Indexing API (Opcional)

**¿Qué es Google Indexing API?**
API oficial de Google para notificar cambios en páginas que contienen datos estructurados `JobPosting` o `BroadcastEvent`. Notificar otros tipos de página queda fuera de sus condiciones de uso y no garantiza la indexación.

**Casos de uso admitidos:**
- Páginas de ofertas de empleo con datos estructurados `JobPosting`
- Páginas de retransmisiones en directo con `BroadcastEvent` incrustado en un `VideoObject`

**Requisitos:**
1. **Proyecto en Google Cloud Console**
   - Cuenta de Google (gmail.com o Google Workspace)
   - Proyecto en Google Cloud Platform (gratuito para crear)

2. **Cuenta de Servicio (Service Account)**
   - Tipo de autenticación: OAuth 2.0
   - Archivo credentials.json con clave privada

3. **Permisos en Google Search Console**
   - Sitio verificado en Search Console
   - Cuenta de servicio añadida como **Propietario** (sin este permiso, la API responde con error 403)

4. **Límites de uso:**
   - 200 notificaciones por día y proyecto (incluye `URL_UPDATED` y `URL_DELETED`)
   - La notificación de eliminación (`URL_DELETED`) exige que la URL devuelva 404 o 410

**Configuración:**
Se realizará en el Capítulo 4.2 (Configuración de Google Indexing API)

**¿Es obligatorio?**
No. El sistema funciona completamente con solo IndexNow. Google Indexing API es opcional y proporciona:
- Notificación directa a Google para las páginas admitidas (canal independiente de IndexNow; Google no participa en IndexNow)
- Notificaciones de actualización (`URL_UPDATED`) y de eliminación (`URL_DELETED`) para esas páginas

### 1.4 Verificación Inicial

#### Script de Verificación de Requisitos

**Crear archivo de verificación:**
```bash
nano verify_requirements.sh
```

**Contenido del script:**
```bash
#!/bin/bash

echo "==================================="
echo "Verificación de Requisitos - SSEN"
echo "==================================="
echo ""

# 1. Verificar PHP
echo "[1/5] Verificando PHP..."
PHP_VERSION=$(php -v | head -n 1 | awk '{print $2}')
if command -v php &> /dev/null; then
    echo "✓ PHP instalado: versión $PHP_VERSION"
    
    # Verificar versión mínima (8.1)
    PHP_VERSION_MAJOR=$(echo $PHP_VERSION | cut -d. -f1)
    PHP_VERSION_MINOR=$(echo $PHP_VERSION | cut -d. -f2)
    
    if [ "$PHP_VERSION_MAJOR" -ge 9 ] || ([ "$PHP_VERSION_MAJOR" -eq 8 ] && [ "$PHP_VERSION_MINOR" -ge 1 ]); then
        echo "✓ Versión cumple requisitos mínimos (8.1+)"
    else
        echo "✗ Versión no admitida. Actualice a PHP 8.1 o superior"
    fi
else
    echo "✗ PHP no está instalado"
fi
echo ""

# 2. Verificar extensiones PHP
echo "[2/5] Verificando extensiones PHP..."
EXTENSIONS=("openssl" "curl" "json" "simplexml")
for ext in "${EXTENSIONS[@]}"; do
    if php -m | grep -i "^$ext$" &> /dev/null; then
        echo "✓ $ext: instalado"
    else
        echo "✗ $ext: NO INSTALADO"
    fi
done
echo ""

# 3. Verificar estructura de directorios
echo "[3/5] Verificando estructura de directorios..."
DIRS=("core_index" "core_index/config" "core_index/data" "../ssen-private/logs")
for dir in "${DIRS[@]}"; do
    if [ -d "$dir" ]; then
        echo "✓ $dir: existe"
    else
        echo "✗ $dir: NO EXISTE"
    fi
done
echo ""

# 4. Verificar permisos de escritura
echo "[4/5] Verificando permisos de escritura..."
WRITE_DIRS=("core_index/data" "../ssen-private/logs")
for dir in "${WRITE_DIRS[@]}"; do
    if [ -w "$dir" ]; then
        echo "✓ $dir: escribible"
    else
        echo "✗ $dir: NO ESCRIBIBLE (revisar propietario y permisos)"
    fi
done
echo ""

# 5. Verificar archivos PHP principales
echo "[5/5] Verificando archivos PHP del sistema..."
PHP_FILES=(
    "core_index/logger.php"
    "core_index/sitemap_diff.php"
    "core_index/sitemap_generator.php"
    "core_index/sitemap_form_url.php"
    "core_index/auth_guard.php"
    "core_index/indexnow_auth.php"
    "core_index/indexnow_client.php"
    "core_index/google_indexing_auth.php"
    "core_index/google_indexing_client.php"
    "core_index/config/config.php"
)

ERRORS=0
for file in "${PHP_FILES[@]}"; do
    if [ -f "$file" ]; then
        # Verificar sintaxis
        php -l "$file" &> /dev/null
        if [ $? -eq 0 ]; then
            echo "✓ $file: OK"
        else
            echo "✗ $file: ERROR DE SINTAXIS"
            ERRORS=$((ERRORS + 1))
        fi
    else
        echo "✗ $file: NO EXISTE"
        ERRORS=$((ERRORS + 1))
    fi
done
echo ""

# Resumen
echo "==================================="
if [ $ERRORS -eq 0 ]; then
    echo "✓ SISTEMA LISTO PARA CONFIGURACIÓN"
else
    echo "✗ $ERRORS ERRORES ENCONTRADOS"
fi
echo "==================================="
```

**Hacer ejecutable y correr:**
```bash
chmod +x verify_requirements.sh
./verify_requirements.sh
```

**Salida esperada (sistema correcto):**
```
===================================
Verificación de Requisitos - SSEN
===================================

[1/5] Verificando PHP...
✓ PHP instalado: versión 8.1.2
✓ Versión cumple requisitos mínimos (7.4+)

[2/5] Verificando extensiones PHP...
✓ openssl: instalado
✓ curl: instalado
✓ json: instalado
✓ simplexml: instalado

[3/5] Verificando estructura de directorios...
✓ core_index: existe
✓ core_index/config: existe
✓ core_index/data: existe
✓ ../ssen-private/logs: existe

[4/5] Verificando permisos de escritura...
✓ core_index/data: escribible
✓ ../ssen-private/logs: escribible

[5/5] Verificando archivos PHP del sistema...
✓ core_index/logger.php: OK
✓ core_index/sitemap_diff.php: OK
✓ core_index/sitemap_generator.php: OK
✓ core_index/sitemap_form_url.php: OK
✓ core_index/auth_guard.php: OK
✓ core_index/indexnow_auth.php: OK
✓ core_index/indexnow_client.php: OK
✓ core_index/google_indexing_auth.php: OK
✓ core_index/google_indexing_client.php: OK
✓ core_index/config/config.php: OK

===================================
✓ SISTEMA LISTO PARA CONFIGURACIÓN
===================================
```

---

## Capítulo 2: Instalación

### 2.1 Clonar o Descargar el Repositorio

#### Opción A: Clonar con Git (Recomendado)

**Requisitos:**
- Git instalado en el sistema

**Verificar instalación de Git:**
```bash
git --version
```

**Clonar el repositorio:**
```bash
# Navegar al directorio donde deseas instalar el sistema
cd /var/www/html

# Clonar repositorio
git clone https://github.com/paginaviva/simple-search-engine-notification.git

# Entrar al directorio
cd simple-search-engine-notification

# Verificar contenido
ls -la
```

**Ventajas:**
- Actualizaciones fáciles con `git pull`
- Historial de cambios disponible
- Control de versiones integrado

#### Opción B: Descargar ZIP

**Pasos:**

1. **Descargar desde GitHub:**
   ```
   https://github.com/paginaviva/simple-search-engine-notification/archive/refs/heads/main.zip
   ```

2. **Extraer archivo:**
   ```bash
   # Descargar con curl o wget
   wget https://github.com/paginaviva/simple-search-engine-notification/archive/refs/heads/main.zip
   
   # O con curl
   curl -L -o ssen-main.zip https://github.com/paginaviva/simple-search-engine-notification/archive/refs/heads/main.zip
   
   # Extraer
   unzip ssen-main.zip
   
   # Renombrar directorio
   mv simple-search-engine-notification-main simple-search-engine-notification
   
   # Entrar al directorio
   cd simple-search-engine-notification
   ```

3. **Establecer permisos:**
   ```bash
   sudo chown -R www-data:www-data .
   chmod 755 core_index/data

   # Directorio privado fuera del documento raíz (registro y credenciales)
   # El propietario debe ser el usuario que ejecuta PHP
   mkdir -p ../ssen-private/logs
   sudo chown -R USUARIO_PHP:USUARIO_PHP ../ssen-private
   chmod 700 ../ssen-private ../ssen-private/logs
   ```

**Ventajas:**
- No requiere Git
- Instalación más rápida
- Menor tamaño de descarga

### 2.2 Verificar Estructura de Directorios

#### Comando de Verificación

**Con el comando `tree` (recomendado):**
```bash
# Instalar tree si no está disponible
sudo apt install tree  # Ubuntu/Debian
sudo yum install tree  # CentOS/RHEL

# Visualizar estructura hasta 3 niveles
tree -L 3 core_index/
```

**Sin el comando `tree` (alternativa):**
```bash
# Listar estructura manualmente
ls -R core_index/ | grep ":$" | sed -e 's/:$//' -e 's/[^-][^\/]*\//--/g' -e 's/^/   /' -e 's/-/|/'
```

#### Estructura Esperada

```
core_index/
├── config/
│   ├── config.php
│   └── indexnow_key.txt
├── data/
│   ├── urls.json
│   └── urls.csv
├── tools/
│   ├── check_config.php
│   └── generate_indexnow_key.php
├── logger.php
├── sitemap_diff.php
├── urls_loader.php
├── sitemap_generator.php
├── sitemap_form_url.php
├── auth_guard.php
├── indexnow_auth.php
├── indexnow_client.php
├── google_indexing_auth.php
├── google_indexing_client.php
└── README.md
```

**Nota:** El registro se escribe fuera del documento raíz, en `../ssen-private/logs/` (`LOG_DIR` dentro de `PRIVATE_DIR`); el directorio `core_index/logs/` ya no existe.

#### Verificación Detallada de Archivos

**Listar todos los archivos PHP:**
```bash
find core_index -name "*.php" -type f | sort
```

**Salida esperada:**
```
core_index/auth_guard.php
core_index/config/config.php
core_index/google_indexing_auth.php
core_index/google_indexing_client.php
core_index/indexnow_auth.php
core_index/indexnow_client.php
core_index/logger.php
core_index/sitemap_diff.php
core_index/sitemap_form_url.php
core_index/sitemap_generator.php
core_index/tools/check_config.php
core_index/tools/generate_indexnow_key.php
core_index/urls_loader.php
```

**Total esperado:** 13 archivos PHP

**Verificar integridad con conteo:**
```bash
echo "Archivos PHP: $(find core_index -name "*.php" | wc -l)"
echo "Directorios: $(find core_index -type d | wc -l)"
```

**Resultado esperado:**
```
Archivos PHP: 13
Directorios: 4
```

### 2.3 Configuración Inicial de config.php

#### Ubicación del Archivo
```
core_index/config/config.php
```

#### Paso 1: Definir BASE_URL

**Abrir archivo para edición:**
```bash
nano core_index/config/config.php
```

**Localizar la línea (aproximadamente línea 17):**
```php
define('BASE_URL', 'https://tu-dominio.com');
```

**Reemplazar con tu dominio real:**
```php
define('BASE_URL', 'https://www.miempresa.com');
```

**Ejemplos según entorno:**

**Producción:**
```php
define('BASE_URL', 'https://www.miempresa.com');
```

**Producción con subdirectorio:**
```php
define('BASE_URL', 'https://ejemplo.com/sitio');
```

**Desarrollo local:**
```php
define('BASE_URL', 'http://localhost:8000');
```

**Desarrollo con puerto personalizado:**
```php
define('BASE_URL', 'http://localhost:3000');
```

**⚠️ IMPORTANTE:**
- NO incluir barra diagonal al final: ❌ `https://ejemplo.com/`
- Usar HTTPS en producción: ✅ `https://ejemplo.com`
- Verificar que el dominio sea accesible públicamente

#### Paso 2: Verificar Rutas del Sistema

**Localizar las siguientes líneas (aproximadamente líneas 22-43):**
```php
// Directorios del sistema
define('ROOT_DIR', dirname(__DIR__, 2));
define('CORE_DIR', ROOT_DIR . '/core_index');
define('CONFIG_DIR', CORE_DIR . '/config');
define('DATA_DIR', CORE_DIR . '/data');

// Directorio privado FUERA del documento raíz (registros, credenciales y autenticación)
define('PRIVATE_DIR', dirname(ROOT_DIR) . '/ssen-private');
define('LOG_DIR', PRIVATE_DIR . '/logs');

// Sitemap (en la raíz del sitio) y registro de actividad
define('SITEMAP_PATH', ROOT_DIR . '/sitemap.xml');
define('SITEMAP_OLD_PATH', ROOT_DIR . '/sitemap.old.xml');
define('LOG_PATH', LOG_DIR . '/indexing_api_sitemap.log');
```

**⚠️ USUALMENTE NO REQUIEREN CAMBIOS**

Estas rutas se calculan automáticamente a partir de la ubicación del proyecto (`ROOT_DIR` se obtiene con `dirname(__DIR__, 2)`). Solo modificar si:
- Has cambiado la estructura de directorios
- Necesitas almacenar datos o registros en una ubicación personalizada

**Ejemplo de rutas personalizadas:**
```php
// Almacenar datos y registros fuera de core_index
define('DATA_DIR', '/var/www/data/ssen');
define('LOG_DIR', '/var/log/ssen');
define('LOG_PATH', LOG_DIR . '/indexing_api_sitemap.log');
```

**Si modificas las rutas, asegúrate de:**
1. Crear los directorios manualmente
2. Establecer permisos de escritura
3. Usar rutas absolutas

#### Paso 3: Configurar IndexNow

**Localizar las siguientes líneas (aproximadamente líneas 45-50):**
```php
define('INDEXNOW_HOST', 'tu-dominio.com');
define('INDEXNOW_KEY_FILE', CONFIG_DIR . '/indexnow_key.txt');
define('INDEXNOW_TIMEOUT', 30);
define('INDEXNOW_MAX_URLS_PER_REQUEST', 10000);
```

**⚠️ NO MODIFICAR ESTAS LÍNEAS SALVO `INDEXNOW_HOST`**

`INDEXNOW_HOST` debe coincidir con el dominio de `BASE_URL`, sin protocolo (`tu-dominio.com`, no `https://tu-dominio.com`). El endpoint de IndexNow (`https://api.indexnow.org/indexnow`) está integrado en el cliente y no se define en `config.php`.

**Nota:** La clave IndexNow se configurará en el Capítulo 4.1

#### Paso 4: Configurar Google Indexing API

**Localizar las siguientes líneas (aproximadamente líneas 30 y 52-64):**
```php
// Directorio privado FUERA del documento raíz (registros, credenciales y autenticación)
define('PRIVATE_DIR', dirname(ROOT_DIR) . '/ssen-private');
define('GOOGLE_CREDENTIALS_PATH', PRIVATE_DIR . '/credentials.json');
```

**⚠️ NO MODIFICAR ESTAS LÍNEAS**

`GOOGLE_CREDENTIALS_PATH` apunta por defecto a `../ssen-private/credentials.json`, fuera del documento raíz y con permisos `600`. Nunca debe alojarse en `core_index/data/` ni dentro del sitio publicado. El endpoint de Google Indexing API está integrado en el cliente.

**Nota:** Las credenciales de Google se configurarán en el Capítulo 4.2

#### Resumen de Configuración

**Configuración mínima requerida:**
```php
<?php
// Línea ~17: ajustar al dominio propio (SIN barra final)
define('BASE_URL', 'https://tu-dominio.com');

// Líneas ~22-43: NO MODIFICAR (rutas automáticas)
define('ROOT_DIR', dirname(__DIR__, 2));
define('CORE_DIR', ROOT_DIR . '/core_index');
define('CONFIG_DIR', CORE_DIR . '/config');
define('DATA_DIR', CORE_DIR . '/data');
define('PRIVATE_DIR', dirname(ROOT_DIR) . '/ssen-private');
define('LOG_DIR', PRIVATE_DIR . '/logs');
define('SITEMAP_PATH', ROOT_DIR . '/sitemap.xml');
define('SITEMAP_OLD_PATH', ROOT_DIR . '/sitemap.old.xml');
define('LOG_PATH', LOG_DIR . '/indexing_api_sitemap.log');

// Líneas ~45-50: ajustar INDEXNOW_HOST al dominio propio (Capítulo 4.1)
define('INDEXNOW_HOST', 'tu-dominio.com');
define('INDEXNOW_KEY_FILE', CONFIG_DIR . '/indexnow_key.txt');

// Líneas ~52-64: NO MODIFICAR (credenciales opcionales, Capítulo 4.2)
define('GOOGLE_CREDENTIALS_PATH', PRIVATE_DIR . '/credentials.json');
?>
```

**Guardar y cerrar:**
- En nano: `Ctrl + O` (guardar), `Enter`, `Ctrl + X` (salir)
- En vim: `Esc`, `:wq`, `Enter`

### 2.4 Validar Configuración

#### Método 1: Validación con Script PHP

**Crear script de validación:**
```bash
nano validate_config.php
```

**Contenido del script:**
```php
<?php
echo "Validando configuración de SSEN...\n";
echo str_repeat("=", 50) . "\n";

// Cargar configuración
require_once 'core_index/config/config.php';

$errors = 0;
$warnings = 0;

// 1. Verificar BASE_URL
echo "[1/6] Verificando BASE_URL...\n";
if (defined('BASE_URL') && !empty(BASE_URL)) {
    echo "✓ BASE_URL definida: " . BASE_URL . "\n";
    
    // Validar formato
    if (filter_var(BASE_URL, FILTER_VALIDATE_URL)) {
        echo "✓ BASE_URL tiene formato válido\n";
    } else {
        echo "✗ BASE_URL tiene formato inválido\n";
        $errors++;
    }
} else {
    echo "✗ BASE_URL no está definida\n";
    $errors++;
}
echo "\n";

// 2. Verificar directorios
echo "[2/6] Verificando directorios...\n";
$dirs = [
    'DATA_DIR' => DATA_DIR,
    'LOG_DIR' => LOG_DIR
];

foreach ($dirs as $name => $path) {
    if (is_dir($path)) {
        echo "✓ $name existe: $path\n";
        
        if (is_writable($path)) {
            echo "✓ $name es escribible\n";
        } else {
            echo "✗ $name NO es escribible\n";
            $errors++;
        }
    } else {
        echo "✗ $name NO existe: $path\n";
        $errors++;
    }
}
echo "\n";

// 3. Verificar archivos de configuración de IndexNow
echo "[3/6] Verificando IndexNow...\n";
if (defined('INDEXNOW_KEY_FILE')) {
    echo "✓ INDEXNOW_KEY_FILE definido: " . INDEXNOW_KEY_FILE . "\n";
    
    if (file_exists(INDEXNOW_KEY_FILE)) {
        echo "✓ Archivo de clave IndexNow existe\n";
    } else {
        echo "⚠ Archivo de clave IndexNow NO existe (configura en Capítulo 4.1)\n";
        $warnings++;
    }
} else {
    echo "✗ INDEXNOW_KEY_FILE no definido\n";
    $errors++;
}
echo "\n";

// 4. Verificar archivos de credenciales de Google
echo "[4/6] Verificando Google Indexing API...\n";
if (defined('GOOGLE_CREDENTIALS_PATH')) {
    echo "✓ GOOGLE_CREDENTIALS_PATH definido: " . GOOGLE_CREDENTIALS_PATH . "\n";
    
    if (file_exists(GOOGLE_CREDENTIALS_PATH)) {
        echo "✓ Archivo credentials.json existe\n";
    } else {
        echo "⚠ Archivo credentials.json NO existe; la notificación a Google quedará deshabilitada (opcional, configura en Capítulo 4.2)\n";
        $warnings++;
    }
} else {
    echo "✗ GOOGLE_CREDENTIALS_PATH no definido\n";
    $errors++;
}
echo "\n";

// 5. Verificar módulos PHP
echo "[5/6] Verificando módulos del sistema...\n";
$modules = [
    'logger.php',
    'sitemap_diff.php',
    'indexnow_auth.php',
    'google_indexing_auth.php',
    'indexnow_client.php',
    'google_indexing_client.php',
    'sitemap_generator.php',
    'sitemap_form_url.php'
];

foreach ($modules as $module) {
    $path = 'core_index/' . $module;
    if (file_exists($path)) {
        echo "✓ $module existe\n";
    } else {
        echo "✗ $module NO existe\n";
        $errors++;
    }
}
echo "\n";

// 6. Verificar función de validación integrada
echo "[6/6] Ejecutando validación integrada del sistema...\n";
if (function_exists('validateConfiguration')) {
    $validation = validateConfiguration();
    if ($validation['valid']) {
        echo "✓ validateConfiguration(): sin errores\n";
    } else {
        echo "✗ validateConfiguration(): " . count($validation['errors']) . " errores\n";
        foreach ($validation['errors'] as $error) {
            echo "  - $error\n";
        }
        $errors += count($validation['errors']);
    }
    // Las advertencias no bloquean la ejecución (p. ej., credenciales de Google ausentes)
    foreach ($validation['warnings'] as $warning) {
        echo "  ⚠ $warning\n";
        $warnings++;
    }
} else {
    echo "⚠ Función validateConfiguration() no encontrada\n";
    $warnings++;
}
echo "\n";

// Resumen
echo str_repeat("=", 50) . "\n";
if ($errors === 0 && $warnings === 0) {
    echo "✓ CONFIGURACIÓN COMPLETA Y VÁLIDA\n";
    echo "Sistema listo para configuración de APIs (Capítulo 4)\n";
} elseif ($errors === 0) {
    echo "✓ CONFIGURACIÓN BÁSICA VÁLIDA\n";
    echo "⚠ $warnings advertencias (configuración opcional pendiente)\n";
} else {
    echo "✗ $errors ERRORES ENCONTRADOS\n";
    if ($warnings > 0) {
        echo "⚠ $warnings advertencias adicionales\n";
    }
    echo "Corrija los errores antes de continuar\n";
}
echo str_repeat("=", 50) . "\n";
?>
```

**Nota sobre el contrato de retorno:** `validateConfiguration()` devuelve un array con las claves `valid` (booleano), `errors` (lista de errores bloqueantes) y `warnings` (lista de advertencias no bloqueantes). La ausencia de credenciales de Google aparece en `warnings` y no impide la ejecución: la rama de Google queda deshabilitada.

**Ejecutar validación:**
```bash
php validate_config.php
```

**Salida esperada (con la clave de IndexNow pendiente del Capítulo 4.1):**
```
Validando configuración de SSEN...
==================================================
[1/6] Verificando BASE_URL...
✓ BASE_URL definida: https://tu-dominio.com
✓ BASE_URL tiene formato válido

[2/6] Verificando directorios...
✓ DATA_DIR existe: /ruta/al/proyecto/core_index/data
✓ DATA_DIR es escribible
✓ LOG_DIR existe: /ruta/al/ssen-private/logs
✓ LOG_DIR es escribible

[3/6] Verificando IndexNow...
✓ INDEXNOW_KEY_FILE definido: /ruta/al/proyecto/core_index/config/indexnow_key.txt
⚠ Archivo de clave IndexNow NO existe (configura en Capítulo 4.1)

[4/6] Verificando Google Indexing API...
✓ GOOGLE_CREDENTIALS_PATH definido: /ruta/al/ssen-private/credentials.json
⚠ Archivo credentials.json NO existe; la notificación a Google quedará deshabilitada (opcional, configura en Capítulo 4.2)

[5/6] Verificando módulos del sistema...
✓ logger.php existe
✓ sitemap_diff.php existe
✓ indexnow_auth.php existe
✓ google_indexing_auth.php existe
✓ indexnow_client.php existe
✓ google_indexing_client.php existe
✓ sitemap_generator.php existe
✓ sitemap_form_url.php existe

[6/6] Ejecutando validación integrada del sistema...
✗ validateConfiguration(): 1 errores
  - No existe el archivo de clave IndexNow: /ruta/al/proyecto/core_index/config/indexnow_key.txt (genérelo con: php core_index/tools/generate_indexnow_key.php)
  ⚠ Sin credenciales de Google; la notificación a Google Indexing API quedará deshabilitada: /ruta/al/ssen-private/credentials.json

==================================================
✗ 1 ERRORES ENCONTRADOS
⚠ 3 advertencias adicionales
Corrija los errores antes de continuar
==================================================
```

**Nota:** El único error pendiente es la clave de IndexNow, que se genera en el Capítulo 4.1; la ausencia de credenciales de Google aparece en `warnings` y no bloquea la ejecución (la rama de Google queda deshabilitada).

#### Método 2: Validación Manual Rápida

**Verificar solo BASE_URL:**
```bash
php -r "require 'core_index/config/config.php'; echo 'BASE_URL: ' . BASE_URL . PHP_EOL;"
```

**Verificar directorios escribibles:**
```bash
php -r "require 'core_index/config/config.php'; echo (is_writable(DATA_DIR) ? 'DATA_DIR: ✓ Escribible' : 'DATA_DIR: ✗ NO escribible') . PHP_EOL; echo (is_writable(LOG_DIR) ? 'LOG_DIR: ✓ Escribible' : 'LOG_DIR: ✗ NO escribible') . PHP_EOL;"
```

---

## Resumen de la Subfase 3.1

### ✅ Tareas Completadas

1. **Capítulo 1: Prerrequisitos**
   - Verificación de PHP 8.1+
   - Verificación de extensiones (openssl, curl, json, simplexml)
   - Configuración de permisos de archivos
   - Obtención de información sobre cuentas necesarias (IndexNow, Google)
   - Script de verificación de requisitos

2. **Capítulo 2: Instalación**
   - Clonación o descarga del repositorio
   - Verificación de estructura de directorios
   - Configuración inicial de `config.php` (BASE_URL e INDEXNOW_HOST)
   - Validación de configuración con scripts

### 📋 Próximos Pasos

**Subfase 3.2: Configuración de Datos y APIs**
- Crear archivos de entrada (urls.json, urls.csv)
- Generar y configurar clave IndexNow
- Configurar Google Indexing API (opcional)

### 🔗 Referencias

- **Documentación PHP:** https://www.php.net/manual/es/
- **IndexNow Protocol:** https://www.indexnow.org/documentation
- **Google Cloud Console:** https://console.cloud.google.com

---

**Fin de Subfase 3.1 - Fundamentos y Preparación Inicial**
