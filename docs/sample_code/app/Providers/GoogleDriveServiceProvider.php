<?php

namespace App\Providers;

use App\Filesystem\GoogleDriveFilesystemAdapter;
use Google\Client;
use Google\Service\Drive;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\ServiceProvider;
use League\Flysystem\Filesystem;
use Masbug\Flysystem\GoogleDriveAdapter;

class GoogleDriveServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Storage::extend('google', function ($app, array $config) {
            $options = [];

            if (! empty($config['teamDriveId'] ?? null)) {
                $options['teamDriveId'] = $config['teamDriveId'];
            }

            if (! empty($config['sharedFolderId'] ?? null)) {
                $options['sharedFolderId'] = $config['sharedFolderId'];
            }

            $client = new Client;
            $client->setClientId($config['clientId']);
            $client->setClientSecret($config['clientSecret']);
            $client->refreshToken($config['refreshToken']);
            $client->setApplicationName(config('app.name', 'Laravel'));

            $service = new Drive($client);
            $adapter = new GoogleDriveAdapter(
                $service,
                $config['folder'] ?? '/',
                $options
            );
            $driver = new Filesystem($adapter);

            return new GoogleDriveFilesystemAdapter($driver, $adapter, $config);
        });
    }
}
