Laravel Raven
==============

[![Build Status](https://travis-ci.org/jenssegers/Laravel-Raven.svg)](https://travis-ci.org/jenssegers/Laravel-Raven) [![Coverage Status](https://coveralls.io/repos/jenssegers/Laravel-Raven/badge.png)](https://coveralls.io/r/jenssegers/Laravel-Raven)

Sentry (Raven) error monitoring integration for Laravel projects. This library will add a listener to Laravel's logging component. All Sentry messages will be pushed onto Laravel's queue system, so that they can be processed in the background without slowing down the application.

![rollbar](https://www.getsentry.com/_static/getsentry/images/hero.png)

Installation
------------

Add the package to your `composer.json` and run `composer update`.

    {
        "require": {
            "jenssegers/raven": "*"
        }
    }

Add the service provider in `app/config/app.php`:

    'Jenssegers\Raven\RavenServiceProvider',

Optional: register the Raven alias:

    'Raven'           => 'Jenssegers\Raven\Facades\Raven',

Configuration
-------------

Publish the included configuration file:

    php artisan config:publish jenssegers/raven

And change your Sentry DSN:

    'dsn' => '',

Because this library uses the queue system, make sure your `config/queue.php` file is configured correctly. If you do not wish to process the jobs in the background, you can set the queue driver to 'sync':

    'default' => 'sync',

Usage
-----

To monitor exceptions, simply use the `Log` facade:

    App::error(function(Exception $exception, $code)
    {
        Log::error($exception);
    });

Your other log messages will also be sent to Sentry:

    Log::info('Here is some debug information', array('context'));
