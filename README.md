Laravel Raven
==============

[![Build Status](https://travis-ci.org/jenssegers/Laravel-Raven.svg)](https://travis-ci.org/jenssegers/Laravel-Raven) [![Coverage Status](https://coveralls.io/repos/jenssegers/Laravel-Raven/badge.png)](https://coveralls.io/r/jenssegers/Laravel-Raven)

Sentry/Raven error monitoring integration for Laravel projects.

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

Usage
-----

The service provider will make sure all your exceptions and log messages are passed to Sentry automatically.

    throw new Exception('Something went wrong');

Or if you want to directly send the exception object:

    Log::error(new Exception('Something went wrong'));

Your log messages will also be sent to Sentry:

    Log::info('Hello Sentry', array('context'));
