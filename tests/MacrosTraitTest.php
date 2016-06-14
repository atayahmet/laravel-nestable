<?php

namespace Nestable\Tests;

class MacrosTraitTest extends TestCase
{
    protected $categories;

    public function setUp()
    {
        parent::setUp();

        $this->categories = $this->dummyData();
    }

    public function testMacro()
    {
        $nestable = new \Nestable\Services\NestableService();
        $nested = $nestable->make($this->categories);

        $nested->macro('test', function () {
            return 'Nestable';
        });

        $this->assertTrue($nested->hasMacro('test'));
    }

    public function testHasMacro()
    {
        $nestable = new \Nestable\Services\NestableService();
        $nested = $nestable->make($this->categories);

        $nested->macro('test', function () {
            return 'Nestable';
        });

        $this->assertTrue($nested->hasMacro('test'));
        $this->assertFalse($nested->hasMacro('_test'));
    }

    public function testRunMacro()
    {
        $nestable = new \Nestable\Services\NestableService();
        $nested = $nestable->make($this->categories);

        $nested->macro('test', function () {
            return 'Nestable';
        });

        $this->assertEquals('Nestable', $nested->test());
    }
}
