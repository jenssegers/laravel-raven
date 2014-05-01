<?php

class ServiceProviderTest extends Orchestra\Testbench\TestCase {

    public function setUp()
    {
        parent::setUp();
        Config::set('raven::environments', array('testing'));
    }

    public function tearDown()
    {
        Mockery::close();
    }

    protected function getPackageProviders()
    {
        return array('Jenssegers\Raven\RavenServiceProvider');
    }

    public function testBinding()
    {
        $raven = App::make('raven');
        $this->assertInstanceOf('Raven_Client', $raven);
    }

    public function testFacade()
    {
        $raven = Jenssegers\Raven\Facades\Raven::getFacadeRoot();
        $this->assertInstanceOf('Raven_Client', $raven);
    }

    public function testPassConfiguration()
    {
        Config::set('raven::dsn', 'https://foo:bar@app.getsentry.com/12345');

        $raven = App::make('raven');
        $this->assertEquals('12345', $raven->project);
        $this->assertEquals('foo', $raven->public_key);
        $this->assertEquals('bar', $raven->secret_key);
        $this->assertEquals(array('https://app.getsentry.com/api/store/'), $raven->servers);
    }

    public function testIsSingleton()
    {
        $raven1 = App::make('raven');
        $raven2 = App::make('raven');
        $this->assertEquals(spl_object_hash($raven1), spl_object_hash($raven2));
    }

    public function testRegisterErrorListener()
    {
        $exception = new Exception('Testing error handler');

        $mock = Mockery::mock('Raven_Client');
        $mock->shouldReceive('captureException')->once()->with($exception);
        $this->app->instance('raven', $mock);

        $handler = $this->app->exception;
        $response = (string) $handler->handleException($exception);
    }

    public function testRegisterLogListener()
    {
        $exception = new Exception('Testing error handler');

        $mock = Mockery::mock('Raven_Client');
        $mock->shouldReceive('captureMessage')->once()->with('hello', array(), array('level' => 'info', 'extra' => array()));
        $mock->shouldReceive('captureMessage')->once()->with('oops', array(), array('level' => 'error', 'extra' => array('context')));
        $mock->shouldReceive('captureException')->once()->with($exception, array('level' => 'error', 'extra' => array()));
        $this->app->instance('raven', $mock);

        Log::info('hello');
        Log::error('oops', array('context'));
        Log::error($exception);
    }

    public function testEnvironments()
    {
        Config::set('raven::environments', array('production', 'local', 'staging'));
        $this->app['env'] = 'local';

        $mock = Mockery::mock('Raven_Client');
        $mock->shouldReceive('captureMessage')->times(1);
        $mock->shouldReceive('captureException')->times(1);
        $this->app->instance('raven', $mock);

        $handler = $this->app->exception;
        $handler->handleException(new Exception('Testing error handler'));
        Log::info('hello');

        // ------

        Config::set('raven::environments', array('production', 'local', 'staging'));
        $this->app['env'] = 'testing';

        $mock = Mockery::mock('Raven_Client');
        $mock->shouldReceive('captureMessage')->times(0);
        $mock->shouldReceive('captureException')->times(0);
        $this->app->instance('raven', $mock);

        $handler = $this->app->exception;
        $handler->handleException(new Exception('Testing error handler'));
        Log::info('hello');

        // ------

        Config::set('raven::environments', array());
        $this->app['env'] = 'testing';

        $mock = Mockery::mock('Raven_Client');
        $mock->shouldReceive('captureMessage')->times(0);
        $mock->shouldReceive('captureException')->times(0);
        $this->app->instance('raven', $mock);

        $handler = $this->app->exception;
        $handler->handleException(new Exception('Testing error handler'));
        Log::info('hello');
    }

}
