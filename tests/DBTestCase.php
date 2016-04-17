<?php namespace Nestable\Tests;

use Orchestra\Testbench\TestCase as OrchestraTestCase;

class DBTestCase extends TestCase {
    /*
     * Bootstrap the application
     */
    public function setUp()
    {
        parent::setUp();

        $this->artisan = $this->app->make('Illuminate\Contracts\Console\Kernel');

        $this->artisan('migrate', [
                '--database' => 'testbench',
                '--realpath' => realpath(__DIR__.'/migrations'),
            ]
        );

    }

    /**
     * Define environment setup.
     *
     * @param  \Illuminate\Foundation\Application  $app
     * @return void
     */
    protected function getEnvironmentSetUp($app)
    {
        // Setup default database to use sqlite :memory:
        $app['config']->set('database.default', 'testbench');
        $app['config']->set('database.connections.testbench', [
            'driver'   => 'sqlite',
            'database' => ':memory:',
            'prefix'   => '',
        ]);

        $app['config']->set('nestable',  require(__DIR__.'/../config/nestable.php'));

    }
}
