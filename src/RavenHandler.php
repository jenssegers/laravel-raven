<?php namespace Jenssegers\Raven;

use Exception;
use InvalidArgumentException;
use Monolog\Logger as Monolog;
use Psr\Log\AbstractLogger;
use Raven_Client;

class RavenHandler extends AbstractLogger
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
     * @var \Illuminate\Container\Container
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
    public function __construct(Raven_Client $raven, ContextBuilder $context = null, $level = 'debug')
    {
        $this->raven = $raven;

        $this->context = $context;

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

        // Merge context from context builder.
        if ($this->context) {
            $context = $this->context->build($context);
        }

        if ($message instanceof Exception) {
            $this->raven->captureException($message, $context);
        } else {
            $this->raven->captureMessage($message, [], $context);
        }
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
