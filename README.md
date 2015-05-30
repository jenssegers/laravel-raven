Laravel Raven
==============

[![Build Status](http://img.shields.io/travis/jenssegers/laravel-raven.svg)](https://travis-ci.org/jenssegers/laravel-raven) [![Coverage Status](http://img.shields.io/coveralls/jenssegers/laravel-raven.svg)](https://coveralls.io/r/jenssegers/laravel-raven)

Sentry (Raven) error monitoring integration for Laravel projects. This library adds a listener to Laravel's logging component. Laravel's session information will be sent in to Sentry's user context, as well as some other helpful tags such as 'environment', 'server', and 'ip'.

![rollbar](https://www.getsentry.com/_static/getsentry/images/hero.png)

Installation
------------

Install using composer:

```
composer require jenssegers/raven
```

Add the service provider in `app/config/app.php`:

```php
'Jenssegers\Raven\RavenServiceProvider',
```

Optional: register the Raven alias:

```php
'Raven'           => 'Jenssegers\Raven\Facades\Raven',
```

Configuration
-------------

This package supports configuration through the services configuration file located in `app/config/services.php`. All configuration variables will be directly passed to Raven:

```php
'raven' => [
    'dsn'   => 'your-raven-dsn',
    'level' => 'debug'
],
```

**NOTE:** You can also set the DSN using the `RAVEN_DSN` environment variable.

The level variable defines the minimum log level at which log messages are sent to Sentry. For development you could set this either to `debug` to send all log messages, or to `none` to sent no messages at all. For production you could set this to `error` so that all info and debug messages are ignored.

The Raven client is initiated with `curl_method` in `async` mode by default for best-effort asynchronous submissions. If you wish to change this behavior, then you can set `curl_method` to `sync` in your configuration array.

For more information about the possible configuration variables, check https://github.com/getsentry/raven-php

Usage
-----

To automatically monitor exceptions, simply use the `Log` facade in your error handler in `app/Exceptions/Handler.php`:

```php
public function report(Exception $e)
{
    Log::error($e);

    return parent::report($e);
}
```

For Laravel 4 installations, this is located in `app/start/global.php`:

```php
App::error(function(Exception $exception, $code)
{
    Log::error($exception);
});
```

Your other log messages will also be sent to Sentry:

```php
Log::debug('Here is some debug information');
```

### Context informaton

You can pass user information as context like this:

```php
Log::error('Something went wrong', [
    'user' => ['name' => 'John Doe', 'email' => 'john@doe.com']
]);
```

Or pass additional tags:

```php
Log::info('Task completed', [
    'tags' => ['state' => 1234]
]);
```

Or pass some extra information:

```php
Log::warning('Something went wrong', [
    'download_size' => 3432425235
]);
```
