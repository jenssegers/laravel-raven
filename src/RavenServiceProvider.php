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
            return new Raven_Client(Config::get('raven::dsn'));
        });
    }

    /**
     * Register error and log listeners.
     *
     * @return void
     */
    protected function registerListeners()
    {
        // Register error listener
        $this->app->error(function(Exception $exception)
        {
            if ( ! in_array(App::environment(), Config::get('raven::environments'))) return;

            $raven = App::make('raven');
            $raven->captureException($exception);
        });

        // Register log listener
        $this->app->log->listen(function($level, $message, $context)
        {
            if ( ! in_array(App::environment(), Config::get('raven::environments'))) return;

            $raven = App::make('raven');

            if ($message instanceof Exception)
            {
                $raven->captureException($message, array('level' => $level, 'extra' => $context));
            }
            else
            {
                $raven->captureMessage($message, array(), array('level' => $level, 'extra' => $context));
            }
        });
    }

}
