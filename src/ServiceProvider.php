<?php
namespace DreamFactory\Core\File;

use DreamFactory\Core\Components\ServiceDocBuilder;
use DreamFactory\Core\Enums\ServiceTypeGroups;
use DreamFactory\Core\Handlers\Events\ServiceEventHandler;
use DreamFactory\Core\File\Models\LocalFileConfig;
use DreamFactory\Core\File\Services\LocalFileService;
use DreamFactory\Core\Services\ServiceManager;
use DreamFactory\Core\Services\ServiceType;
use Event;

class ServiceProvider extends \Illuminate\Support\ServiceProvider
{
    use ServiceDocBuilder;

    /**
     * Bootstrap the application events.
     *
     */
    public function boot()
    {
        // add our df config
        $configPath = __DIR__ . '/../config/config.php';
        if (function_exists('config_path')) {
            $publishPath = config_path('df.php');
        } else {
            $publishPath = base_path('config/df.php');
        }
        $this->publishes([$configPath => $publishPath], 'config');

        // add migrations, https://laravel.com/docs/5.4/packages#resources
        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');

        // subscribe to all listened to events
        Event::subscribe(new ServiceEventHandler());
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        // merge in df config, https://laravel.com/docs/5.4/packages#resources
        $configPath = __DIR__ . '/../config/config.php';
        $this->mergeConfigFrom($configPath, 'df');

        // Add our service types.
        $this->app->resolving('df.service', function (ServiceManager $df) {
            $df->addType(
                new ServiceType([
                    'name'            => 'local_file',
                    'label'           => 'Local File Storage',
                    'description'     => 'File service supporting the local file system.',
                    'group'           => ServiceTypeGroups::FILE,
                    'config_handler'  => LocalFileConfig::class,
                    'default_api_doc' => function ($service) {
                        return $this->buildServiceDoc($service->id, LocalFileService::getApiDocInfo($service));
                    },
                    'factory'         => function ($config) {
                        return new LocalFileService($config);
                    },
                ])
            );
        });
    }
}
