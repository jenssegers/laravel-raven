<?php namespace Jenssegers\Raven;

use App;
use Request;
use Session;
use Monolog\Logger;
use Monolog\Handler\RavenHandler;
use Monolog\Formatter\LineFormatter;
use Monolog\Handler\AbstractProcessingHandler;

class Handler extends RavenHandler {

    /**
     * @param integer      $level       The minimum logging level at which this handler will be triggered
     * @param Boolean      $bubble      Whether the messages that are handled can bubble up the stack or not
     */
    public function __construct($level = Logger::DEBUG, $bubble = true)
    {
        // Skip RavenHandler constructor.
        AbstractProcessingHandler::__construct($level, $bubble);
    }

    /**
     * {@inheritdoc}
     */
    protected function write(array $record)
    {
        // Only instantiate the raven client when write is called.
        $this->ravenClient = App::make('raven');

        // Get user information from context.
        $user = array();
        if (isset($record['context']['user']))
        {
            $user = $record['context']['user'];
            unset($record['context']['user']);
        }

        // Add Laravel session data.
        $user['id'] = isset($user['id']) ? $user['id'] : Session::getId();
        $user['data'] = isset($user['data']) ? array_merge(Session::all(), $user['data']) : Session::all();

        // Set user context.
        $this->ravenClient->user_context($user);

        // Additional tags.
        $tags = array(
            'environment' => App::environment(),
            'ip' => Request::getClientIp()
        );

        // Add tags to record.
        if (isset($record['context']['tags']))
        {
            $record['context']['tags'] = array_merge($tags, $record['context']['tags']);
        }
        else
        {
            $record['context']['tags'] = $tags;
        }

        return parent::write($record);
    }

    /**
     * {@inheritDoc}
     */
    protected function getDefaultFormatter()
    {
        return new LineFormatter("%message%");
    }

}
