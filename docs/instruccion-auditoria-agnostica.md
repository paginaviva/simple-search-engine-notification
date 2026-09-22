# Instrucción agnóstica respecto de la pila auditada para delegar una auditoría con OpenAgents Control

Fecha de revisión: 2026-09-21
Versión: 2.0

Nota de alcance: esta instrucción es agnóstica respecto de la pila tecnológica del proyecto auditado. No es agnóstica respecto de la herramienta auditora: requiere OpenAgents Control y OpenCode CLI en ejecución, porque delega en subagentes de OpenAgents Control.

Fuente de los guiones propios de OpenAgents Control: repositorio oficial, https://github.com/darrenhinde/OpenAgentsControl, carpetas `scripts/registry/` y `scripts/validation/`. Esos guiones validan el registro de componentes y las suites de evaluación del propio repositorio de OpenAgents Control. No son validadores genéricos de un proyecto cualquiera.

## Dos supuestos de uso

- Supuesto A. El proyecto auditado es el propio repositorio de OpenAgents Control. Aplican los guiones de la fuente oficial, con sus prerequisitos.
- Supuesto B. El proyecto auditado es cualquier otro proyecto. Se omiten esos guiones y se emplean los comandos propios de su pila para instalación, verificación de tipos, construcción y pruebas.

## Prerequisitos

Base, en ambos supuestos:
- OpenCode CLI en ejecución y OpenAgents Control instalado, de modo que existan los subagentes ContextScout, CodeReviewer, TestEngineer, BuildAgent, TaskManager y DocWriter.
- Bash 3.2 o superior, git y curl.
- jq, en una versión reciente.

Adicionales, solo en el supuesto A:
- Node.js con npx.
- ajv-cli instalado en `evals/framework`.
- `registry.json`, `evals/agents/` y `evals/framework` presentes en la raíz del repositorio.

Si falta alguna dependencia, no continúe: instálela o cambie al supuesto B.

## Verificación previa de los guiones

Aplique este apartado solo en el supuesto A.

```bash
test -f scripts/registry/validate-registry.sh && echo "Existe guion de registro" || echo "Falta guion de registro"
test -f scripts/validation/validate-test-suites.sh && echo "Existe guion de validación" || echo "Falta guion de validación"
```

Si falta alguno, obténgalo desde el repositorio oficial, cree antes el directorio de destino y otorgue permiso de ejecución. Verifique el contenido descargado antes de usarlo:

```bash
mkdir -p scripts/registry scripts/validation
test -e /tmp/openagents-control || git clone --depth 1 https://github.com/darrenhinde/OpenAgentsControl /tmp/openagents-control
cp /tmp/openagents-control/scripts/registry/validate-registry.sh scripts/registry/validate-registry.sh
cp /tmp/openagents-control/scripts/validation/validate-test-suites.sh scripts/validation/validate-test-suites.sh
chmod +x scripts/registry/validate-registry.sh scripts/validation/validate-test-suites.sh
```

Advertencia: copiar solo los guiones no basta. Ambos resuelven rutas relativas a su ubicación, dos niveles por encima del guion, y dependen de la estructura de OpenAgents Control: `registry.json`, `evals/agents/`, `evals/framework` y `suite-schema.json`. Si el proyecto auditado no es OpenAgents Control, use el supuesto B.

## Bloque 1. Descubrimiento previo

El descubrimiento de rutas de contexto corresponde a ContextScout. Invóquelo antes de fijar rutas, sobre todo si el proyecto no dispone del árbol estándar de contexto.

```javascript
task(
  subagent_type="ContextScout",
  description="Descubrir contexto para auditoría",
  prompt="Busca archivos de contexto relevantes para: auditoría general de proyecto con foco en calidad, seguridad, rendimiento y facilidad de mantenimiento, verificación de pruebas y validación de construcción. Devuelve rutas y relevancia."
)
```

## Bloque 2. Auditoría general con el revisor de código

CodeReviewer invoca ContextScout conforme a su diseño. Las rutas indicadas son las esperadas en una instalación estándar de OpenAgents Control.

```javascript
task(
  subagent_type="CodeReviewer",
  description="Auditar proyecto completo",
  prompt="Contexto a cargar:\n- .opencode/context/core/workflows/code-review.md\n- .opencode/context/core/standards/code-quality.md\n\nTarea: auditar [nombre del proyecto] en [ruta base del proyecto].\n\nArchivos a revisar:\n- [archivo 1] - [propósito]\n- [archivo 2] - [propósito]\n- [directorio 1] - [propósito]\n\nFocos obligatorios:\n- Calidad y patrones modulares y funcionales\n- Vulnerabilidades de seguridad y gestión de secretos\n- Rendimiento y uso eficiente de recursos\n- Facilidad de mantenimiento y claridad documental\n\nEntregable:\n- Lista de hallazgos por severidad\n- Evidencia por archivo y línea\n- Recomendación concreta por hallazgo\n- Veredicto global: apto, apto con correcciones o no apto"
)
```

## Bloque 3. Verificación de pruebas con el ingeniero de pruebas

TestEngineer es el encargado de ejecutar las pruebas: su configuración de permisos lo autoriza. Invoca ContextScout conforme a su diseño.

```javascript
task(
  subagent_type="TestEngineer",
  description="Verificar pruebas del proyecto",
  prompt="Contexto a cargar:\n- .opencode/context/core/standards/test-coverage.md\n\nTarea: verificar el estado de pruebas de [nombre del proyecto].\n\nArchivos a verificar:\n- [archivo de origen 1]\n- [archivo de prueba 1]\n\nCriterios:\n- Casos positivos y negativos\n- Patrón organizar, actuar y verificar\n- Cobertura de ramas críticas y casos límite\n- Aislamiento de dependencias externas\n\nEntregable:\n- Cobertura estimada por módulo\n- Pruebas ausentes o débiles\n- Riesgos por falta de cobertura"
)
```

## Bloque 4. Validación de construcción con el agente de construcción

BuildAgent solo dispone de permiso para verificación de tipos y construcción. No puede instalar dependencias ni ejecutar pruebas. No le asigne esas tareas; la ejecución de pruebas corresponde al Bloque 3. Invoca ContextScout conforme a su diseño.

```javascript
task(
  subagent_type="BuildAgent",
  description="Validar construcción del proyecto",
  prompt="Contexto a cargar:\n- .opencode/context/core/standards/code-quality.md\n\nTarea: validar la verificación de tipos y la construcción de [nombre del proyecto].\n\nComandos de referencia, adapte según la pila y use solo los permitidos al agente:\n- [comando de verificación de tipos]\n- [comando de construcción]\n\nEntregable:\n- Resultado por comando: correcto o fallo\n- Salida relevante del fallo\n- Causa probable sin aplicar corrección"
)
```

## Bloque 5. Coordinación compleja con el gestor de tareas, solo si aplica

Use este bloque cuando la auditoría abarque cuatro o más archivos, exija más de sesenta minutos o presente dependencias complejas.

El agente orquestador, no TaskManager, crea el directorio y el fichero de contexto de la sesión antes de delegar. TaskManager no crea sesiones ni escribe en `.tmp/sessions/`: recibe el contexto del agente llamador.

```javascript
task(
  subagent_type="TaskManager",
  description="Coordinar auditoría compleja",
  prompt="Cargue el contexto de la sesión en .tmp/sessions/[identificador-de-sesion]/context.md. Divida la auditoría en subtareas atómicas con criterios de aceptación y marque como paralelas las tareas aisladas."
)
```

## Bloque 6. Informe final con el redactor de documentación

```javascript
task(
  subagent_type="DocWriter",
  description="Redactar informe de auditoría",
  prompt="Contexto a cargar:\n- .opencode/context/core/standards/documentation.md\n\nTarea: redactar el informe final de auditoría de [nombre del proyecto].\n\nIncluya:\n- Resumen ejecutivo con veredicto\n- Hallazgos por severidad con archivo y línea\n- Riesgos y deuda técnica\n- Próximos pasos ordenados por prioridad\n- Anexos con resultados de pruebas y construcción\n\nNormas:\n- Contenido conciso y de alta señal\n- Ejemplos donde aporten claridad\n- Fecha y versión del informe"
)
```

## Bloque 7. Validación del propio repositorio de OpenAgents Control y regla ante fallos

Este bloque se compone de dos asuntos distintos.

### 7.1 Ejecución de los guiones, solo en el supuesto A

Los guiones validan el repositorio de OpenAgents Control, no un proyecto cualquiera. Ejecútelos únicamente en el supuesto A y tras superar la verificación previa.

```bash
./scripts/registry/validate-registry.sh
./scripts/validation/validate-test-suites.sh
```

Códigos de salida de ambos guiones:
- 0: todo válido.
- 1: se encontraron errores de validación.
- 2: error de análisis o dependencias ausentes.

En el supuesto B, sustituya estos guiones por los comandos propios del proyecto auditado para instalación, verificación de tipos, construcción y pruebas.

### 7.2 Regla ante fallos, en ambos supuestos

Ante cualquier fallo, detenga el trabajo, informe del guion o comando fallido y su salida, proponga la causa y los pasos de corrección, y espere aprobación antes de corregir. Vuelva a validar tras la corrección.
