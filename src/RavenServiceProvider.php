<?php namespace Jenssegers\Raven;

use Illuminate\Support\ServiceProvider;
use InvalidArgumentException;
use Raven_Client;
use Raven_ErrorHandler;

class RavenServiceProvider extends ServiceProvider {

    /**
     * Indicates if loading of the provider is deferred.
     *
     * @var bool
     */
    protected $defer = false;

    /**
     * Indicates if the log listener is registered.
     *
     * @var bool
     */
    protected $listenerRegistered = false;

    /**
     * Bootstrap the application events.
     */
    public function boot()
    {
        if ( ! $this->listenerRegistered)
        {
            $this->registerListener();
        }
    }

    /**
     * Register the service provider.
     */
    public function register()
    {
        $app = $this->app;
        $dsn = getenv('RAVEN_DSN') ?: $app['config']->get('services.raven.dsn');

        if (!$dsn) {
            return null;
        }

        $this->app['Raven_Client'] = $this->app->share(function ($app) use ($dsn)
        {
            $defaults = [
                'curl_method' => 'async',
            ];

            $config = array_merge($defaults, $app['config']->get('services.raven', []));
            return new Raven_Client($dsn, array_except($config, ['dsn']));
        });

        $this->app['Jenssegers\Raven\RavenLogHandler'] = $this->app->share(function ($app)
        {
            $level = getenv('RAVEN_LEVEL') ?: $app['config']->get('services.raven.level', 'debug');

            return new RavenLogHandler($app['Raven_Client'], $app, $level);
        });

        if (isset($this->app['log']))
        {
            $this->registerListener();
        }

        // Register the fatal error handler.
        register_shutdown_function(function () use ($app)
        {
            if (isset($app['Raven_Client']))
            {
                (new Raven_ErrorHandler($app['Raven_Client']))->registerShutdownFunction();
            }
        });
    }

    /**
     * Register the log listener.
     */
    protected function registerListener()
    {
        $app = $this->app;

        $this->app['log']->listen(function ($level, $message, $context) use ($app)
        {
            $app['Jenssegers\Raven\RavenLogHandler']->log($level, $message, $context);
        });

        $this->listenerRegistered = true;
    }

}
