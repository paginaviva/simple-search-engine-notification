<?php
// Accesorio de prueba: página que devuelve 404 (para probar isUrlGone)
http_response_code(404);
echo 'gone';
