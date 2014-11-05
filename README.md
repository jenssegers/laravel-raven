Laravel Raven
==============

[![Build Status](http://img.shields.io/travis/jenssegers/laravel-raven.svg)](https://travis-ci.org/jenssegers/laravel-raven) [![Coverage Status](http://img.shields.io/coveralls/jenssegers/laravel-raven.svg)](https://coveralls.io/r/jenssegers/laravel-raven)

Sentry (Raven) error monitoring integration for Laravel projects. This library will add a listener to Laravel's logging component. All Sentry messages will be pushed onto Laravel's queue system, so that they can be processed in the background without slowing down the application. Laravel's session data will also be sent in the user context.

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

### Option 1: Services configuration file

This package supports configuration through the services configuration file located in `app/config/services.php`. All configuration variables will be directly passed to Raven:

    'raven' => array(
        'dsn' => 'your-raven-dsn',
    ),

### Option 2: The package configuration file

Publish the included configuration file:

    php artisan config:publish jenssegers/raven

And change your Sentry DSN:

    'dsn' => 'your-raven-dsn',

### Attention!

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
