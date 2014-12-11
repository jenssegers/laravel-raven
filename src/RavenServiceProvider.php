<?php namespace Jenssegers\Raven;

use Exception, InvalidArgumentException;
use Raven_Client;
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
        // Fix for PSR-4
        $this->package('jenssegers/raven', 'raven', realpath(__DIR__));

        $app = $this->app;

        // Listen to log messages.
        $app['log']->listen(function($level, $message, $context) use ($app)
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

        $this->app['raven.client'] = $this->app->share(function($app)
        {
            $config = $app['config']->get('services.raven');

            if (empty($config['dsn']))
            {
                throw new InvalidArgumentException('Raven DSN not configured');
            }

            $options = array_except($config, array('dsn'));

            return new Raven_Client($config['dsn'], $options);
        });

        $this->app['raven.handler'] = $this->app->share(function($app)
        {
            $client = $app['raven.client'];

            $level = $app['config']->get('services.raven.level', 'debug');

            return new RavenLogHandler($client, $app, $level);
        });
    }

}
