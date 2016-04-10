<?php namespace Jenssegers\Raven;

use Illuminate\Support\ServiceProvider;
use InvalidArgumentException;
use Raven_Client;
use Raven_ErrorHandler;

class RavenServiceProvider extends ServiceProvider
{
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
        if (! $this->listenerRegistered) {
            $this->registerListener();
        }
    }

    /**
     * Register the service provider.
     */
    public function register()
    {
        // Don't register rollbar if it is not configured.
        if (! getenv('RAVEN_DSN') and ! $this->app['config']->get('services.raven')) {
            return;
        }

        $this->app['Raven_Client'] = $this->app->share(function ($app) {
            $config = $app['config']->get('services.raven', []);
            $dsn = getenv('RAVEN_DSN') ?: $app['config']->get('services.raven.dsn');

            if (! $dsn) {
                throw new InvalidArgumentException('Raven DSN not configured');
            }

            return new Raven_Client($dsn, array_except($config, ['dsn']));
        });

        $this->app['Jenssegers\Raven\RavenHandler'] = $this->app->share(function ($app) {
            $level = getenv('RAVEN_LEVEL') ?: $app['config']->get('services.raven.level', 'debug');
            $builder = new ContextBuilder($app);

            return new RavenHandler($app['Raven_Client'], $builder, $level);
        });

        // Register log listeners for Laravel.
        if (isset($this->app['log'])) {
            $this->registerListener();
        }

        // Register the fatal error handler.
        register_shutdown_function(function () {
            if (isset($this->app['Raven_Client'])) {
                (new Raven_ErrorHandler($this->app['Raven_Client']))->registerShutdownFunction();
            }
        });
    }

    /**
     * Register the log listener.
     */
    protected function registerListener()
    {
        if (method_exists($this->app['log'], 'listen')) {
            $this->app['log']->listen(function ($level, $message, $context) {
                $this->app['Jenssegers\Raven\RavenHandler']->log($level, $message, $context);
            });
        }

        $this->listenerRegistered = true;
    }
}
