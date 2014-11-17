<?php namespace Jenssegers\Raven;

use App;
use Session;
use Queue;
use Request;
use Raven_Client;
use Illuminate\Queue\QueueManager;

class Raven extends Raven_Client {

    /**
     * The queue manager instance.
     *
     * @var \Illuminate\Queue\QueueManager
     */
    protected $queue;

    /**
     * Constructor.
     */
    public function __construct($config = array(), QueueManager $queue = null)
    {
        // Prepare constructor values.
        $dsn = isset($config['dsn']) ? $config['dsn'] : '';
        $config = array_except($config, array('dsn'));

        parent::__construct($dsn, $config);

        $this->setQueue($queue);
    }

    /**
     * Set the queue manager instance.
     *
     * @param  \Illuminate\Queue\QueueManager  $queue
     * @return \Jenssegers\Rollbar\Rollbar
     */
    public function setQueue(QueueManager $queue = null)
    {
        $this->queue = $queue;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    protected function get_user_data()
    {
        $user = isset($this->context->user) ? $this->context->user : array();
        $session = Session::all();

        // Add Laravel session data
        if (isset($user['data']))
        {
            $user['data'] = array_merge($session, $user['data']);
        }
        else
        {
            $user['data'] = $session;
        }

        // Add session ID
        if ( ! isset($user['id']))
        {
            $user['id'] = Session::getId();
        }

        return array(
            'sentry.interfaces.User' => $user,
        );
    }

    /**
     * {@inheritdoc}
     */
    public function get_default_data()
    {
        // Add additional tags
        $this->tags['environment'] = App::environment();
        $this->tags['ip'] = Request::getClientIp();

        return parent::get_default_data();
    }

    /**
     * {@inheritdoc}
     */
    public function send($data)
    {
        // If we have a QueueManager instance, we will push the payload as
        // a job to the queue instead of sending it to Raven directly.
        if ($this->queue)
        {
            $this->queue->push('Jenssegers\Raven\Job', $data);
        }

        // Otherwise we will just execute the original send method.
        else
        {
            return parent::send($data);
        }
    }

    /**
     * Parse the given context information.
     *
     * @param  array  $context
     * @return array
     */
    public function parseContext($context = array())
    {
        // Set user context
        if (isset($context['user']))
        {
            $this->user_context($context['user']);
            unset($context['user']);
        }

        // Set extra context
        if ( ! isset($context['extra']))
        {
            $context['extra'] = array_except($context, array('user', 'tags', 'level'));
        }

        return $context;
    }

    /**
     * Send data from the queue job.
     *
     * @param  array $data
     * @return void
     */
    public function sendFromJob($data)
    {
        return parent::send($data);
    }

    /**
     * Allow camel case methods.
     *
     * @param  string $method
     * @param  array  $parameters
     * @return mixed
     */
    public function __call($method, $parameters)
    {
        return call_user_func_array(array($this, snake_case($method)), $parameters);
    }

}
