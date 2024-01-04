<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Services\MonocleService;

class MonocleServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->app->singleton(MonocleService::class, function ($app) {
            $privateKey = file_get_contents(storage_path('monocle-key.pem'));
            return new MonocleService($privateKey);
        });
    }
}
