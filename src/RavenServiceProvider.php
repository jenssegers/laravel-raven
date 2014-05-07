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
        $this->app->bindShared('raven', function($app)
        {
            // Get the Raven config
            $config = array_except(Config::get('raven::config'), array('dsn'));

            return new Raven(Config::get('raven::dsn'), $config);
        });
    }

    /**
     * Register error and log listeners.
     *
     * @return void
     */
    protected function registerListeners()
    {
        // Register log listener
        $this->app->log->listen(function($level, $message, $context)
        {
            $raven = App::make('raven');

            // Prepare the context
            $context = $raven->parseContext($context);
            $context['level'] = $level;

            if ($message instanceof Exception)
            {
                if (!in_array($level, ['debug', 'info', 'notice']))
                    $raven->captureException($message, $context);
            }
            else
            {
                if (!in_array($level, ['debug', 'info', 'notice']))
                    $raven->captureMessage($message, array(), $context);
            }
        });

        // Register after filter
        $this->app->after(function()
        {
            $raven = App::make('raven');
            $raven->sendUnsentErrors();
        });
    }

}
