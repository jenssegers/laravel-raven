<?php

class RavenTest extends Orchestra\Testbench\TestCase {

    public function setUp()
    {
        parent::setUp();

        $this->dsn = 'https://foo:bar@app.getsentry.com/12345';
        $this->app->config->set('services.raven.dsn', $this->dsn);
    }

    public function tearDown()
    {
        Mockery::close();

        parent::tearDown();
    }

    protected function getPackageProviders($app)
    {
        return ['Jenssegers\Raven\RavenServiceProvider'];
    }

    public function testBinding()
    {
        $client = $this->app->make('raven.client');
        $this->assertInstanceOf('Raven_Client', $client);

        $handler = $this->app->make('raven.handler');
        $this->assertInstanceOf('Jenssegers\Raven\RavenLogHandler', $handler);
    }

    public function testIsSingleton()
    {
        $handler1 = $this->app->make('raven.handler');
        $handler2 = $this->app->make('raven.handler');
        $this->assertEquals(spl_object_hash($handler1), spl_object_hash($handler2));
    }

    public function testFacade()
    {
        $client = Jenssegers\Raven\Facades\Raven::getFacadeRoot();
        $this->assertInstanceOf('Raven_Client', $client);
    }

    public function testNoConfiguration()
    {
        $this->setExpectedException('InvalidArgumentException');

        $this->app->config->set('services.raven.dsn', null);
        $client = $this->app->make('raven.client');
    }

    public function testPassConfiguration()
    {
        $client = $this->app->make('raven.client');
        $this->assertEquals('12345', $client->project);
        $this->assertEquals('foo', $client->public_key);
        $this->assertEquals('bar', $client->secret_key);
        $this->assertEquals(['https://app.getsentry.com/api/12345/store/'], $client->servers);
    }

    public function testCustomConfiguration()
    {
        $this->app->config->set('services.raven.name', 'foo');
        $this->app->config->set('services.raven.site', 'bar');
        $this->app->config->set('services.raven.tags', ['php_version' => phpversion()]);

        $client = $this->app->make('raven.client');
        $this->assertEquals('foo', $client->name);
        $this->assertEquals('bar', $client->site);
        $this->assertEquals(['php_version' => phpversion()], $client->tags);
    }

    public function testAutomaticContext()
    {
        $this->app->session->set('foo', 'bar');

        $clientMock = Mockery::mock('Raven_Client');
        $clientMock->shouldReceive('captureMessage')->once()->with('Test log message', [], [
            'level' => 'info',
            'user' => [
                'data' => ['foo' => 'bar'],
                'id' => $this->app->session->getId()
            ],
            'tags' => [
                'environment' => 'testing',
                'server' => 'localhost'
            ],
            'extra' => [
                'ip' => '127.0.0.1'
            ]
        ]);

        $handlerMock = Mockery::mock('Jenssegers\Raven\RavenLogHandler', [$clientMock, $this->app]);
        $handlerMock->shouldReceive('log')->passthru();
        $this->app['raven.handler'] = $handlerMock;

        $handler = $this->app->make('raven.handler');
        $handler->log('info', 'Test log message');
    }

    public function testMergedContext()
    {
        $this->app->session->set('foo', 'bar');

        $clientMock = Mockery::mock('Raven_Client');
        $clientMock->shouldReceive('captureMessage')->once()->with('Test log message', [], [
            'level' => 'info',
            'user' => [
                'email' => 'john@doe.com',
                'data' => ['foo' => 'bar'],
                'id' => 1337
            ],
            'tags' => [
                'environment' => 'testing',
                'server' => 'localhost',
                'one' => 'two'
            ],
            'extra' => [
                'ip' => '127.0.0.1'
            ]
        ]);

        $handlerMock = Mockery::mock('Jenssegers\Raven\RavenLogHandler', [$clientMock, $this->app]);
        $handlerMock->shouldReceive('log')->passthru();
        $this->app['raven.handler'] = $handlerMock;

        $handler = $this->app->make('raven.handler');
        $handler->log('info', 'Test log message', [
            'tags' => ['one' => 'two'],
            'user' => ['id' => 1337, 'email' => 'john@doe.com']
        ]);
    }

    public function testEasyExtraData()
    {
        $clientMock = Mockery::mock('Raven_Client');
        $clientMock->shouldReceive('captureMessage')->once()->with('Test log message', [], [
            'level' => 'info',
            'tags' => [
                'environment' => 'testing',
                'server' => 'localhost',
            ],
            'extra' => [
                'ip' => '127.0.0.1',
                'download_size' => 3432425235
            ]
        ]);

        $handlerMock = Mockery::mock('Jenssegers\Raven\RavenLogHandler', [$clientMock, $this->app]);
        $handlerMock->shouldReceive('log')->passthru();
        $this->app['raven.handler'] = $handlerMock;

        $handler = $this->app->make('raven.handler');
        $handler->log('info', 'Test log message', ['download_size' => 3432425235]);
    }

    public function testLogListener()
    {
        $exception = new Exception('Testing error handler');

        $clientMock = Mockery::mock('Raven_Client');
        $clientMock->shouldReceive('captureMessage')->times(2);
        $clientMock->shouldReceive('captureException')->times(1)->with($exception, [
            'level' => 'error',
            'tags' => [
                'environment' => 'testing',
                'server' => 'localhost'
            ],
            'extra' => [
                'ip' => '127.0.0.1'
            ]
        ]);

        $handlerMock = Mockery::mock('Jenssegers\Raven\RavenLogHandler', [$clientMock, $this->app]);
        $handlerMock->shouldReceive('log')->passthru();
        $this->app['raven.handler'] = $handlerMock;

        $this->app->log->info('hello');
        $this->app->log->error('oops');
        $this->app->log->error($exception);
    }

    public function testBelowLevel()
    {
        $this->app->config->set('services.raven.level', 'error');

        $clientMock = Mockery::mock('Raven_Client');
        $clientMock->shouldReceive('captureMessage')->times(0);
        $this->app['raven.client'] = $clientMock;

        $this->app->log->info('hello');
        $this->app->log->debug('hello');
        $this->app->log->notice('hello');
        $this->app->log->warning('hello');
    }

    public function testAboveLevel()
    {
        $this->app->config->set('services.raven.level', 'error');

        $clientMock = Mockery::mock('Raven_Client');
        $clientMock->shouldReceive('captureMessage')->times(4);
        $this->app['raven.client'] = $clientMock;

        $this->app->log->error('hello');
        $this->app->log->critical('hello');
        $this->app->log->alert('hello');
        $this->app->log->emergency('hello');
    }

}
