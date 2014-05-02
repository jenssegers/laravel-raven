<?php

class ServiceProviderTest extends Orchestra\Testbench\TestCase {

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

    public function testCustomConfiguration()
    {
        Config::set('raven::name', 'foo');
        Config::set('raven::site', 'bar');
        Config::set('raven::tags', array('php_version' => phpversion()));

        $raven = App::make('raven');
        $this->assertEquals('foo', $raven->name);
        $this->assertEquals('bar', $raven->site);
        $this->assertEquals(array('php_version' => phpversion()), $raven->tags);
    }

    public function testIsSingleton()
    {
        $raven1 = App::make('raven');
        $raven2 = App::make('raven');
        $this->assertEquals(spl_object_hash($raven1), spl_object_hash($raven2));
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

}
