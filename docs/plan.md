# PLAN DE ACTUACIÓN: Limpieza de Referencias y Actualización de Dominios
## Proyecto: Simple Search Engine Notification (SSEN)

> **Documento histórico:** este plan refleja el estado anterior a la remediación de septiembre de 2026 y se conserva como referencia del proceso. La documentación vigente es `README.md` y `docs/INSTALACION_Y_DESPLIEGUE.md`.

**Fecha:** 10 de enero de 2026  
**Objetivo:** Eliminar todas las referencias al proyecto original y neutralizar el código para uso como template genérico.

---

## 1. LIMPIEZA DE REFERENCIAS AL PROYECTO ORIGINAL

### 1.1. Referencias a "Meridiano" / "blog Meridiano" / "MBB"

#### **A. Archivos de Configuración (CRÍTICO - Afecta funcionamiento)**

| # | Archivo | Línea | Texto Actual | Texto Propuesto | Justificación |
|---|---------|-------|--------------|-----------------|---------------|
| 1 | `config.php` | 5 | `define('OG_SITE_NAME', 'Meridiano LVBP Blog');` | `define('OG_SITE_NAME', 'Mi Sitio Web');` | Nombre del sitio en metadatos Open Graph |
| 2 | `config.php` | 8 | `define('SITE_DIR', '/home/udcwscico/public_html/udn_meridiano_com/');` | `define('SITE_DIR', '/ruta/a/tu/proyecto/');` | Directorio físico - debe personalizarse |
| 3 | `config.php` | 11 | `define('SITE_URL', 'https://www.meridiano.com/');` | `define('SITE_URL', 'https://tu-dominio.com/');` | URL base del proyecto |
| 4 | `config.php` | 17 | `define('SITE_AUTHOR_DEFAULT', 'Redacción Meridiano');` | `define('SITE_AUTHOR_DEFAULT', 'Redacción');` | Autor por defecto |
| 5 | `core_index/config/config.php` | 18 | `define('BASE_URL', 'https://www.meridiano.com');` | `define('BASE_URL', 'https://tu-dominio.com');` | URL base para IndexNow (SIN barra final) |
| 6 | `core_index/config/config.php` | 44 | `define('INDEXNOW_HOST', 'www.meridiano.com');` | `define('INDEXNOW_HOST', 'tu-dominio.com');` | Host para autenticación IndexNow (sin https://) |
| 7 | `core_index/config/config.php` | 53 | `define('GOOGLE_CREDENTIALS_PATH', ROOT_DIR . '/gestion/meridiano-mbb-4ba1b54b57a9.json');` | `define('GOOGLE_CREDENTIALS_PATH', ROOT_DIR . '/gestion/credentials.json');` | Ruta genérica a credenciales Google |

#### **B. Archivos de Datos de Ejemplo (Reemplazar URLs)**

| # | Archivo | Líneas | Texto Actual | Texto Propuesto | Justificación |
|---|---------|--------|--------------|-----------------|---------------|
| 8 | `core_index/data/urls.json` | 4, 12, 20 | `"loc": "https://www.meridiano.com/..."` | `"loc": "https://tu-dominio.com/..."` | URLs de ejemplo para testing |
| 9 | `core_index/data/urls.csv` | 2-4 | `https://www.meridiano.com/...` | `https://tu-dominio.com/...` | URLs de ejemplo en formato CSV |
| 10 | `sitemap.xml` | 4-1096 | `<loc>https://www.meridiano.com/...</loc>` (262 URLs) | **ELIMINAR ARCHIVO** o reemplazar todas las URLs | Sitemap legacy con URLs del proyecto original |

**Nota sobre sitemap.xml:** Este archivo contiene 262 URLs específicas del proyecto Meridiano. **Recomendación:** Eliminar el archivo completo, ya que se regenerará automáticamente al ejecutar el sistema con las nuevas URLs.

#### **C. Documentación - README.md Principal**

| # | Archivo | Línea | Texto Actual | Texto Propuesto | Justificación |
|---|---------|-------|--------------|-----------------|---------------|
| 11 | `README.md` | 7 | `Este proyecto surge de la transformación de un sistema monolítico de 389 archivos (blog Meridiano) a un template ligero...` | `Este proyecto es un template ligero y genérico de 39 archivos para generación de sitemaps...` | Eliminar referencia histórica al origen |
| 12 | `README.md` | 130 | `├── BPYT4_briefing.md # Definición y objetivos del proyecto` | `├── PROJECT_briefing.md # Definición y objetivos del proyecto` | Sustituir nombre código interno |
| 13 | `README.md` | 137-139 | Referencias a archivos con "BPYT4" | Reemplazar "BPYT4" por "CONVERSION" | Neutralizar nombre de código interno |
| 14 | `README.md` | 355, 360-362 | Enlaces a documentos con nombres "BPYT4_" y "MERIDIANO" | Actualizar nombres de archivos referenciados | Mantener consistencia |

#### **D. Documentación Técnica - core_index/README.md**

| # | Archivo | Línea | Texto Actual | Texto Propuesto | Justificación |
|---|---------|-------|--------------|-----------------|---------------|
| 15 | `core_index/README.md` | 1 | `# Sistema Modular de Sitemap e Indexación BPYT4` | `# Sistema Modular de Sitemap e Indexación` | Eliminar código interno |
| 16 | `core_index/README.md` | 61, 82 | URLs `https://www.meridiano.com/core_index/...` | `https://tu-dominio.com/core_index/...` | URLs de ejemplo |
| 17 | `core_index/README.md` | 168-169 | `Fase 5.7: Eliminación Meridiano` | `Fase 5.7: Limpieza de Archivos Legacy` | Neutralizar referencia |
| 18 | `core_index/README.md` | 176 | `docs/conversion/BPYT4_PLAN_TRABAJO.md` | `docs/conversion/PLAN_CONVERSION.md` | Nombre genérico |
| 19 | `core_index/README.md` | 193 | `**Proyecto:** BPYT4 - Plantilla de Sitemap e Indexación` | `**Proyecto:** SSEN - Simple Search Engine Notification` | Nombre oficial del proyecto |

#### **E. Archivos PHP - Comentarios de Cabecera**

| # | Archivo | Línea | Texto Actual | Texto Propuesto | Justificación |
|---|---------|-------|--------------|-----------------|---------------|
| 20 | `core_index/sitemap_generator.php` | 5 | `@package BPYT4` | `@package SSEN` | Nombre del paquete en PHPDoc |
| 21 | `core_index/config/config.php` | 3, 5 | `Configuración Centralizada del Sistema BPYT4` / `@package BPYT4` | `Configuración Centralizada del Sistema SSEN` / `@package SSEN` | Consistencia en documentación |
| 22 | `core_index/sitemap_form_url.php` | 5 | `@package BPYT4` | `@package SSEN` | PHPDoc |
| 23 | `core_index/indexnow_client.php` | 5 | `@package BPYT4` | `@package SSEN` | PHPDoc |
| 24 | `core_index/google_indexing_auth.php` | 8 | `@package BPYT4` | `@package SSEN` | PHPDoc |
| 25 | `core_index/google_indexing_client.php` | 5 | `@package BPYT4` | `@package SSEN` | PHPDoc |
| 26 | `core_index/logger.php` | 5 | `@package BPYT4` | `@package SSEN` | PHPDoc |
| 27 | `core_index/indexnow_auth.php` | 5 | `@package BPYT4` | `@package SSEN` | PHPDoc |
| 28 | `core_index/sitemap_diff.php` | 5 | `@package BPYT4` | `@package SSEN` | PHPDoc |

#### **F. Interfaz Web - result_sitemap.php**

| # | Archivo | Línea | Texto Actual | Texto Propuesto | Justificación |
|---|---------|-------|--------------|-----------------|---------------|
| 29 | `gestion/result_sitemap.php` | 251 | `Sistema BPYT4 - Generación de sitemap...` | `Sistema SSEN - Generación de sitemap...` | Footer visible al usuario |

#### **G. Archivo de Autenticación Google**

| # | Archivo | Línea | Texto Actual | Texto Propuesto | Justificación |
|---|---------|-------|--------------|-----------------|---------------|
| 30 | `gestion/indexing_api_auth.php` | 24 | `$serviceAccountPath = __DIR__ . '/meridiano-mbb-4ba1b54b57a9.json';` | `$serviceAccountPath = __DIR__ . '/credentials.json';` | Ruta genérica a credenciales |

#### **H. .gitignore**

| # | Archivo | Línea | Texto Actual | Texto Propuesto | Justificación |
|---|---------|-------|--------------|-----------------|---------------|
| 31 | `.gitignore` | 3 | `/gestion/meridiano-mbb-4ba1b54b57a9.json` | `/gestion/credentials.json` | Excluir credenciales genéricas |

---

### 1.2. Referencias en Documentación de Manuales

**Nota:** Los manuales en `/manuales/` ya utilizan placeholders genéricos (`tu-dominio.com`, `ejemplo.com`), por lo que NO requieren cambios en este aspecto. Sin embargo, contienen referencias a documentos con nombres "BPYT4" y "MERIDIANO":

| # | Archivo | Línea | Texto Actual | Texto Propuesto | Justificación |
|---|---------|-------|--------------|-----------------|---------------|
| 32 | `manuales/04 Mantenimiento_Referencias.md` | 915 | `BPYT4_briefing.md` | `PROJECT_briefing.md` | Nombre genérico |
| 33 | `manuales/04 Mantenimiento_Referencias.md` | 923-925 | Referencias a archivos `BPYT4_` y `MERIDIANO` | Actualizar nombres | Consistencia |

---

### 1.3. Archivos de Documentación para Renombrar

| # | Archivo Actual | Archivo Propuesto | Justificación |
|---|----------------|-------------------|---------------|
| 34 | `docs/PLAN_DOCUMENTACION_FINAL_BPYT4.md` | `docs/PLAN_DOCUMENTACION_FINAL.md` | Eliminar código interno |
| 35 | Referencia en README a: `docs/conversion/LISTA_ELIMINACION_MERIDIANO.md` | Este archivo ya NO existe (movido a `/docs/borrar/`) | Actualizar referencia o eliminar mención |

**Nota:** Los archivos en `docs/borrar/` mantienen sus nombres históricos intencionalmente, ya que están en carpeta de archivo.

---

## 2. ACTUALIZACIÓN DE DOMINIOS EN RUTAS Y DIRECCIONES DE RED

### 2.1. Dominio "meridiano.com" → "tu-dominio.com"

Todos los cambios listados en la **Sección 1.1 (ítems 1-10)** ya incluyen la actualización de dominio.

### 2.2. Verificación Adicional en Documentación

Los manuales técnicos ya utilizan correctamente los placeholders:
- ✅ `tu-dominio.com` - Para ejemplos de configuración del usuario
- ✅ `ejemplo.com` - Para ejemplos ilustrativos genéricos

**No requieren cambios adicionales en este aspecto.**

---

## 3. RESUMEN EJECUTIVO

### 3.1. Archivos que Requieren Modificación (Críticos)

| Prioridad | Cantidad | Tipo | Justificación |
|-----------|----------|------|---------------|
| **CRÍTICA** | 7 | Archivos de configuración PHP | Afectan funcionamiento del sistema |
| **ALTA** | 3 | Archivos de datos (urls.json, urls.csv, indexing_api_auth.php) | Contienen URLs y rutas de ejemplo |
| **MEDIA** | 9 | Comentarios PHPDoc en módulos | Consistencia de documentación técnica |
| **BAJA** | 6 | Archivos README y documentación | Limpieza de referencias históricas |

### 3.2. Archivo Especial: sitemap.xml

**Recomendación:** **ELIMINAR** el archivo `sitemap.xml` de la raíz del proyecto.
- Contiene 262 URLs específicas del proyecto Meridiano
- Se regenerará automáticamente al ejecutar el sistema con los datos del nuevo usuario
- No tiene valor como plantilla

### 3.3. Total de Cambios Propuestos

- **35 referencias** identificadas para modificación/eliminación
- **262 URLs** en sitemap.xml (recomendación: eliminar archivo completo)
- **2 archivos** para renombrar (eliminar "BPYT4" del nombre)

---

## 4. PLAN DE EJECUCIÓN RECOMENDADO (Para el Usuario)

### Fase 1: Configuración Crítica (Obligatoria antes de usar)
1. Editar `config.php` (líneas 5, 8, 11, 17)
2. Editar `core_index/config/config.php` (líneas 18, 44, 53)
3. Renombrar archivo de credenciales Google a `credentials.json` genérico
4. Actualizar `.gitignore` (línea 3)

### Fase 2: Datos de Ejemplo
5. Reemplazar URLs en `core_index/data/urls.json` o eliminar y crear nuevos
6. Reemplazar URLs en `core_index/data/urls.csv` o eliminar y crear nuevos
7. **ELIMINAR** `sitemap.xml` de la raíz (se regenerará)
8. Actualizar `gestion/indexing_api_auth.php` (línea 24)

### Fase 3: Limpieza de Código (Opcional pero recomendada)
9. Actualizar comentarios `@package` en 8 archivos PHP
10. Actualizar footer en `gestion/result_sitemap.php`

### Fase 4: Documentación (Cosmética)
11. Actualizar referencias en `README.md`
12. Actualizar referencias en `core_index/README.md`
13. Renombrar archivos de documentación con "BPYT4" en el nombre
14. Actualizar referencias cruzadas en manuales

---

## 5. ARCHIVOS QUE NO REQUIEREN CAMBIOS

✅ **Manuales técnicos** (`/manuales/`) - Ya utilizan placeholders genéricos  
✅ **Archivos en** `/docs/borrar/` - Mantienen nombres históricos intencionalmente  
✅ **Scripts de ejemplo** en documentación - Ya utilizan `ejemplo.com`  

---

## 6. NOTAS FINALES

### 6.1. Priorización
- **Fase 1 es OBLIGATORIA** - El sistema no funcionará sin estos cambios
- **Fases 2-3 son IMPORTANTES** - Eliminan rastros del proyecto origen
- **Fase 4 es OPCIONAL** - Mejora la presentación pero no afecta funcionalidad

### 6.2. Validación Post-Cambios
El usuario debe:
1. Verificar que `BASE_URL` en `core_index/config/config.php` **NO** termina en `/`
2. Verificar que `INDEXNOW_HOST` **NO** incluye `https://`
3. Probar generación de sitemap con sus propias URLs
4. Verificar acceso a `{dominio}/{clave-indexnow}.txt`

### 6.3. Archivo credentials.json
El archivo `meridiano-mbb-4ba1b54b57a9.json` mencionado en el código **NO existe** en el repositorio (está en .gitignore). El usuario debe:
- Generar sus propias credenciales en Google Cloud Console
- Guardarlas como `credentials.json` (nombre genérico)
- El código ya busca la ruta correcta si se actualiza según este plan

---

## 7. CHECKLIST DE IMPLEMENTACIÓN

### ✅ Fase 1: Configuración Crítica
- [ ] `config.php` - Línea 5: OG_SITE_NAME
- [ ] `config.php` - Línea 8: SITE_DIR
- [ ] `config.php` - Línea 11: SITE_URL
- [ ] `config.php` - Línea 17: SITE_AUTHOR_DEFAULT
- [ ] `core_index/config/config.php` - Línea 18: BASE_URL
- [ ] `core_index/config/config.php` - Línea 44: INDEXNOW_HOST
- [ ] `core_index/config/config.php` - Línea 53: GOOGLE_CREDENTIALS_PATH
- [ ] `.gitignore` - Línea 3: Ruta credenciales

### ✅ Fase 2: Datos de Ejemplo
- [ ] `core_index/data/urls.json` - Reemplazar URLs
- [ ] `core_index/data/urls.csv` - Reemplazar URLs
- [ ] `sitemap.xml` - ELIMINAR archivo
- [ ] `gestion/indexing_api_auth.php` - Línea 24: Ruta credenciales

### ✅ Fase 3: Limpieza de Código
- [ ] `core_index/sitemap_generator.php` - @package
- [ ] `core_index/config/config.php` - @package y descripción
- [ ] `core_index/sitemap_form_url.php` - @package
- [ ] `core_index/indexnow_client.php` - @package
- [ ] `core_index/google_indexing_auth.php` - @package
- [ ] `core_index/google_indexing_client.php` - @package
- [ ] `core_index/logger.php` - @package
- [ ] `core_index/indexnow_auth.php` - @package
- [ ] `core_index/sitemap_diff.php` - @package
- [ ] `gestion/result_sitemap.php` - Footer

### ✅ Fase 4: Documentación
- [ ] `README.md` - Línea 7: Descripción proyecto
- [ ] `README.md` - Líneas 130, 137-139: Referencias BPYT4
- [ ] `README.md` - Líneas 355, 360-362: Enlaces documentación
- [ ] `core_index/README.md` - Línea 1: Título
- [ ] `core_index/README.md` - Líneas 61, 82: URLs ejemplo
- [ ] `core_index/README.md` - Líneas 168-169, 176, 193: Referencias
- [ ] `manuales/04 Mantenimiento_Referencias.md` - Líneas 915, 923-925
- [ ] `docs/PLAN_DOCUMENTACION_FINAL_BPYT4.md` - Renombrar archivo

---

**Fin del Plan de Actuación**
