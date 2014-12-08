<?php namespace Jenssegers\Raven;

use Raven_Client, Exception;
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

            $dsn = isset($config['dsn']) ? $config['dsn'] : '';

            $config = array_except($config, array('dsn'));

            return new Raven_Client($dsn, $config);
        });

        $this->app['raven.handler'] = $this->app->share(function($app)
        {
            $client = $app['raven.client'];

            $level = $app['config']->get('services.raven.level', 'debug');

            var_dump($level);

            return new RavenLogHandler($client, $app, $level);
        });
    }

}
