# Informe de auditoría de SSEN (Simple Search Engine Notification)

## 1. Metadatos

| Campo | Valor |
|---|---|
| Título | Informe de auditoría de SSEN (Simple Search Engine Notification) |
| Fecha | 2026-09-21 |
| Versión | 1.0 |
| Proyecto | SSEN (Simple Search Engine Notification) |
| Ruta base | `/home/yousuaario/Documentos/pyts-oac/desa-simple-search-engine-notification` |
| Instrucción rectora | `docs/instruccion-auditoria-agnostica.md`, versión 2.0; supuesto B |
| Sesión | `20260921-auditoria-ssen` |
| Alcance | Canónico `core_index/` (nueve módulos PHP y su README); legado `gestion/` (tres archivos) y `config.php` de la raíz en revisión de superficie; documentación (`README.md`, `AGENTS.md`, `docs/` y `manuales/`) |
| Focos | Calidad y patrones modulares y funcionales; seguridad y gestión de secretos; rendimiento y uso de recursos; facilidad de mantenimiento y claridad documental |
| Método | Revisión estática por CodeReviewer (Bloque 2) y TestEngineer (Bloque 3); validación de construcción por BuildAgent (Bloque 4) sin ejecución de comandos en su sesión; comprobaciones de entorno de solo lectura por el agente orquestador; coordinación con TaskManager (Bloque 5); informe de DocWriter (Bloque 6); regla ante fallos del apartado 7.2 aplicada; Bloque 7.1 omitido por el supuesto B |
| Limitaciones | La interfaz de línea de comandos de PHP y `xmllint` no existen en el contenedor; no hay repositorio git, historial ni integración continua; no existe suite de pruebas ni construcción; los subagentes carecieron de herramienta de terminal en esta instalación; el límite de profundidad de subagentes (1) impidió que CodeReviewer y TestEngineer invocaran ContextScout. La verificación de sintaxis y de extensiones queda pendiente de ejecución en el entorno de producción |

### Índice de secciones

1. Metadatos
2. Resumen ejecutivo
3. Hallazgos por severidad (crítica, alta, media y baja)
4. Estado de pruebas (Bloque 3)
5. Validación de construcción (Bloque 4)
6. Riesgos y deuda técnica
7. Próximos pasos
8. Anexos (pruebas, construcción y entorno, incidencias de herramientas y artefactos de la sesión)

---

## 2. Resumen ejecutivo

**Veredicto global: no apto.**

El punto de entrada canónico no arranca por el cálculo erróneo de las rutas base (C-03); la interfaz web permite ejecuciones anónimas con efectos secundarios y consumo de cuota (C-01); la gestión de secretos es incompleta y contradice la documentación (C-02); la notificación de borrados a Google es incorrecta (A-02); y la automatización por línea de comandos documentada no existe (A-03).

**Condiciones mínimas para alcanzar «apto con correcciones»:**

1. Corregir las tres severidades críticas: rutas base (C-03), autenticación del punto de entrada web (C-01) y gestión de secretos, incluida la rotación de la clave IndexNow y la creación de `.gitignore` (C-02).
2. Corregir la notificación de borrados de la Google Indexing API para que envíe `URL_DELETED` (A-02).
3. Alinear la documentación con el contrato real de entradas y con las rutas efectivas (A-06 y A-07).

Los hallazgos de severidad media y baja no bloquean el veredicto, pero se recomienda abordarlos para reducir la deuda técnica acumulada.

La dimensión de pruebas también resulta **no apta**: no existe suite ni caso alguno, y la cobertura automatizada es nula en todos los módulos (cero por ciento, sin instrumentación).

---

## 3. Hallazgos por severidad

### 3.1 Severidad crítica

#### C-01 — Interfaz web sin autenticación con efectos secundarios

- **Evidencia**: `core_index/sitemap_form_url.php:268-289`; no existe `.htaccess` ni regla equivalente de servidor; `manuales/03:97-120` trata la protección como opcional.
- **Impacto**: agotamiento de la cuota de IndexNow y de Google, escritura del sitemap y notificaciones no deseadas por visitas repetidas.
- **Recomendación**: exigir autenticación (Basic con archivo de contraseñas fuera del repositorio, token en cabecera o variable de entorno), limitar la frecuencia y documentar la protección como obligatoria. Si solo se busca automatización, retirar el punto de entrada web.

#### C-02 — Gestión de secretos incompleta

- **Evidencia**: no existe `.gitignore` (verificado); `core_index/config/config.php:45` y `:47` incluyen la clave IndexNow literal; `core_index/config/indexnow_key.txt:1` la repite; `gestion/generate_sitemap.php:34-36` la vuelve a fijar; `GOOGLE_CREDENTIALS_PATH` en `core_index/config/config.php:53` apunta a `gestion/credentials.json`, dentro del árbol web; la documentación afirma que existe `.gitignore` y que las credenciales están excluidas (`README.md:153`, `:187`; `AGENTS.md:16`, `:36`; `manuales/02:811`; `docs/plan.md:84`), lo que no es cierto. La clave no se reproduce en este informe.
- **Recomendación**: crear `.gitignore` (credenciales, sitemaps generados, registros y clave real), rotar la clave IndexNow, cargarla desde archivo o variable de entorno, mover las credenciales fuera del documento raíz o denegar su acceso y corregir la documentación.

#### C-03 — Rutas base mal calculadas: el sistema canónico no arranca

- **Evidencia**: `core_index/config/config.php:22-26`, `:31`, `:35`, `:38`, `:53`; `validateConfiguration()` en `:83-111`; aborto en `core_index/sitemap_form_url.php:270-273`; `AGENTS.md:16` describe rutas que no coinciden.
- **Causa**: `ROOT_DIR = dirname(__DIR__)` resuelve a `core_index`, por lo que `CORE_DIR`, `DATA_DIR` y `URLS_JSON_PATH` apuntan a rutas inexistentes. El archivo real de entrada está en `core_index/data/urls.json` (verificado presente).
- **Ejemplo de corrección propuesta**:

```php
// core_index/config/config.php:22
// Actual: resuelve a core_index (incorrecto)
define('ROOT_DIR', dirname(__DIR__));
// Corrección: resuelve a la raíz del proyecto
define('ROOT_DIR', dirname(__DIR__, 2));
```

- **Recomendación**: recalcular las constantes dependientes y añadir una prueba de humo que verifique la configuración y la ruta real de `urls.json`.

### 3.2 Severidad alta

#### A-01 — Inyección y validación insuficiente al construir el sitemap

- **Evidencia**: `core_index/sitemap_generator.php:194-197` escapa solo `loc` e interpola `lastmod`, `changefreq` y `priority`; la ruta JSON no convierte `priority` a número real ni valida `loc` (líneas 53, 57-64); `MAX_URLS_PER_SITEMAP` (`core_index/config/config.php:69`) no se aplica.
- **Recomendación**: escapar los cuatro campos con `htmlspecialchars($valor, ENT_XML1 | ENT_QUOTES, 'UTF-8')`, validar `loc`, `priority` (rango 0-1), `changefreq` (lista blanca) y `lastmod` (formato ISO), y aplicar el límite de entradas.

#### A-02 — Google Indexing API nunca envía `URL_DELETED`

- **Evidencia**: `core_index/sitemap_generator.php:122`, `:227-234`, `:259-269`; `core_index/google_indexing_client.php:145-147` fija `URL_UPDATED` para todas las URLs; contradice `AGENTS.md:24` y `manuales/03:452-475`.
- **Recomendación**: transportar el tipo por URL (`URL_UPDATED` para nuevas y actualizadas, `URL_DELETED` para eliminadas) y reflejar contadores reales.

#### A-03 — La ejecución por línea de comandos documentada no hace nada

- **Evidencia**: `core_index/sitemap_generator.php` carece de bloque principal (cierra en la línea 301); `README.md:304-307` y `:346-356`, `manuales/03:249-280` y `manuales/04:642-651` documentan su uso con salida esperada y cron.
- **Recomendación**: añadir un bloque principal condicionado a la interfaz de línea de comandos o corregir la documentación.

#### A-04 — La opción Google no es realmente opcional en la interfaz web

- **Evidencia**: `core_index/config/config.php:109-111` añade error si falta la credencial y `core_index/sitemap_form_url.php:270-273` aborta; `README.md:88-92`, `AGENTS.md:18` y `manuales/01:239-243` afirman lo contrario.
- **Recomendación**: separar advertencias de errores y deshabilitar solo la rama de Google.

#### A-05 — Uso de Google Indexing API fuera de sus condiciones documentadas

- **Evidencia**: `manuales/00:7-11` y `:64` recogen la limitación a `JobPosting` y `BroadcastEvent`; `README.md:33-37`, `manuales/01:210-217` y `core_index/google_indexing_client.php:57` la presentan como notificación general.
- **Recomendación**: advertir la limitación y no prometer indexación general.

#### A-06 — Formatos de entrada erróneos en la documentación principal

- **Evidencia**: `README.md:259-289` propone un array desnudo con clave `url` y cabecera `url`; reproducido en `manuales/02:22-31`, `:132-136`, `:164-168` y `manuales/04:994-1016`; el contrato real exige `{"urls": [{"loc": ...}]}` y cabecera `loc` (`core_index/sitemap_form_url.php:59`, `:127`; `core_index/README.md:65-89`).
- **Ejemplo del contrato real**:

```json
{"urls": [{"loc": "https://ejemplo.test/pagina", "priority": 0.8}]}
```

- **Recomendación**: unificar documentación y ejemplos con el contrato real.

#### A-07 — Rutas de clave, credenciales, sitemap y registros incorrectas en casi toda la documentación

- **Evidencia**: `README.md:239` frente a `core_index/indexnow_auth.php:24` y `core_index/config/config.php:46`; `README.md:330` frente a `core_index/config/config.php:53`; `README.md:310` y `:384-395`, `manuales/03:544-575`, `manuales/04:525-530` y `:730-731` frente a `LOG_PATH` único (`core_index/config/config.php:35`); `core_index/README.md:108`; `AGENTS.md:11` y `:17`.
- **Recomendación**: fijar una única fuente de verdad de rutas y regenerar la documentación.

### 3.3 Severidad media

#### M-01 — Búsquedas lineales dentro de bucles en el filtrado de notificaciones

- **Evidencia**: `core_index/sitemap_generator.php:227-234` (`in_array` sobre `$changedUrls` por URL).
- **Recomendación**: usar `array_flip` e `isset`.

#### M-02 — Constantes de configuración sin efecto

- **Evidencia**: `INDEXNOW_TIMEOUT` (`core_index/config/config.php:48`) no se usa (`core_index/indexnow_client.php:82` fija 30); `GOOGLE_API_TIMEOUT` (`core_index/config/config.php:54`) no se usa (`core_index/google_indexing_client.php:76`, `core_index/google_indexing_auth.php:144`); `GOOGLE_DAILY_QUOTA` (`:55`) y `MAX_URLS_PER_SITEMAP` (`:69`) no se aplican; sin troceo por encima de 10 000 URLs (`core_index/indexnow_client.php:39-47`).
- **Recomendación**: usar las constantes, aplicar la cuota y trocear los envíos.

#### M-03 — Fallo silencioso al interpretar el sitemap anterior

- **Evidencia**: `core_index/sitemap_diff.php:32-37` devuelve lista vacía ante XML ilegible; `core_index/sitemap_generator.php:96-99` copia el respaldo con `@copy` sin comprobar.
- **Impacto**: renotificación masiva.
- **Recomendación**: distinguir ausencia de corrupción, registrar el fallo y evitar la renotificación.

#### M-04 — Clasificación de cambios duplicada

- **Evidencia**: `core_index/sitemap_diff.php:72-101` y `core_index/sitemap_generator.php:122-141`.
- **Recomendación**: devolver la clasificación completa desde el módulo de diferencias.

#### M-05 — El núcleo canónico escribe credenciales y registros en el directorio legado

- **Evidencia**: `core_index/config/config.php:35` y `:53`; `core_index/logger.php:57-63`; `gestion/generate_sitemap.php:550`, `:269-278`.
- **Recomendación**: trasladar a una ubicación neutral, sin ampliar `gestion/`.

#### M-06 — Mensajes dinámicos sin escapar

- **Evidencia**: `core_index/sitemap_form_url.php:231`, `:245` y `:272`.
- **Recomendación**: escapar con `htmlspecialchars($valor, ENT_QUOTES, 'UTF-8')` y valorar no mostrar detalles internos.

#### M-07 — Divulgación de rutas internas y visualización de errores

- **Evidencia**: `core_index/sitemap_form_url.php:272`, `:282`; `config.php` de la raíz:21-27.
- **Recomendación**: mensajes genéricos al visitante y detalle solo en el registro.

#### M-08 — Registro sin bloqueo, sin rotación efectiva y sin saneado completo

- **Evidencia**: `core_index/logger.php:43-54`, `:66`; `LOG_MAX_SIZE` y `LOG_ENABLED` sin aplicar (`core_index/config/config.php:73-74`); excepción no capturada en `core_index/sitemap_generator.php:265`.
- **Recomendación**: añadir `LOCK_EX`, sanear todos los campos, aplicar la rotación y capturar excepciones.

#### M-09 — Tratamiento incompleto del CSV

- **Evidencia**: `core_index/sitemap_form_url.php:145`, `:153-163`; `manuales/02:209-213` frente a los valores por defecto reales (`core_index/config/config.php:61-66`).
- **Recomendación**: validar con filtros y lista blanca de cabeceras y valores, y documentar los valores por defecto.

#### M-10 — Documentación técnica desactualizada y enlaces rotos

- **Evidencia**: `core_index/README.md:7` frente a `:34`; `:176-179`; `README.md:129-143`, `:368-373`; `docs/PLAN_DOCUMENTACION_FINAL.md:207-228`, `:296`; `docs/plan.md:31`, `:139`; `manuales/04:933-942`.
- **Recomendación**: marcar los planes como históricos y alinear con el inventario real.

#### M-11 — Comandos y fragmentos de prueba de los manuales no funcionan

- **Evidencia**: `manuales/02:645` frente a `core_index/indexnow_auth.php:20-44`; `manuales/02:900-908` y `manuales/03:727-728` frente a `core_index/google_indexing_auth.php:204-209`; `manuales/01:817-828` frente a `validateConfiguration()`.
- **Recomendación**: corregir ejemplos y contratos de retorno.

#### M-12 — Página de resultados muerta y rastros del proyecto original en el legado

- **Evidencia**: `gestion/result_sitemap.php:67-75`, `:92`; `gestion/generate_sitemap.php:4`, `:34-36`, `:342`, `:678`, `:776-779`; `README.md:214`.
- **Recomendación**: marcar o retirar el legado en la limpieza y corregir la descripción del flujo.

### 3.4 Severidad baja

#### B-01 — `openssl_free_key()` obsoleto desde PHP 8.0

- **Evidencia**: `core_index/google_indexing_auth.php:112`; `gestion/indexing_api_auth.php:89`.
- **Recomendación**: eliminarlo.

#### B-02 — Falta lista blanca en el parámetro `source`

- **Evidencia**: `core_index/sitemap_form_url.php:276-279`.
- **Recomendación**: aceptar solo `json` y `csv`.

#### B-03 — Dependencia externa no declarada

- **Evidencia**: `core_index/sitemap_form_url.php:192` carga Bootstrap 5 desde `cdn.jsdelivr.net` sin atributo de integridad.
- **Recomendación**: alojar los activos en el proyecto o documentar la dependencia con integridad.

#### B-04 — Constantes sin uso operativo o duplicadas

- **Evidencia**: `core_index/config/config.php:26`, `:44-48`, `:54-55`, `:61-74`; `core_index/indexnow_auth.php:51-52`, `:55`.
- **Recomendación**: una única fuente para clave, host y ubicación.

#### B-05 — `AGENTS.md` describe archivos que no existen

- **Evidencia**: `AGENTS.md:11`, `:17`; `core_index/README.md:108`.
- **Recomendación**: corregir o reponer.

#### B-06 — `validateConfiguration()` no comprueba lo que la documentación afirma

- **Evidencia**: `core_index/config/config.php:104-121` frente a `AGENTS.md:15`.
- **Recomendación**: validar barra final y escribibilidad de sitemap y registro.

#### B-07 — Encabezado de resultados siempre verde

- **Evidencia**: `core_index/sitemap_form_url.php:198-201`, `:228-250`.
- **Recomendación**: derivar el estado global del resultado.

---

## 4. Estado de pruebas (Bloque 3)

**Veredicto de la dimensión de pruebas: no apto.**

- **Ausencia de suite**: no existe suite de pruebas, ni ejecutor, ni caso alguno. Confirmado: sin `composer.json`, sin `phpunit.xml`, sin referencias a PHPUnit, sin integración continua, sin construcción (`Makefile`, `Dockerfile`) y sin archivos de prueba en todo el proyecto.
- **Cobertura automatizada**: nula (cero por ciento) en todos los módulos; no hay instrumentación ni cifra medible, por lo que no se estiman porcentajes.
- **Criterios evaluados y resultado**:
  - Casos positivos y negativos: incumplido por ausencia total.
  - Patrón organizar, actuar y verificar: no aplicable sin pruebas.
  - Cobertura de ramas críticas y casos límite: inexistente.
  - Aislamiento de dependencias externas: inexistente (`curl` directo, constantes fijadas al incluir, escritura en la raíz, dependencia del tiempo real y colisiones de nombres entre `gestion/` y `core_index/` que impiden cargar ambos árboles en el mismo proceso).
- **Riesgos principales**:
  - Regresión silenciosa en la lógica de diferencias (alto).
  - Toda prueba del orquestador escribiría el sitemap en la raíz y consumiría cuota real (alto).
  - Configuración de seguridad sin red de seguridad (alto).
  - Contratos de entrada frágiles (medio).
  - Degradación silenciosa de la autenticación de Google (medio).
  - Fallo de escritura del registro (medio).
- **Recomendación priorizada mínima**, sin crear pruebas en esta auditoría:
  1. Lógica pura de diferencias y validaciones.
  2. Cargas de entrada y presentación.
  3. Orquestador con rutas y clientes inyectables.
  4. Clientes HTTP y autenticación con servidor local de prueba.
  5. Legado solo si se conserva.

La elección de herramienta (ejecutor nativo con aserciones propias o PHPUnit mediante archivo PHAR) queda pendiente de aprobación.

---

## 5. Validación de construcción (Bloque 4)

La sesión de BuildAgent no dispuso de herramienta de ejecución de comandos: ninguna comprobación se pudo ejecutar en su seno. Lo registró conforme al apartado 7.2 y no aplicó correcciones.

Comprobaciones ejecutadas por el agente orquestador (solo lectura):

- `command -v php`: ausente. `php --version`, `php -m` y `php -l`: no ejecutables en este contenedor (compatible con lo advertido en `AGENTS.md:35`).
- `command -v xmllint`: ausente. `xmllint --noout sitemap.xml` y `grep -c "<url>" sitemap.xml`: no aplicables, porque `sitemap.xml` no existe (no se genera).
- Inventario verificado de los 13 archivos PHP: presentes y coincidentes con el alcance (uno en la raíz, nueve en `core_index/` y tres en `gestion/`).
- Sin `composer.json`, sin `phpunit.xml`, sin `Makefile` y sin flujos de integración continua: confirma el supuesto B.

**Conclusión**: la verificación de sintaxis y de extensiones no es reproducible en este contenedor. Debe ejecutarse en el entorno de producción (ea-php81 mediante cPanel o LiteSpeed) con `php -l` sobre los 13 archivos y `php -m` para `openssl`, `curl`, `json` y `simplexml`. No se propone instalar nada en el contenedor.

---

## 6. Riesgos y deuda técnica

| Ámbito | Riesgo o deuda | Referencias |
|---|---|---|
| Operación y seguridad | Ejecución anónima del punto de entrada web con consumo de cuota; renotificación masiva por fallo silencioso del sitemap anterior; secretos en claro y credenciales dentro del árbol web | C-01, C-02, M-03 |
| Funcionalidad | El flujo canónico no arranca y la notificación de borrados es incorrecta; los borradores de la línea de comandos no existen | C-03, A-02, A-03 |
| Configuración | Cuatro constantes sin efecto (tiempos de espera, cuota diaria, límite de URLs) y constantes duplicadas | M-02, B-04 |
| Estructura | Clasificación de cambios duplicada; el núcleo escribe en el directorio legado; el legado conserva una página muerta y rastros del proyecto original | M-04, M-05, M-12 |
| Documentación | Contratos de entrada y rutas erróneas, ejemplos no funcionales, enlaces rotos y archivos descritos que no existen | A-05, A-06, A-07, M-10, M-11, B-05, B-06 |
| Pruebas | Ausencia total de suite, ejecutor y cobertura, sin red de seguridad ante regresiones | Bloque 3 |
| Proceso | Sin repositorio git ni `.gitignore`, sin integración continua y sin control de versiones del proyecto auditado | Limitaciones de entorno, C-02 |

---

## 7. Próximos pasos

Ordenados por prioridad. Ninguna corrección debe aplicarse sin aprobación previa, conforme al apartado 7.2.

1. **Correcciones críticas y bloqueantes**
   - C-03: recalcular `ROOT_DIR` como `dirname(__DIR__, 2)` y las constantes dependientes; añadir prueba de humo de configuración y ruta de `urls.json`.
   - C-01: exigir autenticación en el punto de entrada web, limitar la frecuencia y documentar la protección como obligatoria (o retirar la interfaz).
   - C-02: crear `.gitignore`, rotar la clave IndexNow, cargarla desde archivo o variable de entorno, mover o proteger las credenciales y corregir la documentación que afirma lo contrario.
   - A-02: transportar el tipo de operación por URL y enviar `URL_DELETED` en las eliminaciones.
2. **Correcciones de severidad alta**
   - A-01: escapar los cuatro campos y validar `loc`, `priority`, `changefreq` y `lastmod`; aplicar `MAX_URLS_PER_SITEMAP`.
   - A-03: añadir bloque principal a `sitemap_generator.php` o retirar la documentación de uso por línea de comandos.
   - A-04: separar advertencias de errores para que Google sea realmente opcional en la interfaz web.
3. **Alineación documental**
   - A-05: advertir la limitación de la Google Indexing API a `JobPosting` y `BroadcastEvent`.
   - A-06 y A-07: unificar contratos de entrada, rutas de clave, credenciales, sitemap y registros con la fuente de verdad real.
   - M-10, M-11 y B-05: marcar planes como históricos, corregir ejemplos no funcionales y alinear `AGENTS.md` con el inventario real.
4. **Pruebas**
   - Ejecutar la secuencia priorizada del Bloque 3 (diferencias y validaciones; cargas y presentación; orquestador con rutas inyectables; clientes HTTP con servidor local; legado solo si se conserva), tras aprobar la herramienta.
5. **Deuda técnica de severidad media y baja**
   - M-01, M-02, M-04 a M-09 y M-12: búsquedas lineales, constantes sin efecto, fallos silenciosos, duplicación, mensajes y registro, tratamiento del CSV y limpieza del legado.
   - B-01 a B-04, B-06 y B-07: limpiezas menores y comprobaciones de configuración y estado.

---

## 8. Anexos

### Anexo A — Resultados de pruebas

| Criterio | Resultado |
|---|---|
| Casos positivos y negativos | Incumplido: no existe caso alguno |
| Patrón organizar, actuar y verificar | No aplicable: sin pruebas |
| Cobertura de ramas críticas y casos límite | Inexistente |
| Aislamiento de dependencias externas | Inexistente |
| Cobertura automatizada estimada | Cero por ciento en todos los módulos; sin instrumentación |

Inventario de soporte: sin `composer.json`, `phpunit.xml`, PHPUnit, integración continua, `Makefile`, `Dockerfile` ni archivos de prueba.

### Anexo B — Resultados de construcción y comprobaciones de entorno

| Comprobación | Resultado |
|---|---|
| `command -v php` | Ausente en el contenedor |
| `php --version`, `php -m`, `php -l` | No ejecutables |
| `command -v xmllint` | Ausente en el contenedor |
| `xmllint --noout sitemap.xml` | No aplicable: `sitemap.xml` no existe |
| `grep -c "<url>" sitemap.xml` | No aplicable: `sitemap.xml` no existe |
| Inventario de archivos PHP | 13 presentes y coincidentes con el alcance |
| `composer.json`, `phpunit.xml`, `Makefile`, integración continua | Ausentes: confirma el supuesto B |

Verificación pendiente en producción: `php -l` sobre los 13 archivos y `php -m` para `openssl`, `curl`, `json` y `simplexml`.

### Anexo C — Incidencias de herramientas (apartado 7.2)

Incidencias informadas, sin corregir y pendientes de aprobación:

1. **Límite de profundidad de subagentes**: `Subagent depth limit reached (1)`. CodeReviewer y TestEngineer no pudieron invocar ContextScout desde su sesión; continuaron con los contextos indicados directamente. Propuesta: elevar `subagent_depth` a 2 en la configuración de OpenCode o invocar ContextScout únicamente desde el agente orquestador.
2. **Sesiones de subagente sin ejecución de comandos**: TestEngineer, BuildAgent y CodeReviewer carecieron de herramienta de terminal en esta instalación; las comprobaciones de entorno las asumió el orquestador. Propuesta: revisar la configuración de permisos y de herramientas de los subagentes si se desea que ejecuten comandos en futuras auditorías.
3. **Interfaz de línea de comandos de tareas no ejecutable**: `npx ts-node` (10.9.2) falla con Node.js 24 (`TypeError: Cannot read properties of undefined (reading 'fileExists')`); `.opencode/package.json` no declara `ts-node`. Propuesta: usar Node.js 22 LTS, un ejecutor compatible (por ejemplo `tsx`) o validar los archivos JSON con Node.js nativo y `jq`.
4. **Ruta de seguimiento de la interfaz de línea de comandos**: la herramienta lee `.tmp/tasks/{feature}/`, mientras las subtareas se crearon en `tasks/subtasks/auditoria-ssen/` por indicación expresa. Propuesta: copiarlas a `.tmp/tasks/auditoria-ssen/` con un archivo de nivel de función si se desea seguimiento con la interfaz de línea de comandos.

### Anexo D — Artefactos de la sesión

- Artefactos de la sesión `20260921-auditoria-ssen` (contexto, manifiesto y consolidado de hallazgos): eliminados tras el cierre, conforme a la limpieza aprobada.
- `.tmp/sessions/20260921-remediacion-ssen/` — sesión de la remediación (contexto y manifiesto).
- `tasks/subtasks/auditoria-ssen/` — subtareas de coordinación de TaskManager.
- `docs/INFORME_AUDITORIA_SSEN_2026-09-21.md` — este informe.
- `docs/instruccion-auditoria-agnostica.md` — instrucción rectora, versión 2.0.

Bloques ejecutados: 1 (descubrimiento), 2 (auditoría general), 3 (pruebas), 4 (construcción adaptada), 5 (coordinación), 6 (informe) y 7.2 (regla ante fallos). Bloque 7.1 omitido por tratarse del supuesto B.

---

### Anexo E — Estado tras la remediación (22 de septiembre de 2026)

- **Correcciones aplicadas**: las tres severidades críticas (rutas base, autenticación del punto de entrada y gestión de secretos), la notificación `URL_DELETED` de Google con comprobación previa de 404 o 410, el resto de severidades altas y la deuda media y baja; clave de IndexNow rotada y secretos fuera del control de versiones.
- **Pruebas**: arnés nativo con 30 pruebas (correctas en PHP 8.3 en el servidor); integración continua preparada con matriz de PHP 8.1 a 8.4.
- **Repositorio**: https://github.com/paginaviva/simple-search-engine-notification — commit `c123a6a`; descripción, temas y página principal actualizados. La integración continua queda pendiente de publicar mientras el token no disponga del permiso «workflow».
- **Despliegue de pruebas**: https://www.espresso.iadesarrollo.top — host canónico con `www`, autenticación básica de aplicación (401 sin credenciales y 200 con ellas), IndexNow con respuesta 200 y registro fuera del documento raíz (`private/logs`, por la restricción `open_basedir` de Hestia).
- **Pendiente**: actualizar PHP en producción (8.1 sin soporte), configurar credenciales de Google si procede, definir la tarea programada en el servidor de pruebas y valorar la purga del historial remoto (contiene la clave antigua, ya invalidada).
