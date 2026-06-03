<?php

return [
    /*
    |--------------------------------------------------------------------------
    | GC Domotic upload root
    |--------------------------------------------------------------------------
    |
    | Las imágenes del sistema se guardan fuera del build cuando GC_UPLOAD_ROOT
    | está definido. En contenedores/EasyPanel debe apuntar a un volumen
    | persistente para que los archivos no se pierdan en cada implementación.
    |
    */
    'root' => env('GC_UPLOAD_ROOT') ?: base_path('storage'),
];
