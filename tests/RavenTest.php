<?php

class RavenTest extends Orchestra\Testbench\TestCase {

    public function setUp()
    {
        parent::setUp();

        $this->dsn = 'https://foo:bar@app.getsentry.com/12345';
        Config::set('raven::dsn', $this->dsn);
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
        $this->assertInstanceOf('Jenssegers\Raven\Raven', $raven);
        $this->assertInstanceOf('Raven_Client', $raven);
    }

    public function testFacade()
    {
        $raven = Jenssegers\Raven\Facades\Raven::getFacadeRoot();
        $this->assertInstanceOf('Jenssegers\Raven\Raven', $raven);
        $this->assertInstanceOf('Raven_Client', $raven);
    }

    public function testPassConfiguration()
    {
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

    public function testServicesConfiguration()
    {
        $dsn = 'https://bar:foo@app.getsentry.com/4892345';
        Config::set('services.raven.dsn', $dsn);

        $raven = App::make('raven');
        $this->assertEquals('4892345', $raven->project);
        $this->assertEquals('bar', $raven->public_key);
        $this->assertEquals('foo', $raven->secret_key);
    }

    public function testTagsAndSessionData()
    {
        Session::set('foo', 'bar');

        $mock = Mockery::mock('Jenssegers\Raven\Raven[send]');
        $mock->shouldReceive('send')->once()->with(array(
            'sentry.interfaces.User'=>array('data'=>array('foo'=>'bar'),'id'=>Session::getId()),
            'server_name'=>'server',
            'project'=>1,
            'site'=>'',
            'logger'=>'php',
            'tags'=>array('environment'=>'testing','ip'=>'127.0.0.1',),
            'platform'=>'php',
            'event_id'=>1,
            'timestamp'=>'',
            'level'=>'error',
            'extra'=>array(),
        ));
        $this->app->instance('raven', $mock);

        $raven = App::make('raven');
        $raven->capture(array('event_id' => 1, 'timestamp' => '', 'server_name' => 'server'), array());
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

        $mock = Mockery::mock('Jenssegers\Raven\Raven[captureMessage,captureException]');
        $mock->shouldReceive('captureMessage')->once()->with('hello', array(), array('level' => 'info', 'extra' => array()));
        $mock->shouldReceive('captureMessage')->once()->with('oops', array(), array('level' => 'error', 'extra' => array()));
        $mock->shouldReceive('captureException')->once()->with($exception, array('level' => 'error', 'extra' => array()));
        $this->app->instance('raven', $mock);

        Log::info('hello');
        Log::error('oops');
        Log::error($exception);
    }

    /*public function testFlush()
    {
        $mock = Mockery::mock('Jenssegers\Raven\Raven');
        $mock->shouldReceive('sendUnsentErrors')->once();
        $this->app->instance('raven', $mock);

        Route::enableFilters();
        $this->app->shutdown();
    }*/

    public function testQueueGetsPushed()
    {
        $mock = Mockery::mock('Illuminate\Queue\QueueManager');
        $mock->shouldReceive('push')->once();
        $this->app->instance('queue', $mock);

        Log::info('hello');
    }

    public function testQueueGetsFired()
    {
        $mock = Mockery::mock('Jenssegers\Raven\Job');
        $mock->shouldReceive('fire')->once();
        $this->app->instance('Jenssegers\Raven\Job', $mock);

        Log::info('hello');
    }

    public function testPassContext()
    {
        Session::set('token', 'foobar');

        $mock = Mockery::mock('Jenssegers\Raven\Raven[send]');
        $mock->shouldReceive('send')->once()->with(array(
            'sentry.interfaces.User'=>array('id'=>1,'email'=>'foo@bar.com','data'=>array('token'=>'foobar')),
            'server_name'=>'server',
            'project'=>1,
            'site'=>'',
            'logger'=>'php',
            'tags'=>array('environment'=>'testing','ip'=>'127.0.0.1','tag'=>1),
            'platform'=>'php',
            'event_id'=>1,
            'timestamp'=>'',
            'level'=>'info',
            'extra'=>array('foo'=>'bar'),
            'message'=>'hello',
            'sentry.interfaces.Message'=>array('message'=>'hello','params'=>array())
        ));
        $this->app->instance('raven', $mock);

        Log::info('hello', array(
            'user' => array(
                'id' => 1,
                'email' => 'foo@bar.com'
            ),
            'tags' => array(
                'tag' => '1'
            ),
            'extra' => array(
                'foo' => 'bar'
            ),
            'timestamp' => '',
            'event_id'=>1,
            'server_name'=>'server'
        ));
    }

}
