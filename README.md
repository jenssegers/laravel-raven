Laravel Raven
==============

[![Build Status](http://img.shields.io/travis/jenssegers/laravel-raven.svg)](https://travis-ci.org/jenssegers/laravel-raven) [![Coverage Status](http://img.shields.io/coveralls/jenssegers/laravel-raven.svg)](https://coveralls.io/r/jenssegers/laravel-raven)

Sentry (Raven) error monitoring integration for Laravel projects. This library adds a listener to Laravel's logging component. Laravel's auth and session information will be sent in to Sentry's user context, as well as some other helpful tags such as 'environment', 'server', 'php_version' and 'ip'.

![rollbar](https://media.getsentry.com/_static/279c18667e633e98e9cb1dcf504bc60f/getsentry/dist/stream.gif)

Installation
------------

Install using composer:

```
composer require jenssegers/raven
```

Add the service provider in `app/config/app.php`:

```php
Jenssegers\Raven\RavenServiceProvider::class,
```

If you only want to enable Sentry reporting for certain environments you can conditionally load the service provider in your `AppServiceProvider`:

```php
if ($this->app->environment('production')) {
    $this->app->register(Jenssegers\Raven\RavenServiceProvider::class::class);
}
```

Optional: register the Raven alias:

```php
'Raven'           => Jenssegers\Raven\Facades\Raven::class,
```

Configuration
-------------

This package supports configuration through environment variables and/or the services configuration file located in `app/config/services.php`:

```php
'raven' => [
    'dsn'   => env('RAVEN_DSN'),
    'level' => env('LOG_LEVEL'),
],
```

The level variable defines the minimum log level at which log messages are sent to Sentry. For development you could set this either to `debug` to send all log messages, or to `none` to sent no messages at all. For production you could set this to `error` so that all info and debug messages are ignored.

For more information about the possible configuration variables, check https://github.com/getsentry/raven-php

Usage in Laravel
----------------

In Laravel, the service provider will automatically hook into Laravel's logger and send all messages matching the log level to Sentry.

If you want to send exceptions to Sentry, simply use the `Log` facade:

```php
try {
	something();
} catch (\Exception $e) {
	Log::error($e);
}
```

For logging messages or exceptions, you can any of these methods: `debug`, `notice`, `warning`, `error`, `critical`, `alert` or `emergency`.

```php
Log::debug('Here is some debug information');
```

### Alternative usage

If you prefer dependency injection rather than calling the `Log` facade, you can typehint the `Jenssegers\Raven\RavenHandler` class in your controller methods:

```php
use Jenssegers\Raven\RavenHandler as Raven;

...

public function index(Request $request, Raven $raven)
{
	Raven::info('Request received!');
}
```

### Context informaton

The included context builder will automatically collect information about the current logged in user and the session information. If can pass additional user context information like this:

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

Usage in Lumen
--------------

Because Lumen uses a different way of handling logging, the service provider is unable to intercept actual exceptions. Instead you can use the included `Raven` facade. First add the following to your `bootstrap/app.php`:

```php
$app->register(Jenssegers\Raven\RavenServiceProvider::class);
```

Then add this to your exception handler in `app/Exceptions/Handler.php`:

```php
use Jenssegers\Raven\Facades\Raven;
```

And:

```php
public function report(Exception $e)
{
    if ($this->shouldReport($e)) {
        Raven::error($e);
    }

    parent::report($e);
}
```
