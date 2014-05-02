<?php namespace Jenssegers\Raven;

use App;
use Session;
use Queue;
use Request;
use Raven_Client;

class Raven extends Raven_Client {

    /**
     * Integrate Laravel Session data.
     *
     * @return array
     */
    protected function get_user_data()
    {
        if (is_null($this->context->user) and $session = Session::all())
        {
            $this->context->user = array(
                'data' => $session,
            );
        }

        return parent::get_user_data();
    }

    /**
     * Add additional tags.
     *
     * @return array
     */
    public function get_default_data()
    {
        $this->tags['environment'] = App::environment();
        $this->tags['ip'] = Request::getClientIp();

        return parent::get_default_data();
    }

    /**
     * {@inheritdoc}
     */
    public function send($data)
    {
        // Push the job to the queue instead of sending it to Sentry directly.
        Queue::push('Jenssegers\Raven\Job', $data);
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

}
