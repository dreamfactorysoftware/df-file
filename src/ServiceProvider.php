<?php

namespace DreamFactory\Core\File;

use DreamFactory\Core\Enums\ServiceTypeGroups;
use DreamFactory\Core\File\Models\FTPFileConfig;
use DreamFactory\Core\File\Models\SFTPFileConfig;
use DreamFactory\Core\File\Models\WebDAVFileConfig;
use DreamFactory\Core\File\Services\FTPFileService;
use DreamFactory\Core\File\Services\SFTPFileService;
use DreamFactory\Core\File\Models\LocalFileConfig;
use DreamFactory\Core\File\Services\LocalFileService;
use DreamFactory\Core\File\Services\WebDAVFileService;
use DreamFactory\Core\Services\ServiceManager;
use DreamFactory\Core\Services\ServiceType;

class ServiceProvider extends \Illuminate\Support\ServiceProvider
{
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

        // add routes
        /** @noinspection PhpUndefinedMethodInspection */
        if (!$this->app->routesAreCached()) {
            include __DIR__ . '/../routes/routes.php';
        }

        // add migrations, https://laravel.com/docs/5.4/packages#resources
        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');
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
        $this->app->resolving('df.service', function (ServiceManager $df){
            $df->addType(
                new ServiceType([
                    'name'            => 'local_file',
                    'label'           => 'Local File Storage',
                    'description'     => 'File service supporting the local file system.',
                    'group'           => ServiceTypeGroups::FILE,
                    'config_handler'  => LocalFileConfig::class,
                    'factory'         => function ($config){
                        return new LocalFileService($config);
                    },
                ])
            );

            $df->addType(
                new ServiceType([
                    'name'            => 'ftp_file',
                    'label'           => 'FTP File Storage',
                    'description'     => 'File service supporting the FTP protocol.',
                    'group'           => ServiceTypeGroups::FILE,
                    'config_handler'  => FTPFileConfig::class,
                    'factory'         => function ($config){
                        return new FTPFileService($config);
                    },
                ])
            );

            $df->addType(
                new ServiceType([
                    'name'            => 'sftp_file',
                    'label'           => 'SFTP File Storage',
                    'description'     => 'File service supporting the SFTP protocol.',
                    'group'           => ServiceTypeGroups::FILE,
                    'config_handler'  => SFTPFileConfig::class,
                    'factory'         => function ($config){
                        return new SFTPFileService($config);
                    },
                ])
            );

            $df->addType(
                new ServiceType([
                    'name'            => 'webdav_file',
                    'label'           => 'WebDAV File Storage',
                    'description'     => 'File service supporting WebDAV.',
                    'group'           => ServiceTypeGroups::FILE,
                    'config_handler'  => WebDAVFileConfig::class,
                    'factory'         => function ($config){
                        return new WebDAVFileService($config);
                    },
                ])
            );
        });
    }
}
