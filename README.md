Laravel Raven
==============

[![Build Status](http://img.shields.io/travis/jenssegers/laravel-raven.svg)](https://travis-ci.org/jenssegers/laravel-raven) [![Coverage Status](http://img.shields.io/coveralls/jenssegers/laravel-raven.svg)](https://coveralls.io/r/jenssegers/laravel-raven)

Sentry (Raven) error monitoring integration for Laravel projects. This library adds a listener to Laravel's logging component. Laravel's session information will be sent in to Sentry's user context, as well as some other helpful tags such as 'environment', 'server', and 'ip'.

![rollbar](https://www.getsentry.com/_static/getsentry/images/hero.png)

Installation
------------

Install using composer:

    composer require jenssegers/raven

Add the service provider in `app/config/app.php`:

    'Jenssegers\Raven\RavenServiceProvider',

Optional: register the Raven alias:

    'Raven'           => 'Jenssegers\Raven\Facades\Raven',

Configuration
-------------

This package supports configuration through the services configuration file located in `app/config/services.php`. All configuration variables will be directly passed to Raven:

    'raven' => array(
        'dsn'   => 'your-raven-dsn',
        'level' => 'error'
    ),

The level variable defines the minimum log level at which log messages are sent to Sentry. For development you could set this either to `debug` to send all log messages, or to `none` to sent no messages at all.

For more information about the possible configuration variables, check https://github.com/getsentry/raven-php

Usage
-----

To monitor exceptions, simply use the `Log` facade:

    App::error(function(Exception $exception, $code)
    {
        Log::error($exception);
    });

Your other log messages will also be sent to Sentry:

    Log::info('Here is some debug information');

### Context informaton

You can pass user information as context like this:

    Log::info('Something went wrong', [
        'user' => ['name' => 'John Doe', 'email' => 'john@doe.com']
    ]);

Or pass additional tags:

    Log::info('Something went wrong', [
        'tags' => ['state' => 1234]
    ]);

Or pass some extra information:

    Log::info('Something went wrong', [
        'download_size' => 3432425235
    ]);
