# Plan de Documentación Final SSEN

> **Documento histórico:** este plan refleja el estado anterior a la remediación de septiembre de 2026 y se conserva como referencia del proceso. La documentación vigente es `README.md` y `docs/INSTALACION_Y_DESPLIEGUE.md`.

**Fecha:** 10 de enero de 2026  
**Estado del Proyecto:** Sistema completado (39 archivos, 1,555 líneas de código PHP)  
**Objetivo:** Organizar documentación, actualizar README.md y crear manual de configuración

---

## FASE 1: Limpieza del Directorio de Documentación

### Archivos a MANTENER (8 archivos)

#### Categoría A: Documentación Esencial del Proyecto (5 archivos)
1. **docs/BPYT4_briefing.md**
   - Definición original del proyecto (nombre histórico BPYT4)
   - Objetivos, alcance y restricciones fundamentales
   - Documento base para entender la misión del sistema

2. **docs/AUDITORIA_TECNICA.md**
   - Análisis técnico del sistema original (389 archivos)
   - Diagnóstico que justificó la transformación
   - Referencia histórica del estado previo

3. **docs/conversion/RESUMEN_FINAL_BPYT4.md**
   - Resumen ejecutivo de la transformación
   - Métricas: 389→39 archivos, reducción 90%
   - Documento de cierre del proceso

4. **docs/conversion/LISTA_ELIMINACION_MERIDIANO.md**
   - Registro de 350+ archivos eliminados
   - Trazabilidad de la limpieza masiva (Fase 5.7)
   - Auditoría de cambios estructurales

5. **docs/conversion/BPYT4_RESULTADO_IMPLEMENTACION.md**
   - Inventario de los 9 módulos PHP implementados
   - Arquitectura modular (F2A, F2B, F2C)
   - Documentación técnica de referencia (nombre histórico BPYT4)

#### Categoría B: Referencias de API (1 archivo)
6. **docs/Conocimientos+Referencias_Indexing API.md**
   - Documentación técnica IndexNow y Google Indexing API
   - Especificaciones, códigos HTTP, límites
   - Referencia permanente para mantenimiento

#### Categoría C: Análisis Genérico Reutilizable (2 archivos)
7. **docs/REPORTE_BUSQUEDA_AMERICA_20260103.md**
   - Investigación de términos de búsqueda regional
   - Análisis SEO genérico (no específico de Meridiano)
   - Potencial reutilización en proyectos futuros

8. **core_index/README.md**
   - Documentación específica del sistema modular
   - Guía técnica de los 9 módulos PHP
   - Manual de referencia del núcleo del sistema

### Archivos a ELIMINAR (10 archivos)

#### Categoría D: Documentación Específica de Meridiano (7 archivos)
1. **docs/20260102_integration_plan_otras_secciones.md**
   - Plan de integración de secciones del blog Meridiano
   - Contenido: Arquitectura de 10 secciones específicas (salud, economía, etc.)
   - Razón: Las secciones fueron eliminadas, el plan ya no aplica

2. **docs/PLAN_IMPLEMENTACION_LA_FINAL.md**
   - Roadmap de features para el blog Meridiano
   - Contenido: Sistema de tags, comentarios, newsletter
   - Razón: Features específicas del blog, no del template

3. **docs/BITACORA_CAMBIOS_PROYECTO.md**
   - Historial de versiones del blog Meridiano
   - Contenido: Cambios de diseño, features agregadas
   - Razón: Log de proyecto finalizado, no aplica a template

4. **docs/BASE_COGNITIVA_ANALISIS_TECNICO_PROYECTO.md**
   - Análisis profundo (1,282 líneas) del blog Meridiano
   - Contenido: Estructura de posts/, seccion/, lógica de negocio
   - Razón: Sistema analizado fue completamente transformado

5. **docs/conversion/ESTADISTICAS_ELIMINACION.md** (si existe)
   - Estadísticas detalladas de archivos eliminados
   - Razón: Información contenida en LISTA_ELIMINACION_MERIDIANO.md

6. **docs/conversion/MERIDIANO_LEGACY_ANALYSIS.md** (si existe)
   - Análisis del sistema legacy de Meridiano
   - Razón: Sistema legacy ya no existe

7. **docs/conversion/BACKUP_CONFIG_MERIDIANO.md** (si existe)
   - Configuraciones específicas del blog
   - Razón: Configuraciones obsoletas tras transformación

#### Categoría E: Documentación de Transición Ejecutada (3 archivos)
8. **docs/conversion/PLAN_TRANSFORMACION_PASO_A_PASO.md** (si existe)
   - Plan detallado de transformación ya ejecutado
   - Razón: Proceso completado, resultado documentado en RESUMEN_FINAL

9. **docs/conversion/CHECKLIST_VALIDACION_BPYT4.md** (si existe)
   - Lista de verificación para validación (completada)
   - Razón: Validación realizada, todos los módulos pasan php -l

10. **docs/conversion/SCRIPT_ELIMINACION_COMMANDS.md** (si existe)
    - Scripts temporales de eliminación masiva
    - Razón: Eliminación ejecutada, registro en LISTA_ELIMINACION

### Resultado Esperado
- **Antes:** 18 archivos de documentación (10 en docs/, 8 en docs/conversion/)
- **Después:** 8 archivos de documentación (5 en docs/, 3 en docs/conversion/)
- **Reducción:** 56% de documentación obsoleta eliminada
- **Beneficio:** Documentación enfocada solo en SSEN y APIs

---

## FASE 2: Diseño de Nuevo README.md

### Estructura Propuesta (8 Secciones)

```markdown
# Simple Search Engine Notification (SSEN)

## 1. Descripción del Proyecto
- ¿Qué es SSEN?
- Objetivo: Template reutilizable para sitemap + notificación IndexNow/Google
- Origen: Transformación de 389 archivos → 39 archivos (90% reducción)
- Resultado: Sistema modular independiente del negocio

## 2. Características Principales
- ✅ Generación de sitemap.xml con ordenamiento por prioridad/fecha
- ✅ Detección automática de cambios (URLs nuevas/modificadas/eliminadas)
- ✅ Notificación a IndexNow (Bing, Yandex, otros motores)
- ✅ Notificación a Google Indexing API (OAuth 2.0)
- ✅ Soporte de entrada: JSON y CSV
- ✅ Registro detallado de operaciones (logs)
- ✅ Sistema de validación de credenciales
- ✅ Interfaz web para ejecución manual

## 3. Requisitos Técnicos
- PHP 7.4 o superior
- Extensiones: openssl, curl, json, simplexml
- Servidor web: Apache/Nginx/LiteSpeed
- Acceso a archivos del servidor (escritura en /data, /logs)
- Credenciales de API:
  - IndexNow: Clave de 32 caracteres hexadecimales
  - Google Indexing API: Archivo credentials.json (OAuth 2.0)

## 4. Estructura del Proyecto
```
core_index/                    # Sistema modular (9 módulos PHP)
├── config/                    # Configuración centralizada
│   └── config.php            # BASE_URL, rutas, configuración APIs
├── data/                      # Archivos de entrada y salida
│   ├── urls.json             # Ejemplo: URLs en formato JSON
│   ├── urls.csv              # Ejemplo: URLs en formato CSV
│   ├── sitemap.xml           # Sitemap generado
│   ├── sitemap.old.xml       # Sitemap previo (para comparación)
│   ├── indexnow_key.txt      # Clave IndexNow
│   └── credentials.json      # Credenciales Google (NO incluidas en repo)
├── logs/                      # Registros de operaciones
│   ├── indexnow_log.txt
│   └── google_indexing_log.txt
├── logger.php                 # Sistema de logging centralizado
├── sitemap_diff.php          # Comparador de sitemaps
├── sitemap_generator.php     # Orquestador principal
├── sitemap_form_url.php      # Interfaz web de entrada
├── indexnow_auth.php         # Autenticación IndexNow
├── indexnow_client.php       # Cliente HTTP IndexNow
├── google_indexing_auth.php  # Autenticación Google OAuth 2.0
├── google_indexing_client.php # Cliente HTTP Google Indexing API
└── README.md                  # Documentación técnica de módulos

gestion/
├── result_sitemap.php        # Interfaz de resultados (Bootstrap)
└── generate_sitemap.php      # Punto de entrada alternativo

docs/
├── BPYT4_briefing.md         # Definición del proyecto
├── AUDITORIA_TECNICA.md      # Análisis del sistema original
├── Conocimientos+Referencias_Indexing API.md  # Documentación APIs
└── MANUAL_CONFIGURACION.md   # Manual completo (ver Fase 3)

docs/conversion/
├── RESUMEN_FINAL_BPYT4.md    # Resumen de transformación
├── LISTA_ELIMINACION_MERIDIANO.md  # Archivos eliminados
└── BPYT4_RESULTADO_IMPLEMENTACION.md  # Módulos implementados
```

## 5. Flujo de Operación
1. Usuario accede a `sitemap_form_url.php?source=json` o `?source=csv`
2. Sistema carga URLs desde archivo especificado
3. Genera sitemap.xml ordenado por prioridad y fecha
4. Compara con sitemap.old.xml para detectar cambios
5. Notifica URLs a IndexNow (si hay cambios)
6. Notifica URLs a Google Indexing API (si hay cambios)
7. Registra operaciones en logs individuales
8. Muestra resultados en `result_sitemap.php`

## 6. Inicio Rápido

### Paso 1: Configuración Básica
1. Copia el proyecto a tu servidor
2. Edita `core_index/config/config.php`:
   - Define `BASE_URL` (URL de tu sitio)
3. Crea archivo `core_index/data/indexnow_key.txt` con tu clave IndexNow

### Paso 2: Preparar URLs
Crea uno de estos archivos en `core_index/data/`:

**Opción A: urls.json**
```json
[
  {"url": "https://example.com/page1", "priority": 1.0, "changefreq": "daily", "lastmod": "2026-01-10"},
  {"url": "https://example.com/page2", "priority": 0.8, "changefreq": "weekly", "lastmod": "2026-01-09"}
]
```

**Opción B: urls.csv**
```
url,priority,changefreq,lastmod
https://example.com/page1,1.0,daily,2026-01-10
https://example.com/page2,0.8,weekly,2026-01-09
```

### Paso 3: Ejecutar
Accede a: `https://tudominio.com/core_index/sitemap_form_url.php?source=json`

### Paso 4 (Opcional): Configurar Google Indexing API
1. Crea proyecto en Google Cloud Console
2. Habilita Indexing API
3. Crea cuenta de servicio, descarga `credentials.json`
4. Copia a `core_index/data/credentials.json`
5. NO subas este archivo a Git (incluido en .gitignore)

## 7. Documentación Adicional
- **Manual de Configuración:** [docs/MANUAL_CONFIGURACION.md](docs/MANUAL_CONFIGURACION.md)
- **Documentación Técnica:** [core_index/README.md](core_index/README.md)
- **Referencias de API:** [docs/Conocimientos+Referencias_Indexing API.md](docs/Conocimientos+Referencias_Indexing%20API.md)
- **Briefing del Proyecto:** [docs/BPYT4_briefing.md](docs/BPYT4_briefing.md)

## 8. Información del Proyecto
- **Versión:** 1.0.0 (Template SSEN)
- **Fecha de Creación:** Enero 2026
- **Licencia:** [Especificar licencia]
- **Autor:** [Especificar autor/organización]
- **Repositorio:** https://github.com/paginaviva/simple-search-engine-notification
- **Métricas de Transformación:**
  - Archivos: 389 → 39 (90% reducción)
  - Código PHP: ~50,000 líneas → 1,555 líneas (97% reducción)
  - Tamaño: 15 MB → 3.6 MB (76% reducción)
```

### Estimación
- **Longitud:** 200-250 líneas
- **Formato:** Markdown con bloques de código, listas, tablas
- **Estilo:** Profesional, directo, orientado a desarrolladores
- **Público objetivo:** Desarrolladores PHP que quieran usar el template

---

## FASE 3: Manual de Configuración y Puesta en Marcha

### Archivo a Crear
**Ruta:** `docs/MANUAL_CONFIGURACION.md`  
**Propósito:** Guía completa paso a paso para configurar y usar el sistema  
**Audiencia:** Usuarios técnicos y no técnicos

### Índice Detallado del Manual

```markdown
# Manual de Configuración y Puesta en Marcha - SSEN

## Tabla de Contenidos
1. [Prerrequisitos](#1-prerrequisitos)
2. [Instalación](#2-instalación)
3. [Configuración de Archivos de Entrada](#3-configuración-de-archivos-de-entrada)
4. [Configuración de APIs](#4-configuración-de-apis)
5. [Ejecución del Sistema](#5-ejecución-del-sistema)
6. [Solución de Problemas](#6-solución-de-problemas)
7. [Mantenimiento y Mejores Prácticas](#7-mantenimiento-y-mejores-prácticas)
8. [Referencia Rápida](#8-referencia-rápida)

---

## 1. Prerrequisitos

### 1.1 Requisitos del Servidor
- **PHP:** Versión 7.4 o superior
- **Servidor Web:** Apache, Nginx o LiteSpeed
- **Extensiones PHP Requeridas:**
  ```bash
  php -m | grep -E 'openssl|curl|json|simplexml'
  ```
  - `openssl`: Para autenticación OAuth 2.0 (Google)
  - `curl`: Para llamadas HTTP a APIs
  - `json`: Para procesar archivos JSON
  - `simplexml`: Para generar/parsear XML

### 1.2 Permisos de Archivos
- **Lectura:** Todos los archivos PHP
- **Escritura:** Directorios `core_index/data/` y `core_index/logs/`
  ```bash
  chmod 755 core_index/data core_index/logs
  chmod 644 core_index/data/*.{json,csv,xml,txt}
  ```

### 1.3 Cuentas y Credenciales Necesarias
- **IndexNow:** Clave API gratuita (ver sección 4.1)
- **Google Indexing API (opcional):** Proyecto en Google Cloud Console (ver sección 4.2)

### 1.4 Verificación Inicial
```bash
# Verificar versión de PHP
php -v

# Verificar extensiones
php -m | grep -E 'openssl|curl|json|simplexml'

# Verificar sintaxis de todos los módulos
find core_index -name "*.php" -exec php -l {} \;
```

---

## 2. Instalación

### 2.1 Clonar o Descargar el Repositorio
```bash
# Opción A: Git
git clone https://github.com/paginaviva/simple-search-engine-notification.git
cd simple-search-engine-notification

# Opción B: Descarga ZIP
# Descargar desde GitHub y extraer
```

### 2.2 Verificar Estructura de Directorios
```bash
tree -L 3 core_index/
```

**Estructura esperada:**
```
core_index/
├── config/
│   └── config.php
├── data/
│   ├── urls.json (ejemplo)
│   ├── urls.csv (ejemplo)
│   └── indexnow_key.txt (ejemplo)
├── logs/
├── logger.php
├── sitemap_diff.php
├── sitemap_generator.php
├── sitemap_form_url.php
├── indexnow_auth.php
├── indexnow_client.php
├── google_indexing_auth.php
├── google_indexing_client.php
└── README.md
```

### 2.3 Configuración Inicial de config.php

**Archivo:** `core_index/config/config.php`

#### Paso 1: Definir BASE_URL
```php
// Línea ~10
define('BASE_URL', 'https://tu-dominio.com');
```

**Ejemplos:**
- Producción: `https://www.miempresa.com`
- Desarrollo: `http://localhost:8000`
- Subdirectorio: `https://ejemplo.com/sitio`

#### Paso 2: Verificar Rutas (usualmente no requieren cambios)
```php
// Líneas ~15-20
define('DATA_DIR', __DIR__ . '/../data/');
define('LOGS_DIR', __DIR__ . '/../logs/');
define('SITEMAP_PATH', DATA_DIR . 'sitemap.xml');
define('SITEMAP_OLD_PATH', DATA_DIR . 'sitemap.old.xml');
```

#### Paso 3: Configurar IndexNow
```php
// Líneas ~25-30
define('INDEXNOW_KEY_FILE', DATA_DIR . 'indexnow_key.txt');
define('INDEXNOW_ENDPOINT', 'https://api.indexnow.org/indexnow');
```

#### Paso 4: Configurar Google Indexing API (si aplica)
```php
// Líneas ~35-40
define('GOOGLE_CREDENTIALS_FILE', DATA_DIR . 'credentials.json');
define('GOOGLE_INDEXING_ENDPOINT', 'https://indexing.googleapis.com/v3/urlNotifications:publish');
```

### 2.4 Validar Configuración
```bash
php core_index/config/config.php
```

**Salida esperada:**
```
[OK] BASE_URL definida: https://tu-dominio.com
[OK] Directorio de datos existe y es escribible
[OK] Directorio de logs existe y es escribible
[WARNING] IndexNow key file no encontrado (configura en paso 4.1)
[WARNING] Google credentials no encontrados (opcional, configura en paso 4.2)
```

---

## 3. Configuración de Archivos de Entrada

### 3.1 Formato JSON

**Archivo:** `core_index/data/urls.json`

**Estructura:**
```json
[
  {
    "url": "https://tu-dominio.com/pagina",
    "priority": 1.0,
    "changefreq": "daily",
    "lastmod": "2026-01-10"
  }
]
```

**Campos:**
- `url` (obligatorio): URL completa de la página
- `priority` (opcional): 0.0 a 1.0 (default: 0.5)
- `changefreq` (opcional): always, hourly, daily, weekly, monthly, yearly, never
- `lastmod` (opcional): Fecha en formato YYYY-MM-DD

**Ejemplo Completo:**
```json
[
  {
    "url": "https://ejemplo.com/",
    "priority": 1.0,
    "changefreq": "daily",
    "lastmod": "2026-01-10"
  },
  {
    "url": "https://ejemplo.com/productos",
    "priority": 0.9,
    "changefreq": "weekly",
    "lastmod": "2026-01-09"
  },
  {
    "url": "https://ejemplo.com/blog/articulo-1",
    "priority": 0.7,
    "changefreq": "monthly",
    "lastmod": "2026-01-05"
  },
  {
    "url": "https://ejemplo.com/contacto",
    "priority": 0.5,
    "changefreq": "yearly"
  }
]
```

**Validación:**
```bash
php -r "json_decode(file_get_contents('core_index/data/urls.json')); echo json_last_error() === JSON_ERROR_NONE ? 'OK' : 'ERROR';"
```

### 3.2 Formato CSV

**Archivo:** `core_index/data/urls.csv`

**Estructura:**
```csv
url,priority,changefreq,lastmod
https://ejemplo.com/,1.0,daily,2026-01-10
https://ejemplo.com/productos,0.9,weekly,2026-01-09
```

**Reglas:**
- Primera fila: encabezados (obligatorios)
- `url` (obligatorio): URL completa
- `priority` (opcional): número entre 0.0 y 1.0
- `changefreq` (opcional): valores estándar del protocolo sitemap
- `lastmod` (opcional): formato YYYY-MM-DD

**Ejemplo Completo:**
```csv
url,priority,changefreq,lastmod
https://ejemplo.com/,1.0,daily,2026-01-10
https://ejemplo.com/productos,0.9,weekly,2026-01-09
https://ejemplo.com/servicios,0.9,weekly,2026-01-09
https://ejemplo.com/blog,0.8,daily,2026-01-10
https://ejemplo.com/blog/post-1,0.7,monthly,2026-01-08
https://ejemplo.com/blog/post-2,0.7,monthly,2026-01-07
https://ejemplo.com/sobre-nosotros,0.6,monthly,2026-01-01
https://ejemplo.com/contacto,0.5,yearly,2025-12-15
```

### 3.3 Generación Automática de URLs

**Opción A: Desde base de datos MySQL**
```php
// generate_urls_json.php
<?php
$mysqli = new mysqli('localhost', 'usuario', 'password', 'base_datos');
$result = $mysqli->query("SELECT url, priority, changefreq, lastmod FROM paginas");

$urls = [];
while ($row = $result->fetch_assoc()) {
    $urls[] = $row;
}

file_put_contents('core_index/data/urls.json', json_encode($urls, JSON_PRETTY_PRINT));
echo "URLs exportadas: " . count($urls);
?>
```

**Opción B: Desde sistema de archivos**
```php
// generate_urls_from_posts.php
<?php
$posts = glob('post/*.php');
$urls = [];

foreach ($posts as $post) {
    $slug = basename($post, '.php');
    $urls[] = [
        'url' => "https://ejemplo.com/post/$slug",
        'priority' => 0.8,
        'changefreq' => 'monthly',
        'lastmod' => date('Y-m-d', filemtime($post))
    ];
}

file_put_contents('core_index/data/urls.json', json_encode($urls, JSON_PRETTY_PRINT));
echo "URLs generadas: " . count($urls);
?>
```

---

## 4. Configuración de APIs

### 4.1 IndexNow API (Bing, Yandex, otros)

#### Paso 1: Generar Clave IndexNow
```bash
# Generar clave de 32 caracteres hexadecimales
openssl rand -hex 16 > core_index/data/indexnow_key.txt

# Verificar
cat core_index/data/indexnow_key.txt
```

**Ejemplo de clave:** `a1b2c3d4e5f6789012345678abcdef90`

#### Paso 2: Publicar Clave en Raíz del Sitio
```bash
# Copiar clave a raíz del sitio web
cp core_index/data/indexnow_key.txt /ruta/a/raiz/sitio/a1b2c3d4e5f6789012345678abcdef90.txt

# O crear vínculo simbólico
ln -s core_index/data/indexnow_key.txt /ruta/raiz/a1b2c3d4e5f6789012345678abcdef90.txt
```

**Verificación:**
- Acceder a: `https://tu-dominio.com/a1b2c3d4e5f6789012345678abcdef90.txt`
- Debe mostrar la misma clave

#### Paso 3: Probar Autenticación
```bash
php -r "require 'core_index/indexnow_auth.php'; \$creds = getIndexNowCredentials('core_index/config/config.php'); print_r(\$creds);"
```

**Salida esperada:**
```
Array
(
    [key] => a1b2c3d4e5f6789012345678abcdef90
    [keyLocation] => https://tu-dominio.com/a1b2c3d4e5f6789012345678abcdef90.txt
)
```

### 4.2 Google Indexing API (Opcional)

#### Paso 1: Crear Proyecto en Google Cloud Console
1. Acceder a: https://console.cloud.google.com
2. Crear nuevo proyecto: "Indexing API - [Nombre Sitio]"
3. Anotar el Project ID

#### Paso 2: Habilitar Indexing API
1. En el menú: **APIs & Services → Library**
2. Buscar: "Indexing API"
3. Hacer clic en **ENABLE**

#### Paso 3: Crear Cuenta de Servicio
1. **APIs & Services → Credentials**
2. **CREATE CREDENTIALS → Service Account**
3. Nombre: "indexing-service-account"
4. Rol: **Owner** (o crear rol personalizado con permisos indexing)
5. **DONE**

#### Paso 4: Generar Clave JSON
1. En la lista de cuentas de servicio, hacer clic en la cuenta creada
2. Pestaña **KEYS → ADD KEY → Create new key**
3. Tipo: **JSON**
4. Descargar archivo (ejemplo: `proyecto-123456-abc789.json`)

#### Paso 5: Configurar Clave en el Sistema
```bash
# Renombrar y copiar a directorio de datos
cp ~/Downloads/proyecto-123456-abc789.json core_index/data/credentials.json

# Verificar permisos
chmod 600 core_index/data/credentials.json
```

#### Paso 6: Autorizar Cuenta en Google Search Console
1. Abrir el archivo `credentials.json`
2. Copiar el valor de `"client_email"` (ejemplo: `indexing-service-account@proyecto-123456.iam.gserviceaccount.com`)
3. Acceder a: https://search.google.com/search-console
4. Seleccionar tu propiedad
5. **Configuración → Usuarios y permisos → AGREGAR USUARIO**
6. Pegar el email de la cuenta de servicio
7. Permiso: **Propietario**
8. **AGREGAR**

#### Paso 7: Probar Autenticación
```bash
php -r "require 'core_index/google_indexing_auth.php'; \$token = getGoogleIndexingToken('core_index/data/credentials.json'); echo 'Token: ' . substr(\$token, 0, 50) . '...';"
```

**Salida esperada:**
```
Token: ya29.c.Kp8B9QdT... (truncado)
```

---

## 5. Ejecución del Sistema

### 5.1 Ejecución Manual desde Navegador

#### Opción A: Con archivo JSON
```
https://tu-dominio.com/core_index/sitemap_form_url.php?source=json
```

#### Opción B: Con archivo CSV
```
https://tu-dominio.com/core_index/sitemap_form_url.php?source=csv
```

**Resultado:**
- Se genera `sitemap.xml`
- Se detectan cambios respecto a `sitemap.old.xml`
- Se notifica a IndexNow
- Se notifica a Google Indexing API
- Se muestra página de resultados

### 5.2 Ejecución Automatizada con Cron

**Ejemplo: Actualizar sitemap cada 6 horas**
```bash
crontab -e
```

**Agregar línea:**
```
0 */6 * * * /usr/bin/php /ruta/completa/core_index/sitemap_generator.php > /dev/null 2>&1
```

**Variantes:**
```bash
# Cada día a las 3:00 AM
0 3 * * * /usr/bin/php /ruta/sitemap_generator.php

# Cada hora
0 * * * * /usr/bin/php /ruta/sitemap_generator.php

# Cada lunes a las 8:00 AM
0 8 * * 1 /usr/bin/php /ruta/sitemap_generator.php
```

### 5.3 Ejecución desde Línea de Comandos

```bash
cd /ruta/al/proyecto
php core_index/sitemap_generator.php
```

**Parámetros opcionales (si se modifican los scripts):**
```bash
# Forzar regeneración sin comparar
php core_index/sitemap_generator.php --force

# Notificar solo a IndexNow
php core_index/sitemap_generator.php --service=indexnow

# Modo silencioso (sin output)
php core_index/sitemap_generator.php --quiet
```

### 5.4 Interpretación de Resultados

**Página de Resultados (result_sitemap.php):**

| Estado | Significado | Acción Tomada |
|--------|-------------|---------------|
| ✅ URLs Nuevas | URLs que no existían en sitemap anterior | Notificadas a ambas APIs |
| ✏️ URLs Actualizadas | URLs con cambio en lastmod o priority | Notificadas a ambas APIs |
| ❌ URLs Eliminadas | URLs que ya no están en la lista actual | Notificadas a Google como URL_DELETED |
| ℹ️ Sin Cambios | No hay diferencias respecto al sitemap previo | No se envían notificaciones |

**Logs Generados:**

1. **indexnow_log.txt**
   ```
   [2026-01-10 14:30:15] Notificación IndexNow | IndexNow | https://ejemplo.com/page1 | 200 | Exitosa | IndexNow notificado correctamente
   ```

2. **google_indexing_log.txt**
   ```
   [2026-01-10 14:30:18] Notificación Google | Google Indexing API | https://ejemplo.com/page1 | 200 | Exitosa | URL_UPDATED notificado
   ```

---

## 6. Solución de Problemas

### 6.1 Error: "IndexNow key not found"

**Causa:** Archivo `indexnow_key.txt` no existe o está vacío

**Solución:**
```bash
# Generar nueva clave
openssl rand -hex 16 > core_index/data/indexnow_key.txt

# Publicar en raíz del sitio
KEY=$(cat core_index/data/indexnow_key.txt)
cp core_index/data/indexnow_key.txt /ruta/raiz/${KEY}.txt
```

### 6.2 Error: "Google credentials file not found"

**Causa:** Archivo `credentials.json` no está en `core_index/data/`

**Solución:**
```bash
# Verificar ubicación
ls -la core_index/data/credentials.json

# Copiar si está en otro lugar
cp /ruta/al/archivo.json core_index/data/credentials.json

# Verificar permisos
chmod 600 core_index/data/credentials.json
```

### 6.3 Error: "Failed to get Google Indexing token"

**Causas Posibles:**
1. `credentials.json` corrupto o inválido
2. Cuenta de servicio no autorizada en Search Console
3. Indexing API no habilitada en el proyecto

**Diagnóstico:**
```bash
# Verificar JSON válido
php -r "json_decode(file_get_contents('core_index/data/credentials.json')); echo json_last_error_msg();"

# Probar manualmente
php -r "require 'core_index/google_indexing_auth.php'; try { \$token = getGoogleIndexingToken('core_index/data/credentials.json'); echo 'OK'; } catch (Exception \$e) { echo \$e->getMessage(); }"
```

**Solución:**
- Revisar pasos en sección 4.2
- Verificar que el `client_email` del JSON esté agregado como propietario en Search Console

### 6.4 Error: "Directory not writable"

**Causa:** Permisos insuficientes en `data/` o `logs/`

**Solución:**
```bash
# Cambiar propietario (reemplazar www-data con el usuario de tu servidor web)
chown -R www-data:www-data core_index/data core_index/logs

# Establecer permisos
chmod 755 core_index/data core_index/logs
chmod 644 core_index/data/*.{json,csv,xml,txt}
```

### 6.5 Error HTTP 403 en IndexNow

**Causa:** Clave no publicada correctamente en raíz del sitio

**Verificación:**
```bash
# Obtener clave
KEY=$(cat core_index/data/indexnow_key.txt)

# Probar acceso
curl https://tu-dominio.com/${KEY}.txt
```

**Solución:**
```bash
# Copiar a raíz del sitio
KEY=$(cat core_index/data/indexnow_key.txt)
cp core_index/data/indexnow_key.txt /var/www/html/${KEY}.txt

# Verificar con navegador
echo "Verificar: https://tu-dominio.com/${KEY}.txt"
```

### 6.6 Error HTTP 403 en Google Indexing API

**Causa:** Cuenta de servicio no tiene permisos en Search Console

**Solución:**
1. Abrir `core_index/data/credentials.json`
2. Copiar el valor de `"client_email"`
3. Acceder a Google Search Console
4. Agregar ese email como **Propietario** en la propiedad

### 6.7 Sitemap Generado Está Vacío

**Diagnóstico:**
```bash
# Verificar archivo de entrada
cat core_index/data/urls.json
# o
cat core_index/data/urls.csv

# Probar generación manualmente
php -r "require 'core_index/sitemap_form_url.php'; \$urls = loadUrlsFromJSON('core_index/data/urls.json'); print_r(\$urls);"
```

**Causas Posibles:**
1. Archivo de entrada vacío o mal formado
2. JSON con errores de sintaxis
3. CSV sin encabezados correctos

---

## 7. Mantenimiento y Mejores Prácticas

### 7.1 Rotación de Logs

**Problema:** Logs crecen indefinidamente

**Solución:** Implementar rotación mensual
```bash
# Script: rotate_logs.sh
#!/bin/bash
cd /ruta/al/proyecto/core_index/logs
FECHA=$(date +%Y%m%d)

mv indexnow_log.txt indexnow_log_${FECHA}.txt
mv google_indexing_log.txt google_indexing_log_${FECHA}.txt

gzip indexnow_log_${FECHA}.txt
gzip google_indexing_log_${FECHA}.txt

# Eliminar logs de más de 6 meses
find . -name "*_log_*.txt.gz" -mtime +180 -delete
```

**Cron:**
```bash
0 0 1 * * /ruta/a/rotate_logs.sh
```

### 7.2 Backup de Credenciales

**IMPORTANTE:** `credentials.json` NO debe estar en control de versiones

**Estrategia de backup:**
```bash
# Backup encriptado
tar -czf - core_index/data/credentials.json core_index/data/indexnow_key.txt | \
gpg --symmetric --cipher-algo AES256 > credenciales_backup_$(date +%Y%m%d).tar.gz.gpg

# Restaurar
gpg --decrypt credenciales_backup_20260110.tar.gz.gpg | tar -xz
```

### 7.3 Monitoreo de Notificaciones

**Script de verificación:**
```bash
# check_notifications.sh
#!/bin/bash
LOG_FILE="core_index/logs/indexnow_log.txt"

# Contar errores en últimas 24 horas
ERRORS=$(tail -1000 "$LOG_FILE" | grep -c "Error")

if [ $ERRORS -gt 10 ]; then
    echo "ALERTA: $ERRORS errores en IndexNow" | mail -s "Alerta BPYT4" admin@ejemplo.com
fi
```

### 7.4 Validación de Sitemap

**Verificar sitemap generado:**
```bash
# Validar XML
xmllint --noout core_index/data/sitemap.xml && echo "XML válido"

# Contar URLs
grep -c "<url>" core_index/data/sitemap.xml

# Verificar accesibilidad
curl -I https://tu-dominio.com/core_index/data/sitemap.xml
```

### 7.5 Actualización de URLs

**Estrategia recomendada:**
1. Mantener script de generación automática (ver 3.3)
2. Ejecutar antes de la notificación
3. Ejemplo de flujo completo:

```bash
#!/bin/bash
# update_and_notify.sh

cd /ruta/al/proyecto

# Generar URLs desde base de datos
php scripts/generate_urls_json.php

# Ejecutar notificación
php core_index/sitemap_generator.php

# Verificar resultado
tail -5 core_index/logs/indexnow_log.txt
```

---

## 8. Referencia Rápida

### Comandos Esenciales

```bash
# Validar configuración
php core_index/config/config.php

# Generar sitemap y notificar
php core_index/sitemap_generator.php

# Verificar sintaxis de todos los módulos
find core_index -name "*.php" -exec php -l {} \;

# Ver últimas notificaciones
tail -20 core_index/logs/indexnow_log.txt
tail -20 core_index/logs/google_indexing_log.txt

# Contar URLs en sitemap
grep -c "<url>" core_index/data/sitemap.xml

# Verificar clave IndexNow
cat core_index/data/indexnow_key.txt
curl https://tu-dominio.com/$(cat core_index/data/indexnow_key.txt).txt
```

### URLs de Acceso

```
# Interfaz web (JSON)
https://tu-dominio.com/core_index/sitemap_form_url.php?source=json

# Interfaz web (CSV)
https://tu-dominio.com/core_index/sitemap_form_url.php?source=csv

# Sitemap generado
https://tu-dominio.com/core_index/data/sitemap.xml
```

### Archivos Críticos

| Archivo | Propósito | Editar |
|---------|-----------|--------|
| `config/config.php` | Configuración base | Sí (BASE_URL) |
| `data/indexnow_key.txt` | Clave IndexNow | No (generada) |
| `data/credentials.json` | Credenciales Google | No (descargada) |
| `data/urls.json` | Lista de URLs | Sí (manual o script) |
| `data/sitemap.xml` | Sitemap generado | No (generado) |
| `logs/indexnow_log.txt` | Log de IndexNow | No (automático) |
| `logs/google_indexing_log.txt` | Log de Google | No (automático) |

### Códigos de Estado HTTP

#### IndexNow
| Código | Significado | Acción |
|--------|-------------|--------|
| 200 | Éxito | URLs aceptadas y procesadas |
| 202 | Aceptado | URLs en cola para procesamiento |
| 400 | Bad Request | Revisar formato de URLs |
| 403 | Forbidden | Verificar clave en raíz del sitio |
| 422 | Unprocessable | URLs inválidas o duplicadas |
| 429 | Too Many Requests | Reducir frecuencia de notificaciones |

#### Google Indexing API
| Código | Significado | Acción |
|--------|-------------|--------|
| 200 | Éxito | URL notificada correctamente |
| 403 | Forbidden | Verificar permisos en Search Console |
| 429 | Too Many Requests | Implementar delay entre solicitudes |
| 500 | Internal Server Error | Reintentar más tarde |

### Frecuencias Recomendadas

| Tipo de Sitio | Frecuencia Cron | Comando |
|---------------|-----------------|---------|
| Blog (posts diarios) | Cada 6 horas | `0 */6 * * *` |
| E-commerce (inventario dinámico) | Cada 2 horas | `0 */2 * * *` |
| Sitio corporativo (cambios esporádicos) | 1 vez al día | `0 3 * * *` |
| Portal de noticias (actualizaciones constantes) | Cada hora | `0 * * * *` |

### Límites de APIs

| API | Límite | Notas |
|-----|--------|-------|
| IndexNow | 10,000 URLs por request | Sin límite de frecuencia documentado |
| Google Indexing API | 200 requests/min | Implementar delay de 100-300ms entre llamadas |
| Google Indexing API | 200 quotas/día | Quota por cuenta de servicio |

---

## Apéndices

### A. Estructura Completa del Proyecto
[Ver README.md principal, sección 4]

### B. Formato Completo de credentials.json
```json
{
  "type": "service_account",
  "project_id": "proyecto-id-123456",
  "private_key_id": "abc123...",
  "private_key": "-----BEGIN PRIVATE KEY-----\n...\n-----END PRIVATE KEY-----\n",
  "client_email": "indexing-service-account@proyecto-id-123456.iam.gserviceaccount.com",
  "client_id": "123456789012345678901",
  "auth_uri": "https://accounts.google.com/o/oauth2/auth",
  "token_uri": "https://oauth2.googleapis.com/token",
  "auth_provider_x509_cert_url": "https://www.googleapis.com/oauth2/v1/certs",
  "client_x509_cert_url": "https://www.googleapis.com/robot/v1/metadata/x509/..."
}
```

### C. Plantilla de Ejemplo urls.json Completo
```json
[
  {
    "url": "https://ejemplo.com/",
    "priority": 1.0,
    "changefreq": "daily",
    "lastmod": "2026-01-10"
  },
  {
    "url": "https://ejemplo.com/productos",
    "priority": 0.9,
    "changefreq": "weekly",
    "lastmod": "2026-01-09"
  },
  {
    "url": "https://ejemplo.com/servicios",
    "priority": 0.9,
    "changefreq": "weekly",
    "lastmod": "2026-01-09"
  },
  {
    "url": "https://ejemplo.com/blog",
    "priority": 0.8,
    "changefreq": "daily",
    "lastmod": "2026-01-10"
  },
  {
    "url": "https://ejemplo.com/blog/articulo-1",
    "priority": 0.7,
    "changefreq": "monthly",
    "lastmod": "2026-01-08"
  },
  {
    "url": "https://ejemplo.com/sobre-nosotros",
    "priority": 0.6,
    "changefreq": "monthly",
    "lastmod": "2026-01-01"
  },
  {
    "url": "https://ejemplo.com/contacto",
    "priority": 0.5,
    "changefreq": "yearly",
    "lastmod": "2025-12-15"
  }
]
```

### D. Enlaces Útiles
- **IndexNow Documentation:** https://www.indexnow.org/documentation
- **Google Indexing API:** https://developers.google.com/search/apis/indexing-api/v3/quickstart
- **Google Cloud Console:** https://console.cloud.google.com
- **Google Search Console:** https://search.google.com/search-console
- **Sitemap Protocol:** https://www.sitemaps.org/protocol.html

---

**Versión del Manual:** 1.0.0  
**Fecha de Última Actualización:** 10 de enero de 2026  
**Mantenedor:** [Especificar]
```

### Estimación
- **Longitud:** 800-1,000 líneas
- **Formato:** Markdown con bloques de código, tablas, listas
- **Secciones:** 8 capítulos + 4 apéndices
- **Estilo:** Tutorial paso a paso, exhaustivo
- **Público objetivo:** Usuarios técnicos que implementan el sistema desde cero

---

## Resumen de Fases

| Fase | Acción | Archivos Afectados | Estado |
|------|--------|-------------------|--------|
| **Fase 1** | Limpieza de documentación | 10 archivos a eliminar | ⏳ Pendiente aprobación |
| **Fase 2** | Creación de README.md | 1 archivo a crear (200-250 líneas) | ⏳ Pendiente aprobación |
| **Fase 3** | Creación de Manual | 1 archivo a crear (800-1,000 líneas) | ⏳ Pendiente aprobación |

**Total de Trabajo Estimado:**
- Eliminación de archivos: 10 operaciones
- Creación de documentación: ~1,100 líneas de contenido estructurado
- Tiempo estimado: 2-3 horas de redacción + revisión

---

**Fin del Plan de Documentación Final SSEN**
