<?php namespace Jenssegers\Raven;

use Exception;
use Illuminate\Foundation\Application;
use InvalidArgumentException;
use Monolog\Logger as Monolog;
use Psr\Log\AbstractLogger;
use Raven_Client;

class RavenLogHandler extends AbstractLogger
{
    /**
     * The raven client instance.
     *
     * @var Raven_Client
     */
    protected $raven;

    /**
     * The Laravel application.
     *
     * @var \Illuminate\Foundation\Application
     */
    protected $app;

    /**
     * The minimum log level at which messages are sent to Sentry.
     *
     * @var string
     */
    protected $level;

    /**
     * The Log levels.
     *
     * @var array
     */
    protected $levels = [
        'debug'     => Monolog::DEBUG,
        'info'      => Monolog::INFO,
        'notice'    => Monolog::NOTICE,
        'warning'   => Monolog::WARNING,
        'error'     => Monolog::ERROR,
        'critical'  => Monolog::CRITICAL,
        'alert'     => Monolog::ALERT,
        'emergency' => Monolog::EMERGENCY,
        'none'      => 1000,
    ];

    /**
     * Constructor.
     */
    public function __construct(Raven_Client $raven, Application $app, $level = 'debug')
    {
        $this->raven = $raven;

        $this->app = $app;

        $this->level = $this->parseLevel($level ?: 'debug');
    }

    /**
     * Log a message to Sentry.
     *
     * @param mixed  $level
     * @param string $message
     * @param array  $context
     */
    public function log($level, $message, array $context = [])
    {
        // Check if we want to log this message.
        if ($this->parseLevel($level) < $this->level) {
            return;
        }

        // Put level in context.
        $context['level'] = $level;
        $context = $this->addContext($context);

        if ($message instanceof Exception) {
            $this->raven->captureException($message, $context);
        } else {
            $this->raven->captureMessage($message, [], $context);
        }
    }

    /**
     * Add Laravel specific information to the context.
     *
     * @param array $context
     */
    protected function addContext(array $context = [])
    {
        // Add session data.
        if ($session = $this->app->session->all()) {
            if (empty($context['user']) or ! is_array($context['user'])) {
                $context['user'] = [];
            }

            if (isset($context['user']['data'])) {
                $context['user']['data'] = array_merge($session, $context['user']['data']);
            } else {
                $context['user']['data'] = $session;
            }

            // User session id as user id if not set.
            if (! isset($context['user']['id'])) {
                $context['user']['id'] = $this->app->session->getId();
            }
        }

        // Automatic tags
        $tags = [
            'environment' => $this->app->environment(),
            'server'      => $this->app->request->server('HTTP_HOST'),
        ];

        // Add tags to context.
        if (isset($context['tags'])) {
            $context['tags'] = array_merge($tags, $context['tags']);
        } else {
            $context['tags'] = $tags;
        }

        // Automatic extra data.
        $extra = [
            'ip' => $this->app->request->getClientIp(),
        ];

        // Everything that is not 'user', 'tags' or 'level' is automatically considered
        // as additonal 'extra' context data.
        $extra = array_merge($extra, array_except($context, ['user', 'tags', 'level', 'extra']));

        // Add extra to context.
        if (isset($context['extra'])) {
            $context['extra'] = array_merge($extra, $context['extra']);
        } else {
            $context['extra'] = $extra;
        }

        // Clean out other values from context.
        $context = array_only($context, ['user', 'tags', 'level', 'extra']);

        return $context;
    }

    /**
     * Parse the string level into a Monolog constant.
     *
     * @param  string  $level
     * @return int
     *
     * @throws \InvalidArgumentException
     */
    protected function parseLevel($level)
    {
        if (isset($this->levels[$level])) {
            return $this->levels[$level];
        }

        throw new InvalidArgumentException('Invalid log level: ' . $level);
    }
}
