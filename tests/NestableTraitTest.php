<?php

namespace Nestable\Tests;

use RecursiveIteratorIterator;
use RecursiveArrayIterator;

class NestableTraitTest extends DBTestCase
{
    protected $categories;

    /**
     * Setup the test environment.
     */
    public function setUp()
    {
        parent::setUp();

        $this->withFactories(__DIR__.'/factories');

        factory(Model\Category::class, 50)->create();

        $this->categories = Model\Category::all()->toArray();
    }

    public function testNested()
    {
        $nested = Model\Category::nested()->get();

        $iteratorArray = new RecursiveArrayIterator($nested);
        $iterator = new RecursiveIteratorIterator($iteratorArray);

        $this->assertTrue($iterator->valid());
        $this->assertFalse($nested == $this->categories);

        $parent_id = $this->_get_random_parent_id($iteratorArray);

        if ($parent_id) {
            $result = $this->_helper_recursive($nested, $parent_id);

            $this->assertGreaterThan(0, $result);
        }
    }

    public function testRenderAsArray()
    {
        $this->testNested();
    }

    public function testRenderAsJson()
    {
        $nested = Model\Category::renderAsJson();

        json_decode($nested);

        $this->assertLessThan(1, json_last_error());

        $this->assertTrue($nested != json_encode($this->categories));
    }

    public function testRenderAsDropdown()
    {
        $dropdown = Model\Category::renderAsDropdown();
        $this->assertRegExp('/'.$this->_get_pattern('dropdown').'/', $dropdown);

        // test where
        $dropdown = Model\Category::whereRaw("name like '%i%' OR name like '%a%'")->renderAsDropdown();
        $this->assertRegExp('/'.$this->_get_pattern('dropdown_single_option').'/', $dropdown);
    }

    public function testRenderAsMultiple()
    {
        $dropdown = Model\Category::renderAsMultiple();
        $this->assertRegExp('/'.$this->_get_pattern('multiple').'/', $dropdown);

        // test where
        $dropdown = Model\Category::whereRaw("name like '%i%' OR name like '%a%'")->renderAsMultiple();
        $this->assertRegExp('/'.$this->_get_pattern('dropdown_single_option').'/', $dropdown);
    }

    public function testRenderAsHtml()
    {
        $html = Model\Category::renderAsHtml();
        $this->assertRegExp('/'.$this->_get_pattern('html').'/', $html);
    }
}
