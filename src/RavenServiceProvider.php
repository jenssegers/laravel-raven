<?php namespace Jenssegers\Raven;

use App;
use Config;
use Raven_Client;
use Exception;
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

        // Register listeners
        $this->registerListeners();
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->app['raven'] = $this->app->share(function($app)
        {
            // Get configuration
            $config = $app['config']->get('services.raven') ?: $app['config']->get('raven::config');

            return new Raven($config, $app['queue']);
        });
    }

    /**
     * Register error and log listeners.
     *
     * @return void
     */
    protected function registerListeners()
    {
        $app = $this->app;

        // Get configuration
        $config = $app['config']->get('services.raven') ?: $app['config']->get('raven::config');

        // If raven is enabled, register log listener and after filter
        if ($config['enabled'] == true)
        {
            // Register log listener
            $app['log']->listen(function($level, $message, $context) use ($app)
            {
                $raven = $app['raven'];

                // Prepare the context
                $context = $raven->parseContext($context);
                $context['level'] = $level;

                if ($message instanceof Exception)
                {
                    $raven->captureException($message, $context);
                }
                else
                {
                    $raven->captureMessage($message, array(), $context);
                }
            });

            // Register after filter
            $app['router']->after(function ($request, $response) use ($app)
            {
                    $raven = $app['raven'];
                    $raven->sendUnsentErrors();
                }
            );
        }
    }

}
