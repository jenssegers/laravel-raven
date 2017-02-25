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
        // Don't register raven if it is not configured.
        if (! getenv('RAVEN_DSN') and ! $this->app['config']->get('services.raven')) {
            return;
        }

        $this->app->singleton(Raven_Client::class, function ($app) {
            $config = $app['config']->get('services.raven', []);
            $dsn = getenv('RAVEN_DSN') ?: $app['config']->get('services.raven.dsn');

            if (! $dsn) {
                throw new InvalidArgumentException('Raven DSN not configured');
            }

            return new Raven_Client($dsn, array_except($config, ['dsn']));
        });

        $this->app->singleton(RavenHandler::class, function ($app) {
            $level = getenv('RAVEN_LEVEL') ?: $app['config']->get('services.raven.level', 'debug');
            $builder = new ContextBuilder($app);

            return new RavenHandler($app[Raven_Client::class], $builder, $level);
        });

        // Register the fatal error handler.
        register_shutdown_function(function () {
            if (isset($this->app[Raven_Client::class])) {
                (new Raven_ErrorHandler($this->app[Raven_Client::class]))->registerShutdownFunction();
            }
        });
    }

    /**
     * Register the log listener.
     */
    protected function registerListener()
    {
        if (method_exists($this->app['log'], 'listen')) {
            $this->app['log']->listen(function () {
                // Old Laravel way.
                if (func_num_args() == 3) {
                    list($level, $message, $context) = func_get_args();
                    $this->app[RavenHandler::class]->log($level, $message, $context);
                }

                // New Laravel way.
                $message = func_get_arg(0);
                $this->app[RavenHandler::class]->log($message->level, $message->message, $message->context);
            });
        }

        $this->listenerRegistered = true;
    }
}
