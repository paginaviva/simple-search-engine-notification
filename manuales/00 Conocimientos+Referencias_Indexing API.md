## 1 Conocimientos específicos sobre Indexing API que necesita el agente

Todo esto está en la documentación oficial de Google y es lo mínimo que el agente tiene que “dominar” para programar bien los dos guiones PHP:

### a) Qué es exactamente la Indexing API y sus límites

* Saber que la Indexing API **solo puede usarse** para páginas que contengan:

  * `JobPosting`, o
  * `BroadcastEvent` incrustado en un `VideoObject`. ([Google for Developers][1])

  **Advertencia:** notificar otros tipos de página queda fuera de las condiciones de uso de Google y no garantiza la indexación. Esta API no es un mecanismo de indexación general.
* Entender que sirve para **notificar altas, actualizaciones y borrados** de URLs, para que Google programe un nuevo rastreo. ([Google for Developers][2])

### b) Endpoints y contrato HTTP

El agente debe conocer la referencia oficial de la API:

* Recurso REST `v3.urlNotifications` con:

  * `publish` → `POST https://indexing.googleapis.com/v3/urlNotifications:publish`
  * `getMetadata` → `GET https://indexing.googleapis.com/v3/urlNotifications/metadata` ([Google for Developers][3])

Y el formato de datos:

* Objeto `UrlNotification`:

  * Campos: `url` (string), `type` (`URL_UPDATED` o `URL_DELETED`). ([Google for Developers][4])
* Objeto `UrlNotificationMetadata` en la respuesta, con la información del último evento. ([Google for Developers][3])

Con esto puede construir el JSON correcto en PHP para cada URL del lote.

### c) Autenticación con cuenta de servicio (service account)

El agente necesita tener claro:

* Que **todas** las peticiones deben llevar un token OAuth 2.0 Bearer válido. ([Google for Developers][5])
* Que el **scope obligatorio** es:
  `https://www.googleapis.com/auth/indexing` ([Google for Developers][6])
* Que, en PHP, la documentación muestra cómo:

  * Cargar el archivo JSON de la service account:

    ```php
    require_once 'google-api-php-client/vendor/autoload.php';
    $client = new Google_Client();
    $client->setAuthConfig('service_account_file.json');
    $client->addScope('https://www.googleapis.com/auth/indexing');
    ```

    ([Google for Developers][6])
  * Obtener el access token con el cliente de PHP y luego usarlo en llamadas HTTP normales (si se decide no usar la librería de servicio). ([Google for Developers][6])

Tú ya tienes el JSON y la propiedad en Search Console vinculada; el agente solo necesita **saber dónde está ese JSON en el proyecto** y poder leerlo desde los scripts en `gestion/`.

### d) Cuotas, errores y uso correcto

Para no romper nada:

* Conocer las **cuotas por defecto**:

  * `publish`: 200 peticiones/día/proyecto (incluye `URL_UPDATED` y `URL_DELETED`).
  * `getMetadata`: 180 peticiones/minuto/proyecto.
  * Total: 380 peticiones/minuto/proyecto. ([Google for Developers][7])
* Saber que la cuota se resetea a medianoche hora del Pacífico. ([Google for Developers][7])
* Saber que el uso de la API está limitado oficialmente a `JobPosting` y `BroadcastEvent`: no admite la indexación general de páginas y Google ha añadido advertencias contra el abuso y el spam en la documentación. ([Google for Developers][7])
* Entender que errores típicos como `403` o `429` pueden estar relacionados con:

  * falta de permisos del dominio,
  * problemas de cuota o abuso,
    tal y como se discute en issues y foros relacionados con la Indexing API. ([Ayuda de Google][8])

Eso es clave para que el sistema de logs que diseñaste (por URL y por lote) registre los fallos correctamente.

---

## 2 Fuentes de información que el agente debería usar (y nada más)

### a) Documentación oficial de Google para Indexing API

Estas son las **fuentes primarias** que el agente debería tener como referencia obligatoria:

1. **Quickstart / Introducción a Indexing API**
   Explica qué hace la API, qué tipos de contenido admite y el flujo general de uso. ([Google for Developers][1])

2. **Using the Indexing API**
   Describe cómo enviar notificaciones de actualización/borrado, cómo consultar metadatos y repite la limitación a JobPosting/BroadcastEvent. ([Google for Developers][2])

3. **REST Reference: Indexing API**

   * Documenta los métodos `urlNotifications.publish` y `urlNotifications.getMetadata`. ([Google for Developers][3])

4. **Prerequisites**

   * Flujo de creación del proyecto, habilitación de la API y ejemplo explícito de cómo obtener el token con PHP usando el cliente oficial. ([Google for Developers][6])

5. **Authorize Requests**

   * Cómo adjuntar el token OAuth adecuado en cada petición a Indexing API. ([Google for Developers][5])

6. **Quota & Pricing / Requesting Approval and Quota**

   * Límites de 200 `publish`/día, 180 `getMetadata`/min, 380 req/min y que la API es gratuita, con posibilidad de solicitar más cuota para casos JobPosting/BroadcastEvent. ([Google for Developers][7])

7. **Client Libraries (visión general)**

   * Explica que se pueden usar las librerías cliente oficiales y que HTTP+JSON también es válido si se prefiere hacer las peticiones “a mano”. ([Google for Developers][9])

### b) Librería PHP oficial y ejemplos

Para PHP, las fuentes directas serían:

* **google-api-php-client (repositorio oficial en GitHub)**
  Librería general de Google para PHP, usada en el ejemplo de Indexing API en la propia doc de prerrequisitos. ([GitHub][10])

* **Documentación de `Google_Client` (PHP)**
  Referencia de métodos como `setAuthConfig`, `addScope`, `fetchAccessTokenWithAssertion()`, etc. ([googleapis.github.io][11])

* **Ejemplos y discusiones específicas de Indexing API + PHP** (secundarias, solo como apoyo, no como verdad principal):

  * Preguntas en StackOverflow donde se usa Indexing API con `google-api-php-client`. ([Stack Overflow][12])
  * Guías de uso en PHP/Laravel que se basan explícitamente en la librería oficial. ([Wiretuts][13])

El agente debería tratar estos recursos secundarios solo como **ejemplos prácticos**, contrastándolos siempre con la doc oficial anterior.

---

## 3 Información adicional que el agente necesita *de tu proyecto* (no de Google)

Aquí no estoy inventando, solo leo tus requisitos y los traduzco a “inputs” que el agente necesita para programar:

1. **Ruta exacta del JSON de la service account** dentro del proyecto

   * Por ejemplo: `gestion/mi-service-account.json` o similar (debe conocer la ruta real para usarla en `setAuthConfig()` o para leerla si construye el token “a mano”).

2. **Confirmación de la forma de hacer HTTP en PHP en tu entorno**

   * Si se va a usar:

     * la librería oficial `google-api-php-client` (requiere tenerla instalada en el entorno, normalmente vía Composer), o
     * `curl` nativo de PHP (extensión `curl`) para hacer las llamadas a `https://indexing.googleapis.com/v3/urlNotifications:publish` con el token que genere.
       Esto no es especulación: la propia doc de Google dice que puedes usar **cualquier cliente HTTP estándar** o una client library. ([Google for Developers][9])

3. **Estructura real de `sitemap.xml` en tu proyecto**

   * Aunque se asume que es un sitemap estándar (`<urlset><url><loc>...<lastmod>...</lastmod></url>...</urlset>`), el agente necesita ver:

     * Qué etiquetas concretas se usan (`<loc>`, `<lastmod>`, si hay `xmlns` etc.),
     * Y asegurarse de que la lectura con `SimpleXML` o DOM en PHP coincide con esa estructura.
       No es una suposición: tus propias instrucciones dicen que `sitemap.xml` es la “única fuente de verdad” para la lista de URLs de los lotes.

4. **Decisión sobre dónde guardar los logs**

   * Tu instrucción pide archivos de registro específicos para primer y segundo lote, probablemente algo tipo:

     * `gestion/indexing_batch1.log`
     * `gestion/indexing_batch2.log`
   * El agente necesita:

     * la carpeta exacta (y permisos de escritura en el hosting compartido),
     * el formato esperado de cada línea (fecha, URL, lote, resultado, mensaje).

5. **Política exacta de uso de la cuota diaria**

   * Ya has definido los lotes (135 + ~100 URLs) pensando en la cuota de 200/día de Indexing API. ([Google for Developers][7])
   * El agente solo tiene que respetar ese reparto, pero para programar bien necesita saber si:

     * el primer script **debe** mandar siempre las 135 URLs en una sola ejecución,
     * y el segundo mandar todas las restantes (~100) en otra ejecución,
     * o si se quiere incluir lógica para parar si detecta respuestas de error de cuota (por ejemplo, `429`).
       Esto se puede inferir desde tus requisitos pero no requiere inventar comportamiento de la API: simplemente usar lo que devuelva el endpoint.

---

## 4 Resumen en lenguaje muy directo

El **Agente desarrollador** ya tiene:

* ✅ Cuenta de servicio creada
* ✅ JSON de credenciales
* ✅ Propiedad en Search Console vinculada

Para centrarse **solo en la programación de Indexing API**, necesita:

1. **Conocimiento técnico exacto** sobre:

   * Qué URLs se permiten (JobPosting / BroadcastEvent). ([Google for Developers][1])
   * Qué endpoints usar (`publish`, `getMetadata`) y cómo son las peticiones/respuestas. ([Google for Developers][3])
   * Cómo obtener y usar el token OAuth con el scope `https://www.googleapis.com/auth/indexing` en PHP. ([Google for Developers][6])
   * Cuotas y límites (200 `publish`/día, etc.). ([Google for Developers][7])

2. **Fuentes de información** claras y oficiales:

   * Quickstart, Using the API, Reference, Prereqs, Authorize Requests, Quota & Pricing, Client Libraries (todas en developers.google.com). ([Google for Developers][1])
   * Repositorio `google-api-php-client` y su documentación (para la parte PHP). ([GitHub][10])

3. **Datos concretos de tu proyecto**:

   * Ruta del JSON, ruta y esquema de `sitemap.xml`, ubicación de los archivos de log, y si va a usar librería PHP oficial o `curl` directo.

Con eso se puede implementar los dos guiones PHP en `gestion/` (selección de URLs + logging por lote) y **acoplar después** la llamada real a Indexing API sin tocar la lógica de selección ni de registro.


[1]: https://developers.google.com/search/apis/indexing-api/v3/quickstart?utm_source=chatgpt.com "Indexing API Quickstart | Google Search Central"
[2]: https://developers.google.com/search/apis/indexing-api/v3/using-api?utm_source=chatgpt.com "How to Use the Indexing API | Google Search Central"
[3]: https://developers.google.com/search/apis/indexing-api/v3/reference/indexing/rest/v3/urlNotifications/publish?utm_source=chatgpt.com "Method: urlNotifications.publish | Indexing API"
[4]: https://developers.google.com/search/apis/indexing-api/v3/reference/indexing/rpc/google.indexing.v3?utm_source=chatgpt.com "Package google.indexing.v3 | Indexing API"
[5]: https://developers.google.com/search/apis/indexing-api/v3/authorizing?utm_source=chatgpt.com "Authorize Requests | Indexing API Documentation"
[6]: https://developers.google.com/search/apis/indexing-api/v3/prereqs?utm_source=chatgpt.com "Prerequisites for the Indexing API | Google Search Central"
[7]: https://developers.google.com/search/apis/indexing-api/v3/quota-pricing?utm_source=chatgpt.com "Requesting Approval and Quota | Indexing API ..."
[8]: https://support.google.com/webmasters/thread/109854223/indexing-api-of-google?hl=en&utm_source=chatgpt.com "Indexing API of Google - Google Search Central Community"
[9]: https://developers.google.com/search/apis/indexing-api/v3/libraries?utm_source=chatgpt.com "Install Client Libraries | Indexing API Documentation"
[10]: https://github.com/googleapis/google-api-php-client?utm_source=chatgpt.com "A PHP client library for accessing Google APIs"
[11]: https://googleapis.github.io/google-api-php-client/v2.8.3/Google_Client.html?utm_source=chatgpt.com "Google_Client | Google APIs Client Library for PHP API ..."
[12]: https://stackoverflow.com/questions/52177754/google-indexing-api?utm_source=chatgpt.com "php - Google Indexing API"
[13]: https://wiretuts.com/using-google-indexing-api-with-php-and-laravel/?utm_source=chatgpt.com "Using Google Indexing API with PHP and Laravel"
