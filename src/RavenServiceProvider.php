<?php namespace Jenssegers\Raven;

use Exception;
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
     * Bootstrap the application events.
     *
     * @return void
     */
    public function boot()
    {
        $app = $this->app;

        // Listen to log messages.
        $app['log']->listen(function ($level, $message, $context) use ($app)
        {
            $app['raven.handler']->log($level, $message, $context);
        });
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
            $config = $app['config']->get('services.raven');

            if (empty($config['dsn']))
            {
                throw new InvalidArgumentException('Raven DSN not configured');
            }

            // Use async by default.
            if (empty($config['curl_method']))
            {
                $config['curl_method'] = 'async';
            }

            return new Raven_Client($config['dsn'], array_except($config, ['dsn']));
        });

        $this->app['raven.handler'] = $this->app->share(function ($app)
        {
            $level = $app['config']->get('services.raven.level', 'debug');

            return new RavenLogHandler($app['raven.client'], $app, $level);
        });

        register_shutdown_function(function () use ($app)
        {
            if (isset($app['raven.client']))
            {
                (new Raven_ErrorHandler($app['raven.client']))->handleFatalError();
            }
        });
    }

}
