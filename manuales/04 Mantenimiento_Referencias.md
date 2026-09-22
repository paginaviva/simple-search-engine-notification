# Manual de Configuración SSEN - Parte 4
## Mantenimiento y Referencias

**Subfase:** 3.4  
**Capítulos:** 7-8 (Mantenimiento + Referencia Rápida) + Apéndices  
**Fecha:** 10 de enero de 2026  
**Proyecto:** Simple Search Engine Notification (SSEN)

---

## Capítulo 7: Mantenimiento y Mejores Prácticas

### 7.1 Rotación de Logs

Los archivos de log crecen continuamente con cada ejecución. La rotación previene el consumo excesivo de espacio en disco.

#### Problema

Sin rotación, los logs pueden:
- Consumir gigabytes de espacio
- Ralentizar operaciones de lectura
- Dificultar el análisis de eventos recientes
- Causar problemas de rendimiento

#### Estrategia de Rotación Recomendada

**Frecuencia:** Mensual  
**Retención:** 6 meses  
**Método:** Compresión con gzip

#### Script de Rotación

**Crear script:**
```bash
nano rotate_ssen_logs.sh
```

**Contenido:**
```bash
#!/bin/bash

##############################################
# Script de Rotación de Logs - SSEN
# Ejecutar mensualmente vía cron
##############################################

# Configuración
PROJECT_DIR="/var/www/html/simple-search-engine-notification"
PRIVATE_DIR="$PROJECT_DIR/../ssen-private"
LOGS_DIR="$PRIVATE_DIR/logs"
ARCHIVE_DIR="$LOGS_DIR/archive"
RETENTION_DAYS=180  # 6 meses

# Crear directorio de archivo si no existe
mkdir -p "$ARCHIVE_DIR"

# Fecha actual para nombres de archivo
TIMESTAMP=$(date +%Y%m%d_%H%M%S)
YEAR_MONTH=$(date +%Y%m)

echo "=========================================="
echo "Rotación de Logs SSEN - $(date)"
echo "=========================================="

# Función para rotar un log
rotate_log() {
    local log_file=$1
    local log_name=$(basename "$log_file" .log)
    
    if [ -f "$log_file" ]; then
        # Obtener tamaño del archivo
        local size=$(du -h "$log_file" | cut -f1)
        echo "Procesando: $log_name ($size)"
        
        # Copiar con timestamp
        cp "$log_file" "$ARCHIVE_DIR/${log_name}_${TIMESTAMP}.txt"
        
        # Comprimir
        gzip "$ARCHIVE_DIR/${log_name}_${TIMESTAMP}.txt"
        
        # Limpiar log actual (mantener encabezado si existe)
        echo "# Log rotado el $(date)" > "$log_file"
        
        echo "  ✓ Archivado: ${log_name}_${TIMESTAMP}.txt.gz"
    else
        echo "  ⚠ No existe: $log_file"
    fi
}

# Rotar el registro principal
rotate_log "$LOGS_DIR/indexing_api_sitemap.log"

# Eliminar archivos antiguos (más de RETENTION_DAYS)
echo ""
echo "Limpiando archivos antiguos (>${RETENTION_DAYS} días)..."
OLD_FILES=$(find "$ARCHIVE_DIR" -name "*.txt.gz" -mtime +$RETENTION_DAYS)

if [ -n "$OLD_FILES" ]; then
    echo "$OLD_FILES" | while read file; do
        echo "  Eliminando: $(basename "$file")"
        rm "$file"
    done
else
    echo "  No hay archivos antiguos para eliminar"
fi

# Estadísticas
echo ""
echo "=========================================="
echo "Resumen:"
echo "  Logs rotados: $(ls -1 "$ARCHIVE_DIR"/*_${TIMESTAMP}.txt.gz 2>/dev/null | wc -l)"
echo "  Archivos en archivo: $(ls -1 "$ARCHIVE_DIR"/*.txt.gz 2>/dev/null | wc -l)"
echo "  Espacio usado: $(du -sh "$ARCHIVE_DIR" | cut -f1)"
echo "=========================================="
```

**Hacer ejecutable:**
```bash
chmod +x rotate_ssen_logs.sh
```

**Ejecutar manualmente:**
```bash
./rotate_ssen_logs.sh
```

**Salida esperada:**
```
==========================================
Rotación de Logs SSEN - vie 10 ene 2026 15:00:00
==========================================
Procesando: indexing_api_sitemap (2.3M)
  ✓ Archivado: indexing_api_sitemap_20260110_150000.txt.gz

Limpiando archivos antiguos (>180 días)...
  Eliminando: indexing_api_sitemap_20250710_120000.txt.gz

==========================================
Resumen:
  Logs rotados: 1
  Archivos en archivo: 12
  Espacio usado: 15M
==========================================
```

#### Automatizar con Cron

**Ejecutar el primer día de cada mes a las 2:00 AM:**
```bash
crontab -e
```

**Agregar línea:**
```
0 2 1 * * /ruta/completa/rotate_ssen_logs.sh >> /var/log/ssen_rotation.log 2>&1
```

#### Rotación Alternativa: Logrotate

**Para sistemas que usan logrotate:**

**Crear configuración:**
```bash
sudo nano /etc/logrotate.d/ssen
```

**Contenido:**
```
/var/www/html/ssen-private/logs/*.log {
    monthly
    rotate 6
    compress
    delaycompress
    missingok
    notifempty
    create 0600 USUARIO_PHP USUARIO_PHP
    sharedscripts
    postrotate
        # Opcional: reiniciar servicio si es necesario
    endscript
}
```

**Nota:** Sustituya `USUARIO_PHP` por el usuario que ejecuta PHP (en cPanel/LiteSpeed suele ser el usuario del sitio o `www-data`; en Hestia, el propio usuario del sitio). El directorio privado queda fuera del documento raíz.

**Probar configuración:**
```bash
sudo logrotate -d /etc/logrotate.d/ssen
```

**Forzar rotación inmediata (prueba):**
```bash
sudo logrotate -f /etc/logrotate.d/ssen
```

### 7.2 Backup de Credenciales

Las credenciales son críticas para el funcionamiento del sistema. Pérdida de estos archivos requiere reconfiguración completa.

#### Archivos a Respaldar

1. **core_index/config/indexnow_key.txt** (obligatorio)
2. **../ssen-private/.htpasswd** (archivo de usuarios de la interfaz web; fuera del documento raíz)
3. **../ssen-private/credentials.json** (si usas Google; directorio privado fuera del documento raíz)
4. **core_index/config/config.php** (configuración base)

#### Estrategia de Backup Seguro

**⚠️ IMPORTANTE:**
- **NO** almacenar credenciales en repositorios Git públicos
- **NO** enviar por email sin cifrar
- **NO** subir a servicios cloud públicos
- **SÍ** cifrar con contraseña fuerte
- **SÍ** almacenar en ubicación segura offline

#### Script de Backup Cifrado

**Crear script:**
```bash
nano backup_ssen_credentials.sh
```

**Contenido:**
```bash
#!/bin/bash

##############################################
# Backup Cifrado de Credenciales SSEN
# Requiere: GnuPG (gpg)
##############################################

PROJECT_DIR="/var/www/html/simple-search-engine-notification"
BACKUP_DIR="/backup/ssen"
TIMESTAMP=$(date +%Y%m%d_%H%M%S)
BACKUP_NAME="ssen_credentials_${TIMESTAMP}"

# Crear directorio de backup
mkdir -p "$BACKUP_DIR"

echo "=========================================="
echo "Backup de Credenciales SSEN"
echo "=========================================="

# Crear archivo temporal con las credenciales
TEMP_DIR=$(mktemp -d)
mkdir -p "$TEMP_DIR/ssen_backup"

# Copiar archivos
echo "Recopilando archivos..."
cp "$PROJECT_DIR/core_index/config/indexnow_key.txt" "$TEMP_DIR/ssen_backup/" 2>/dev/null && echo "  ✓ indexnow_key.txt"
cp "$PROJECT_DIR/../ssen-private/.htpasswd" "$TEMP_DIR/ssen_backup/" 2>/dev/null && echo "  ✓ .htpasswd"
cp "$PROJECT_DIR/../ssen-private/credentials.json" "$TEMP_DIR/ssen_backup/" 2>/dev/null && echo "  ✓ credentials.json"
cp "$PROJECT_DIR/core_index/config/config.php" "$TEMP_DIR/ssen_backup/" 2>/dev/null && echo "  ✓ config.php"

# Crear tarball
echo ""
echo "Creando archivo comprimido..."
cd "$TEMP_DIR"
tar -czf "${BACKUP_NAME}.tar.gz" ssen_backup/

# Cifrar con GPG
echo ""
echo "Cifrando backup..."
echo "Ingrese contraseña de cifrado (será solicitada):"
gpg --symmetric --cipher-algo AES256 "${BACKUP_NAME}.tar.gz"

# Mover a directorio de backup
mv "${BACKUP_NAME}.tar.gz.gpg" "$BACKUP_DIR/"

# Limpiar archivos temporales
cd /
rm -rf "$TEMP_DIR"

# Verificar
if [ -f "$BACKUP_DIR/${BACKUP_NAME}.tar.gz.gpg" ]; then
    SIZE=$(du -h "$BACKUP_DIR/${BACKUP_NAME}.tar.gz.gpg" | cut -f1)
    echo ""
    echo "=========================================="
    echo "✓ Backup completado exitosamente"
    echo "  Archivo: ${BACKUP_NAME}.tar.gz.gpg"
    echo "  Tamaño: $SIZE"
    echo "  Ubicación: $BACKUP_DIR"
    echo "=========================================="
else
    echo ""
    echo "✗ Error al crear backup"
    exit 1
fi

# Listar backups existentes
echo ""
echo "Backups existentes:"
ls -lh "$BACKUP_DIR"/ssen_credentials_*.tar.gz.gpg 2>/dev/null | awk '{print "  " $9 " (" $5 ")"}'

# Eliminar backups antiguos (más de 1 año)
echo ""
echo "Limpiando backups antiguos (>365 días)..."
find "$BACKUP_DIR" -name "ssen_credentials_*.tar.gz.gpg" -mtime +365 -delete
```

**Hacer ejecutable:**
```bash
chmod +x backup_ssen_credentials.sh
```

**Ejecutar backup:**
```bash
./backup_ssen_credentials.sh
```

**Ingresarás una contraseña para cifrar el archivo**

#### Restaurar Backup

**Crear script de restauración:**
```bash
nano restore_ssen_credentials.sh
```

**Contenido:**
```bash
#!/bin/bash

##############################################
# Restaurar Credenciales SSEN desde Backup
##############################################

if [ -z "$1" ]; then
    echo "Uso: $0 <archivo_backup.tar.gz.gpg>"
    echo ""
    echo "Backups disponibles:"
    ls -lh /backup/ssen/ssen_credentials_*.tar.gz.gpg 2>/dev/null | awk '{print "  " $9}'
    exit 1
fi

BACKUP_FILE="$1"
PROJECT_DIR="/var/www/html/simple-search-engine-notification"
TEMP_DIR=$(mktemp -d)

echo "=========================================="
echo "Restauración de Credenciales SSEN"
echo "=========================================="

# Descifrar
echo "Descifrando backup..."
echo "Ingrese contraseña de descifrado:"
gpg --decrypt "$BACKUP_FILE" > "$TEMP_DIR/backup.tar.gz"

if [ $? -ne 0 ]; then
    echo "✗ Error al descifrar"
    rm -rf "$TEMP_DIR"
    exit 1
fi

# Extraer
echo "Extrayendo archivos..."
cd "$TEMP_DIR"
tar -xzf backup.tar.gz

# Restaurar archivos
echo ""
echo "Restaurando archivos:"
cp ssen_backup/indexnow_key.txt "$PROJECT_DIR/core_index/config/" 2>/dev/null && echo "  ✓ indexnow_key.txt restaurado"
cp ssen_backup/.htpasswd "$PROJECT_DIR/../ssen-private/" 2>/dev/null && echo "  ✓ .htpasswd restaurado"
cp ssen_backup/credentials.json "$PROJECT_DIR/../ssen-private/" 2>/dev/null && echo "  ✓ credentials.json restaurado"
cp ssen_backup/config.php "$PROJECT_DIR/core_index/config/" 2>/dev/null && echo "  ✓ config.php restaurado"

# Establecer permisos
chmod 644 "$PROJECT_DIR/core_index/config/indexnow_key.txt" 2>/dev/null
chmod 600 "$PROJECT_DIR/../ssen-private/.htpasswd" 2>/dev/null
chmod 600 "$PROJECT_DIR/../ssen-private/credentials.json" 2>/dev/null
chmod 644 "$PROJECT_DIR/core_index/config/config.php" 2>/dev/null

# Limpiar
rm -rf "$TEMP_DIR"

echo ""
echo "=========================================="
echo "✓ Restauración completada"
echo "=========================================="
```

**Hacer ejecutable:**
```bash
chmod +x restore_ssen_credentials.sh
```

**Restaurar:**
```bash
./restore_ssen_credentials.sh /backup/ssen/ssen_credentials_20260110_150000.tar.gz.gpg
```

#### Automatizar Backup Mensual

```bash
crontab -e
```

**Agregar:**
```
0 1 1 * * /ruta/completa/backup_ssen_credentials.sh > /var/log/ssen_backup.log 2>&1
```

Ejecuta backup cifrado el primer día de cada mes a la 1:00 AM.

### 7.3 Monitoreo de Notificaciones

#### Script de Monitoreo

**Crear script:**
```bash
nano monitor_ssen.sh
```

**Contenido:**
```bash
#!/bin/bash

##############################################
# Monitor de Notificaciones SSEN
# Analiza logs y detecta problemas
##############################################

PROJECT_DIR="/var/www/html/simple-search-engine-notification"
PRIVATE_DIR="$PROJECT_DIR/../ssen-private"
LOGS_DIR="$PRIVATE_DIR/logs"
ALERT_EMAIL="admin@ejemplo.com"
ERROR_THRESHOLD=10  # Número de errores para enviar alerta

echo "=========================================="
echo "Monitor SSEN - $(date)"
echo "=========================================="

# Verificar logs de las últimas 24 horas
YESTERDAY=$(date -d '24 hours ago' +%Y-%m-%d)

# Función para analizar log
analyze_log() {
    local log_file=$1
    local log_name=$2
    
    if [ ! -f "$log_file" ]; then
        echo "⚠ $log_name: Log no encontrado"
        return
    fi
    
    # Contar notificaciones exitosas (HTTP 200 o 202)
    SUCCESS=$(grep "$YESTERDAY" "$log_file" | grep -cE "HTTP: (200|202)")
    
    # Contar errores (HTTP 4xx, 5xx)
    ERRORS=$(grep "$YESTERDAY" "$log_file" | grep -cE "HTTP: (40[0-9]|50[0-9])")
    
    # Contar total de operaciones
    TOTAL=$(grep -c "$YESTERDAY" "$log_file")
    
    echo ""
    echo "$log_name:"
    echo "  Total operaciones: $TOTAL"
    echo "  Exitosas (200): $SUCCESS"
    echo "  Errores (4xx/5xx): $ERRORS"
    
    if [ $ERRORS -gt $ERROR_THRESHOLD ]; then
        echo "  ⚠ ALERTA: Más de $ERROR_THRESHOLD errores detectados"
        
        # Enviar email de alerta
        echo "ALERTA: SSEN ha registrado $ERRORS errores en $log_name en las últimas 24 horas" | \
        mail -s "Alerta SSEN - Errores en $log_name" "$ALERT_EMAIL"
    fi
    
    # Mostrar últimos errores
    if [ $ERRORS -gt 0 ]; then
        echo "  Últimos 3 errores:"
        grep "$YESTERDAY" "$log_file" | grep -E "HTTP: (40[0-9]|50[0-9])" | tail -3 | while read line; do
            echo "    - $(echo $line | cut -d'|' -f3-)"
        done
    fi
}

# Analizar logs
analyze_log "$LOGS_DIR/indexing_api_sitemap.log" "Indexing API"

# Verificar espacio en disco
echo ""
echo "Espacio en disco:"
DISK_USAGE=$(df -h "$PROJECT_DIR" | tail -1 | awk '{print $5}' | sed 's/%//')
echo "  Uso del disco: ${DISK_USAGE}%"

if [ $DISK_USAGE -gt 90 ]; then
    echo "  ⚠ ALERTA: Espacio en disco crítico"
fi

# Verificar tamaño de logs
echo ""
echo "Tamaño de logs:"
du -h "$LOGS_DIR"/*.log 2>/dev/null | while read size file; do
    echo "  $(basename $file): $size"
done

echo ""
echo "=========================================="
echo "Monitor completado"
echo "=========================================="
```

**Hacer ejecutable:**
```bash
chmod +x monitor_ssen.sh
```

**Ejecutar diariamente:**
```bash
crontab -e
```

**Agregar:**
```
0 9 * * * /ruta/completa/monitor_ssen.sh > /var/log/ssen_monitor.log 2>&1
```

Ejecuta monitoreo cada día a las 9:00 AM.

### 7.4 Validación de Sitemap

#### Validación Manual

**Verificar formato XML:**
```bash
xmllint --noout sitemap.xml && echo "✓ XML válido" || echo "✗ XML inválido"
```

**Contar URLs:**
```bash
grep -c "<url>" sitemap.xml
```

**Verificar URLs duplicadas:**
```bash
grep "<loc>" sitemap.xml | sort | uniq -d
```

**Si hay salida, hay URLs duplicadas**

**Validar accesibilidad del sitemap:**
```bash
curl -I https://tu-dominio.com/sitemap.xml
```

**Debe responder HTTP 200**

#### Script de Validación Automática

**Crear script:**
```bash
nano validate_sitemap.sh
```

**Contenido:**
```bash
#!/bin/bash

SITEMAP="sitemap.xml"
BASE_URL="https://tu-dominio.com"

echo "Validando sitemap..."

# 1. Verificar que existe
[ -f "$SITEMAP" ] && echo "✓ Archivo existe" || { echo "✗ Archivo no existe"; exit 1; }

# 2. Validar XML
xmllint --noout "$SITEMAP" 2>/dev/null && echo "✓ XML válido" || echo "✗ XML inválido"

# 3. Contar URLs
URL_COUNT=$(grep -c "<url>" "$SITEMAP")
echo "✓ Total URLs: $URL_COUNT"

# 4. Verificar duplicados
DUPLICATES=$(grep "<loc>" "$SITEMAP" | sort | uniq -d | wc -l)
if [ $DUPLICATES -eq 0 ]; then
    echo "✓ Sin URLs duplicadas"
else
    echo "✗ $DUPLICATES URLs duplicadas encontradas"
fi

# 5. Verificar accesibilidad
HTTP_CODE=$(curl -s -o /dev/null -w "%{http_code}" "${BASE_URL}/sitemap.xml")
if [ "$HTTP_CODE" -eq 200 ]; then
    echo "✓ Sitemap accesible (HTTP $HTTP_CODE)"
else
    echo "✗ Sitemap no accesible (HTTP $HTTP_CODE)"
fi

# 6. Tamaño del archivo
SIZE=$(du -h "$SITEMAP" | cut -f1)
echo "✓ Tamaño: $SIZE"
```

**Ejecutar:**
```bash
bash validate_sitemap.sh
```

### 7.5 Actualización de URLs

#### Estrategia Recomendada

1. **Generar lista de URLs actualizada** (desde BD, archivos, etc.)
2. **Sobrescribir archivo de entrada** (urls.json o urls.csv)
3. **Ejecutar generador de sitemap**
4. **Sistema detecta cambios automáticamente**
5. **Notificaciones enviadas solo para cambios**

#### Flujo Completo Automatizado

**Crear script integrado:**
```bash
nano update_and_notify.sh
```

**Contenido:**
```bash
#!/bin/bash

##############################################
# Actualizar URLs y Notificar - SSEN
# Flujo completo: generar → notificar → validar
##############################################

PROJECT_DIR="/var/www/html/simple-search-engine-notification"
LOG_FILE="/var/log/ssen_update.log"

echo "=========================================="
echo "Actualización SSEN - $(date)"
echo "=========================================="

# Paso 1: Generar URLs desde base de datos
echo "Paso 1: Generando lista de URLs..."
php "$PROJECT_DIR/generate_urls_from_db.php"

if [ $? -ne 0 ]; then
    echo "✗ Error al generar URLs"
    exit 1
fi
echo "✓ URLs generadas"

# Paso 2: Ejecutar generador de sitemap
echo ""
echo "Paso 2: Generando sitemap y notificando..."
php "$PROJECT_DIR/core_index/sitemap_generator.php"

if [ $? -ne 0 ]; then
    echo "✗ Error al generar sitemap"
    exit 1
fi
echo "✓ Sitemap generado y notificaciones enviadas"

# Paso 3: Validar resultado
echo ""
echo "Paso 3: Validando sitemap..."
xmllint --noout "$PROJECT_DIR/sitemap.xml" 2>/dev/null

if [ $? -eq 0 ]; then
    URL_COUNT=$(grep -c "<url>" "$PROJECT_DIR/sitemap.xml")
    echo "✓ Sitemap válido con $URL_COUNT URLs"
else
    echo "✗ Sitemap inválido"
    exit 1
fi

# Paso 4: Verificar logs
echo ""
echo "Paso 4: Verificando notificaciones..."
SUCCESS_COUNT=$(tail -10 "$PROJECT_DIR/../ssen-private/logs/indexing_api_sitemap.log" | grep -c "Status: SUCCESS")

echo "  Notificaciones correctas: $SUCCESS_COUNT"

echo ""
echo "=========================================="
echo "✓ Actualización completada"
echo "=========================================="
```

**Automatizar cada 6 horas:**
```bash
crontab -e
```

**Agregar:**
```
0 */6 * * * /ruta/completa/update_and_notify.sh >> /var/log/ssen_update.log 2>&1
```

---

## Capítulo 8: Referencia Rápida

### Comandos Esenciales

#### Validación y Diagnóstico

```bash
# Validar sintaxis de todos los módulos PHP
find core_index -name "*.php" -exec php -l {} \;

# Validar configuración y entorno
php core_index/tools/check_config.php

# Validar formato JSON
php -r "json_decode(file_get_contents('core_index/data/urls.json')); echo json_last_error() === JSON_ERROR_NONE ? 'OK' : 'ERROR';"

# Validar XML del sitemap
xmllint --noout sitemap.xml && echo "XML válido"
```

#### Ejecución

```bash
# Ejecutar generador
php core_index/sitemap_generator.php

# Ejecutar generador con el archivo CSV
php core_index/sitemap_generator.php --source=csv

# Ejecutar en segundo plano
php core_index/sitemap_generator.php > /tmp/ssen.log 2>&1 &

# Ejecutar con timeout de 5 minutos
timeout 300 php core_index/sitemap_generator.php
```

#### Análisis de Logs

```bash
# Ver últimas 20 notificaciones
tail -20 ../ssen-private/logs/indexing_api_sitemap.log

# Buscar errores
grep -E "Status: (FAILED|ERROR)" ../ssen-private/logs/indexing_api_sitemap.log

# Contar notificaciones exitosas hoy
grep "$(date +%Y-%m-%d)" ../ssen-private/logs/indexing_api_sitemap.log | grep -c "Status: SUCCESS"

# Ver errores únicos por código HTTP
grep -E "HTTP: (40[0-9]|50[0-9])" ../ssen-private/logs/indexing_api_sitemap.log | cut -d'|' -f4 | sort | uniq -c
```

#### Información del Sitemap

```bash
# Contar URLs
grep -c "<url>" sitemap.xml

# Listar todas las URLs
grep "<loc>" sitemap.xml | sed 's/.*<loc>\(.*\)<\/loc>.*/\1/'

# Verificar duplicados
grep "<loc>" sitemap.xml | sort | uniq -d

# Tamaño del sitemap
du -h sitemap.xml
```

#### Verificación de Credenciales

```bash
# Ver BASE_URL configurada
php -r "require 'core_index/config/config.php'; echo BASE_URL . PHP_EOL;"

# Ver clave IndexNow
cat core_index/config/indexnow_key.txt

# Verificar accesibilidad de clave IndexNow
KEY=$(cat core_index/config/indexnow_key.txt)
curl https://tu-dominio.com/${KEY}.txt

# Ver email de cuenta de servicio Google
php -r "require 'core_index/config/config.php'; echo json_decode(file_get_contents(GOOGLE_CREDENTIALS_PATH))->client_email . PHP_EOL;"
```

### URLs de Acceso

```bash
# Interfaz web con JSON
https://tu-dominio.com/core_index/sitemap_form_url.php?source=json

# Interfaz web con CSV
https://tu-dominio.com/core_index/sitemap_form_url.php?source=csv

# Sitemap generado
https://tu-dominio.com/sitemap.xml

# Archivo de clave IndexNow
https://tu-dominio.com/{clave}.txt
```

### Archivos Críticos

| Archivo | Propósito | ¿Editar? | ¿Backup? |
|---------|-----------|----------|----------|
| `config/config.php` | Configuración base del sistema | ✅ Sí (BASE_URL, INDEXNOW_HOST) | ✅ Sí |
| `config/indexnow_key.txt` | Clave de autenticación IndexNow | ❌ No (generada) | ✅ Sí |
| `../ssen-private/credentials.json` | Credenciales Google Indexing API | ❌ No (descargada) | ✅ Sí |
| `../ssen-private/.htpasswd` | Archivo de usuarios de la interfaz web | ❌ No | ✅ Sí |
| `data/urls.json` | Lista de URLs (formato JSON) | ✅ Sí (manual o script) | ❌ No |
| `data/urls.csv` | Lista de URLs (formato CSV) | ✅ Sí (manual o script) | ❌ No |
| `sitemap.xml` | Sitemap generado (actual, en la raíz) | ❌ No (generado) | ❌ No |
| `sitemap.old.xml` | Sitemap previo (comparación, en la raíz) | ❌ No (automático) | ❌ No |
| `../ssen-private/logs/indexing_api_sitemap.log` | Registro de notificaciones (fuera del documento raíz) | ❌ No (automático) | ❌ No |

### Códigos de Estado HTTP

#### IndexNow API

| Código | Significado | Acción |
|--------|-------------|--------|
| **200** | Success | ✅ URLs procesadas correctamente |
| **202** | Accepted | ✅ URLs en cola de procesamiento |
| **400** | Bad Request | ❌ Verificar formato de URLs |
| **403** | Forbidden | ❌ Clave inválida, verificar archivo en raíz |
| **422** | Unprocessable Entity | ❌ URLs inválidas o duplicadas |
| **429** | Too Many Requests | ⚠️ Reducir frecuencia de notificaciones |
| **500** | Internal Server Error | ⚠️ Error del servidor, reintentar más tarde |

#### Google Indexing API

| Código | Significado | Acción |
|--------|-------------|--------|
| **200** | Success | ✅ URL notificada correctamente |
| **400** | Bad Request | ❌ Formato de solicitud inválido |
| **401** | Unauthorized | ❌ Token inválido, verificar credenciales |
| **403** | Forbidden | ❌ Sin permisos en Search Console |
| **429** | Too Many Requests | ⚠️ Límite de cuota excedido |
| **500** | Internal Server Error | ⚠️ Error de Google, reintentar más tarde |
| **503** | Service Unavailable | ⚠️ Servicio temporalmente no disponible |

### Frecuencias Recomendadas por Tipo de Sitio

| Tipo de Sitio | Frecuencia | Expresión Cron | Justificación |
|---------------|-----------|----------------|---------------|
| **Blog personal** | 1 vez/día | `0 3 * * *` | Publicaciones diarias o menos frecuentes |
| **Blog corporativo** | Cada 6 horas | `0 */6 * * *` | Múltiples publicaciones diarias |
| **Portal de noticias** | Cada hora | `0 * * * *` | Contenido en tiempo real |
| **E-commerce** | Cada 2 horas | `0 */2 * * *` | Inventario dinámico |
| **Sitio corporativo** | 1 vez/día | `0 3 * * *` | Cambios esporádicos |
| **Documentación** | 1 vez/semana | `0 8 * * 1` | Actualizaciones semanales |
| **Sitio estático** | 1 vez/mes | `0 3 1 * *` | Cambios muy raros |

### Límites de APIs

#### IndexNow

| Límite | Valor | Notas |
|--------|-------|-------|
| **URLs por solicitud** | 10.000 | El cliente trocea en lotes si se supera |
| **Frecuencia** | Sin límite documentado | No hay restricciones oficiales |
| **Tamaño de clave** | 8 a 128 caracteres | Alfanuméricos o guion |
| **Formato de notificación** | JSON | Estructura definida en el protocolo |

#### Google Indexing API

| Límite | Valor | Notas |
|--------|-------|-------|
| **Cuota diaria** | 200 | Envíos por día y proyecto |
| **Retardo recomendado** | 100 ms | Entre solicitudes individuales (`GOOGLE_REQUEST_DELAY_MS`) |
| **Duración del token** | 3600 s (1 hora) | Renovación automática |
| **Tipos de notificación** | URL_UPDATED, URL_DELETED | Solo para páginas con `JobPosting` o `BroadcastEvent`; `URL_DELETED` exige 404 o 410 |

**⚠️ IMPORTANTE:**
- Google Indexing API solo admite páginas con datos estructurados `JobPosting` o `BroadcastEvent`
- Implementar el retardo entre solicitudes y supervisar la cuota diaria
- La cuota es por proyecto de Google Cloud, no por cuenta de servicio

---

## Apéndices

### Apéndice A: Estructura Completa del Proyecto

```
simple-search-engine-notification/
│
├── core_index/                          # Sistema modular SSEN (núcleo)
│   │
│   ├── config/                          # Configuración centralizada
│   │   ├── config.php                   # Constantes del sistema, rutas, APIs
│   │   └── indexnow_key.txt             # Clave IndexNow (generada, no versionada)
│   │
│   ├── data/                            # Archivos de entrada
│   │   ├── urls.json                    # Lista de URLs (formato JSON)
│   │   └── urls.csv                     # Lista de URLs (formato CSV)
│   │
│   ├── tools/                           # Herramientas de línea de comandos
│   │   ├── check_config.php             # Comprobación de configuración y entorno
│   │   └── generate_indexnow_key.php    # Generación y rotación de la clave IndexNow
│   │
│   ├── logger.php                       # [F2A] Sistema de registro
│   ├── indexnow_auth.php                # [F2A] Autenticación IndexNow
│   ├── sitemap_diff.php                 # [F2A] Comparación de sitemaps
│   ├── urls_loader.php                  # [F2A] Carga y validación de entradas JSON y CSV
│   │
│   ├── google_indexing_auth.php         # [F2B] Autenticación Google
│   ├── indexnow_client.php              # [F2B] Cliente HTTP IndexNow
│   ├── google_indexing_client.php       # [F2B] Cliente HTTP Google
│   │
│   ├── sitemap_generator.php            # [F2C] Orquestador principal
│   ├── sitemap_form_url.php             # [F2C] Interfaz web (autenticación de la aplicación)
│   ├── auth_guard.php                   # [F2C] Autenticación de la aplicación (Apache, LiteSpeed y nginx)
│   ├── .htaccess                        # Denegación adicional en Apache/LiteSpeed (opcional)
│   └── README.md                        # Documentación técnica de los módulos
│
├── docs/                                # Documentación del proyecto
│   ├── INFORME_AUDITORIA_SSEN_2026-09-21.md  # Informe de auditoría
│   ├── instruccion-auditoria-agnostica.md    # Instrucción de auditoría
│   ├── plan.md                          # Plan histórico de limpieza de referencias
│   └── PLAN_DOCUMENTACION_FINAL.md      # Plan histórico de documentación
│
├── manuales/                            # Manuales de configuración
│   ├── 00 Conocimientos+Referencias_Indexing API.md  # Referencias de APIs
│   ├── 01 Fundamentos_Preparacion_Inicial.md  # Manual Parte 1
│   ├── 02 Configuracion_Datos_APIs.md  # Manual Parte 2
│   ├── 03 Operacion_Diagnostico.md     # Manual Parte 3
│   └── 04 Mantenimiento_Referencias.md # Manual Parte 4 (este archivo)
│
├── gestion/                             # Legado local (no se publica; en .gitignore)
│
├── README.md                            # Documentación principal
├── .gitignore                           # Exclusiones del control de versiones
│
├── sitemap.xml                          # Sitemap generado (en la raíz, no versionado)
├── sitemap.old.xml                      # Sitemap previo (en la raíz, no versionado)
└── {clave}.txt                          # Archivo público de verificación de IndexNow (en la raíz)

Directorio privado (fuera del documento raíz):
../ssen-private/
├── .htpasswd                            # Usuarios de la interfaz web (AUTH_USERS_FILE)
├── credentials.json                     # Credenciales de Google (no versionadas)
└── logs/
    └── indexing_api_sitemap.log         # Registro de notificaciones

Total aproximado:
- 13 módulos PHP en core_index (incluidas las 2 herramientas)
- 4 archivos de documentación en docs/
- 5 manuales en manuales/
- 2 archivos de entrada de ejemplo en core_index/data/
```

Los scripts auxiliares descritos en los capítulos 7 y 8 se crean al seguir este manual y no forman parte del repositorio.

### Apéndice B: Formato Completo de credentials.json

**Archivo generado por Google Cloud Console al crear cuenta de servicio:**

```json
{
  "type": "service_account",
  "project_id": "proyecto-id-123456",
  "private_key_id": "abc123def456ghi789jkl012mno345pqr678stu901",
  "private_key": "-----BEGIN PRIVATE KEY-----\nMIIEvQIBADANBgkqhkiG9w0BAQEFAASCBKcwggSjAgEAAoIBAQC7VJTUt9Us8cKj\nMzEfYyjiWA4R4/M2bS1+fWIcPm15j9F3zzQ9KvlW5vYz0j3l7qO3K8w6pL/5kP+d\n... [múltiples líneas de clave privada] ...\nEPfQlF9hC7tBhJeL8YVkKl3UmB5z4OwYdAaV0zxQJY+xLm3aCw==\n-----END PRIVATE KEY-----\n",
  "client_email": "indexing-service-account@proyecto-id-123456.iam.gserviceaccount.com",
  "client_id": "123456789012345678901",
  "auth_uri": "https://accounts.google.com/o/oauth2/auth",
  "token_uri": "https://oauth2.googleapis.com/token",
  "auth_provider_x509_cert_url": "https://www.googleapis.com/oauth2/v1/certs",
  "client_x509_cert_url": "https://www.googleapis.com/robot/v1/metadata/x509/indexing-service-account%40proyecto-id-123456.iam.gserviceaccount.com",
  "universe_domain": "googleapis.com"
}
```

**Campos críticos:**
- `type`: Siempre "service_account"
- `project_id`: ID del proyecto en Google Cloud
- `private_key`: Clave privada RSA (mantener confidencial)
- `client_email`: Email único de la cuenta de servicio
- `token_uri`: Endpoint para obtener tokens de acceso

**⚠️ SEGURIDAD:**
- **NUNCA** compartir este archivo públicamente
- **NUNCA** subir a repositorios Git públicos
- Guardarlo en el directorio privado fuera del documento raíz y establecer permisos: `chmod 600 ../ssen-private/credentials.json`
- Mantener backups cifrados

### Apéndice C: Plantilla de Ejemplo urls.json Completo

**Archivo de ejemplo con 20 URLs representativas:**

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
      "loc": "https://ejemplo.com/soluciones",
      "lastmod": "2026-01-08",
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
      "loc": "https://ejemplo.com/blog/tecnologia",
      "lastmod": "2026-01-09",
      "changefreq": "weekly",
      "priority": 0.8
    },
    {
      "loc": "https://ejemplo.com/blog/noticias",
      "lastmod": "2026-01-10",
      "changefreq": "daily",
      "priority": 0.8
    },
    {
      "loc": "https://ejemplo.com/blog/articulo-nuevo-producto",
      "lastmod": "2026-01-10",
      "changefreq": "monthly",
      "priority": 0.7
    },
    {
      "loc": "https://ejemplo.com/blog/guia-completa-seo",
      "lastmod": "2026-01-08",
      "changefreq": "monthly",
      "priority": 0.7
    },
    {
      "loc": "https://ejemplo.com/blog/mejores-practicas-2026",
      "lastmod": "2026-01-05",
      "changefreq": "monthly",
      "priority": 0.7
    },
    {
      "loc": "https://ejemplo.com/productos/producto-a",
      "lastmod": "2026-01-07",
      "changefreq": "weekly",
      "priority": 0.7
    },
    {
      "loc": "https://ejemplo.com/productos/producto-b",
      "lastmod": "2026-01-06",
      "changefreq": "weekly",
      "priority": 0.7
    },
    {
      "loc": "https://ejemplo.com/productos/producto-c",
      "lastmod": "2026-01-05",
      "changefreq": "weekly",
      "priority": 0.7
    },
    {
      "loc": "https://ejemplo.com/sobre-nosotros",
      "lastmod": "2026-01-01",
      "changefreq": "monthly",
      "priority": 0.6
    },
    {
      "loc": "https://ejemplo.com/equipo",
      "lastmod": "2025-12-15",
      "changefreq": "monthly",
      "priority": 0.6
    },
    {
      "loc": "https://ejemplo.com/testimonios",
      "lastmod": "2025-12-20",
      "changefreq": "monthly",
      "priority": 0.6
    },
    {
      "loc": "https://ejemplo.com/faq",
      "lastmod": "2025-12-10",
      "changefreq": "monthly",
      "priority": 0.5
    },
    {
      "loc": "https://ejemplo.com/contacto",
      "lastmod": "2025-11-01",
      "changefreq": "yearly",
      "priority": 0.5
    },
    {
      "loc": "https://ejemplo.com/politica-privacidad",
      "lastmod": "2025-06-01",
      "changefreq": "yearly",
      "priority": 0.4
    },
    {
      "loc": "https://ejemplo.com/terminos-servicio",
      "lastmod": "2025-06-01",
      "changefreq": "yearly",
      "priority": 0.4
    }
  ]
}
```

**Notas de implementación:**
- **Priority:** Descendente de 1.0 (más importante) a 0.4 (menos importante)
- **Changefreq:** Basado en frecuencia real de actualizaciones
- **Lastmod:** Formato ISO 8601 (YYYY-MM-DD)
- **URLs:** Todas con protocolo HTTPS
- **Estructura:** Objeto JSON con la clave `urls`, que contiene un array de entradas

### Apéndice D: Enlaces Útiles

#### Documentación Oficial de APIs

**IndexNow:**
- Sitio oficial: https://www.indexnow.org/
- Documentación: https://www.indexnow.org/documentation
- FAQ: https://www.indexnow.org/faq
- Especificación del protocolo: https://www.indexnow.org/documentation#protocol

**Google Indexing API:**
- Documentación oficial: https://developers.google.com/search/apis/indexing-api/v3/quickstart
- Referencia de API: https://developers.google.com/search/apis/indexing-api/v3/reference
- Guías: https://developers.google.com/search/apis/indexing-api/v3/using-api
- Límites y cuotas: https://developers.google.com/search/apis/indexing-api/v3/quota-pricing

**Google Cloud Console:**
- Console: https://console.cloud.google.com
- Gestión de APIs: https://console.cloud.google.com/apis/library
- Cuentas de servicio: https://console.cloud.google.com/iam-admin/serviceaccounts
- Credenciales: https://console.cloud.google.com/apis/credentials

**Google Search Console:**
- Console: https://search.google.com/search-console
- Usuarios y permisos: https://search.google.com/search-console/users
- Ayuda: https://support.google.com/webmasters

#### Protocolo Sitemap

- Especificación oficial: https://www.sitemaps.org/protocol.html
- Extensiones: https://www.sitemaps.org/protocol.html#extend
- Validador de Google: https://search.google.com/search-console/sitemaps

#### PHP y Extensiones

- PHP Documentation: https://www.php.net/manual/es/
- OpenSSL: https://www.php.net/manual/es/book.openssl.php
- cURL: https://www.php.net/manual/es/book.curl.php
- JSON: https://www.php.net/manual/es/book.json.php
- SimpleXML: https://www.php.net/manual/es/book.simplexml.php

#### Herramientas de Desarrollo

- GnuPG (cifrado): https://gnupg.org/
- xmllint (validación XML): http://xmlsoft.org/xmllint.html
- Cron: https://man7.org/linux/man-pages/man5/crontab.5.html
- Logrotate: https://linux.die.net/man/8/logrotate

#### Comunidad y Soporte

- GitHub Repository: https://github.com/paginaviva/simple-search-engine-notification
- Issues: https://github.com/paginaviva/simple-search-engine-notification/issues
- Documentación del proyecto: Ver directorio `/docs`

---

## Resumen de la Subfase 3.4

### ✅ Tareas Completadas

1. **Capítulo 7: Mantenimiento y Mejores Prácticas**
   - 7.1 Rotación de Logs (scripts completos)
   - 7.2 Backup de Credenciales (cifrado con GPG)
   - 7.3 Monitoreo de Notificaciones (alertas automáticas)
   - 7.4 Validación de Sitemap (verificación automática)
   - 7.5 Actualización de URLs (flujo integrado)

2. **Capítulo 8: Referencia Rápida**
   - Comandos esenciales (validación, ejecución, análisis)
   - URLs de acceso
   - Tabla de archivos críticos
   - Tablas de códigos HTTP
   - Frecuencias recomendadas
   - Límites de APIs

3. **Apéndices A-D**
   - Estructura completa del proyecto
   - Formato credentials.json
   - Plantilla urls.json completa
   - Enlaces útiles

### 📋 Scripts Proporcionados

- `rotate_ssen_logs.sh` - Rotación mensual automatizada
- `backup_ssen_credentials.sh` - Backup cifrado con GPG
- `restore_ssen_credentials.sh` - Restauración de credenciales
- `monitor_ssen.sh` - Monitoreo y alertas
- `validate_sitemap.sh` - Validación automática
- `update_and_notify.sh` - Flujo completo integrado

### 🎯 Herramientas de Referencia

- 6 tablas de referencia rápida
- 20+ comandos esenciales documentados
- 4 apéndices con información detallada
- 15+ enlaces a documentación oficial

---

## Resumen General del Manual Completo

### Estado Final de las 4 Subfases

| Subfase | Capítulos | Archivo | Estado | Líneas |
|---------|-----------|---------|--------|--------|
| **3.1** | 1-2 | Fundamentos_Preparacion_Inicial.md | ✅ Completado | ~230 |
| **3.2** | 3-4 | Configuracion_Datos_APIs.md | ✅ Completado | ~680 |
| **3.3** | 5-6 | Operacion_Diagnostico.md | ✅ Completado | ~720 |
| **3.4** | 7-8 + Apéndices | Mantenimiento_Referencias.md | ✅ Completado | ~850 |
| **TOTAL** | 8 capítulos + 4 apéndices | 4 archivos | ✅ Completo | ~2,480 |

### Contenido Completo del Manual

**Parte 1: Fundamentos y Preparación Inicial**
- Capítulo 1: Prerrequisitos
- Capítulo 2: Instalación

**Parte 2: Configuración de Datos y APIs**
- Capítulo 3: Configuración de Archivos de Entrada
- Capítulo 4: Configuración de APIs

**Parte 3: Operación y Diagnóstico**
- Capítulo 5: Ejecución del Sistema
- Capítulo 6: Solución de Problemas

**Parte 4: Mantenimiento y Referencias**
- Capítulo 7: Mantenimiento y Mejores Prácticas
- Capítulo 8: Referencia Rápida
- Apéndices A-D

### Recursos Totales Creados

- **Scripts bash:** 8 scripts completos de producción
- **Scripts PHP:** 8 scripts de generación y validación
- **Tablas de referencia:** 10 tablas detalladas
- **Ejemplos de código:** 50+ bloques de código funcional
- **Comandos documentados:** 40+ comandos con explicaciones

---

**Fin de Subfase 3.4 - Mantenimiento y Referencias**  
**Fin del Manual de Configuración SSEN**
