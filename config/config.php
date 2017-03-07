<?php

return [
    // By default, API calls take the form of http://<server_name>/[<storage_route_prefix>/]<storage_service_name>/<file_path>
    'storage_route_prefix'         => env('DF_STORAGE_ROUTE_PREFIX'),
    'local_file_service_container' => trim(env('DF_LOCAL_FILE_ROOT', 'app'), '/'),
    // File chunk size for downloadable files in Byte. Default is 10MB
    'file_chunk_size'              => env('DF_FILE_CHUNK_SIZE', 10000000),
];
