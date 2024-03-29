<?php


use Stevebauman\Location\LocationServiceProvider;

require_once __DIR__.'/../vendor/autoload.php';

$file = 'sextingfinder.env';

(new Laravel\Lumen\Bootstrap\LoadEnvironmentVariables(
    dirname(__DIR__), $file
))->bootstrap();

// date_default_timezone_set(env('APP_TIMEZONE', 'UTC')); // new on v7.x not used in a.sextingfinder

/*
|--------------------------------------------------------------------------
| Create The Application
|--------------------------------------------------------------------------
|
| Here we will load the environment and create the application instance
| that serves as the central piece of this framework. We'll use this
| application as an "IoC" container and router for this framework.
|
*/

$app = new Laravel\Lumen\Application(
    dirname(__DIR__)
);

$app->withFacades();

$app->withEloquent();

/*
|--------------------------------------------------------------------------
| Register Container Bindings
|--------------------------------------------------------------------------
|
| Now we will register a few bindings in the service container. We will
| register the exception handler and the console kernel. You may add
| your own bindings here if you like or you can make another file.
|
*/

$app->singleton(
    Illuminate\Contracts\Debug\ExceptionHandler::class,
    App\Exceptions\Handler::class
);

$app->singleton(
    Illuminate\Contracts\Console\Kernel::class,
    App\Console\Kernel::class
);

/*
|--------------------------------------------------------------------------
| Register Config Files
|--------------------------------------------------------------------------
|
| Now we will register the "app" configuration file. If the file exists in
| your configuration directory it will be loaded; otherwise, we'll load
| the default version. You may register other files below as needed.
|
*/

// $app->configure('app');

/*
|--------------------------------------------------------------------------
| Register Middleware
|--------------------------------------------------------------------------
|
| Next, we will register the middleware with the application. These can
| be global middleware that run before and after each request into a
| route or middleware that'll be assigned to some specific routes.
|
*/

$app->middleware([
    App\Http\Middleware\CorsMiddleware::class
]);
// in Laravel v10.x => $app->middlewareAliases([
$app->routeMiddleware([
    'jwt.auth' => App\Http\Middleware\JwtMiddleware::class,
    'jwt.auth.optional' => App\Http\Middleware\JwtMiddlewareOptional::class,
    'APIkey' => App\Http\Middleware\APIkey::class,
    'Admin' => App\Http\Middleware\Admin::class,
    'check_ip_ban' => App\Http\Middleware\CheckIpBan::class,
]);


/*
|--------------------------------------------------------------------------
| Register Service Providers
|--------------------------------------------------------------------------
|
| Here we will register all of the application's service providers which
| are used to bind services into the container. Service providers are
| totally optional, so you are not required to uncomment this line.
|
*/

// https://stackoverflow.com/questions/42606575/auth-guard-driver-api-is-not-defined-lumen-dingo-jwtauth
$app->register(App\Providers\AppServiceProvider::class);
// $app->register(Nord\Lumen\Cors\CorsServiceProvider::class);
$app->register(Sentry\Laravel\ServiceProvider::class);
// $app->register(App\Providers\AuthServiceProvider::class);
$app->register(App\Providers\EventServiceProvider::class);
$app->register(SocialiteProviders\Manager\ServiceProvider::class);
$app->register(App\Providers\BroadcastServiceProvider::class);
$app->register(App\Providers\MonocleServiceProvider::class);
$app->register(Illuminate\Database\MigrationServiceProvider::class);
$app->register(Illuminate\Redis\RedisServiceProvider::class);
$app->register(Illuminate\Mail\MailServiceProvider::class);
// $app->register(AlbertCht\InvisibleReCaptcha\InvisibleReCaptchaServiceProvider::class);
$app->register(Stevebauman\Location\LocationServiceProvider::class);
$app->register(GeneaLabs\LaravelModelCaching\Providers\Service::class);

if (env('APP_ENV') == 'local') {
    // $app->register(Krlove\EloquentModelGenerator\Provider\GeneratorServiceProvider::class);
    $app->register(Lanin\Laravel\ApiDebugger\ServiceProvider::class);
}

$app->configure('services');
$app->configure('database');
$app->configure('laravel-model-caching');
$app->configure('location');

$app->configure('mail');
$app->alias('mailer', Illuminate\Mail\Mailer::class);
$app->alias('mailer', Illuminate\Contracts\Mail\Mailer::class);
$app->alias('mailer', Illuminate\Contracts\Mail\MailQueue::class);



/*
|--------------------------------------------------------------------------
| Load The Application Routes
|--------------------------------------------------------------------------
|
| Next we will include the routes file so that they can all be added to
| the application. This will provide all of the URLs the application
| can respond to, as well as the controllers that may handle them.
|
*/

$app->router->group([
    'namespace' => 'App\Http\Controllers',
], function ($router) {
    require __DIR__.'/../routes/web.php';
});

return $app;
