<?php namespace Jenssegers\Raven;

use App;

class Job {

    /**
     * Fire the job.
     *
     * @return void
     */
    public function fire($job, $data)
    {
        // Get the Raven instance.
        $raven = App::make('raven');

        // Send the data to Sentry.
        $raven->sendFromJob($data);

        // Delete the processed job.
        $job->delete();
    }

}
