<?php

/*
|--------------------------------------------------------------------------
| Storage Services Routes
|--------------------------------------------------------------------------
|
| These routes give URL access to folders declared public in your file
| service's configuration.
|
*/

Route::prefix(config('df.storage_route_prefix'))
    ->middleware('df.cors')
    ->group(function () {
        $resourcePathPattern = '[0-9a-zA-Z-_@&\#\!=,:;\/\^\$\.\|\{\}\[\]\(\)\*\+\? ]+';
        $servicePattern = '[_0-9a-zA-Z-.]+';
        $controller = 'DreamFactory\Core\File\Http\Controllers\StorageController';

        Route::get('{storage}/{path}', $controller . '@streamFile')->where(
            ['storage' => $servicePattern, 'path' => $resourcePathPattern]
        );
    }
    );