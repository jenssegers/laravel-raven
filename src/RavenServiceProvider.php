<?php namespace Jenssegers\Raven;

use InvalidArgumentException;
use Raven_Client;
use Raven_ErrorHandler;
use Illuminate\Support\ServiceProvider;

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
     *
     * @return void
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
     *
     * @return void
     */
    public function register()
    {
        $app = $this->app;

        $this->app['raven.client'] = $this->app->share(function ($app)
        {
            $config = $app['config']->get('services.raven', []);

            $dsn = getenv('RAVEN_DSN') ?: $app['config']->get('services.raven.dsn');

            if ( ! $dsn)
            {
                throw new InvalidArgumentException('Raven DSN not configured');
            }

            // Use async by default.
            if (empty($config['curl_method']))
            {
                $config['curl_method'] = 'async';
            }

            return new Raven_Client($dsn, array_except($config, ['dsn']));
        });

        $this->app['raven.handler'] = $this->app->share(function ($app)
        {
            $level = $app['config']->get('services.raven.level', 'debug');

            return new RavenLogHandler($app['raven.client'], $app, $level);
        });

        if (isset($this->app['log']))
        {
            $this->registerListener();
        }

        // Register the fatal error handler.
        register_shutdown_function(function () use ($app)
        {
            if (isset($app['raven.client']))
            {
                (new Raven_ErrorHandler($app['raven.client']))->registerShutdownFunction();
            }
        });
    }

    /**
     * Register the log listener.
     *
     * @return void
     */
    protected function registerListener()
    {
        $app = $this->app;

        $this->app['log']->listen(function ($level, $message, $context) use ($app)
        {
            $app['raven.handler']->log($level, $message, $context);
        });

        $this->listenerRegistered = true;
    }

}
