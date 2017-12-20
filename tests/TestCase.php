<?php

namespace Nestable\Tests;

use Orchestra\Testbench\TestCase as OrchestraTestCase;
use RecursiveArrayIterator;

abstract class TestCase extends OrchestraTestCase
{
    /**
     * Define environment setup.
     *
     * @param \Illuminate\Foundation\Application $app
     */
    protected function getEnvironmentSetUp($app)
    {
        $app['router']->get('category/{slug}', ['as' => 'category', 'uses' => function () {
           return 'hello world';
       }]);
    }

    protected function getPackageProviders($app)
    {
        return [\Nestable\NestableServiceProvider::class, \Orchestra\Database\ConsoleServiceProvider::class];
    }

    protected function getPackageAliases($app)
    {
        return [
            'Nestable' => \Nestable\Facades\NestableService::class,
        ];
    }

    protected function _get_random_parent_id(RecursiveArrayIterator $iteratorArray)
    {
        $loop = rand(0, 2);
        $parent_id = false;

        while ($iteratorArray->valid()) {
            if ($iteratorArray->hasChildren()) {
                foreach ($iteratorArray->getChildren() as $key => $value) {
                    if ($parent_id) {
                        break;
                    }

                    if (is_array($value)) {
                        $rand = rand(0, count($value) - 1);

                        if (isset($value[$rand]['parent_id'])) {
                            $parent_id = $value[$rand]['parent_id'];
                        }
                    }
                }
            }
            $iteratorArray->next();
        }

        return $parent_id;
    }

    protected function _helper_recursive(array $nested, $parent_id, $total = 0)
    {
        foreach ($nested as $key => $category) {
            if (isset($category['parent_id']) && (int) $category['parent_id'] == (int) $parent_id) {
                ++$total;
            } else {
                if (isset($category['child']) && count($category['child']) > 0) {
                    $total += $this->_helper_recursive($category['child'], $parent_id, $total);
                }
            }
        }

        return $total;
    }

    protected function dummyData()
    {
        return [
            [
                'id' => 1,
                'parent_id' => 0,
                'name' => 'Sweaters',
                'slug' => 'sweaters',
            ],
            [
                'id' => 2,
                'parent_id' => 1,
                'name' => 'Black Sweaters',
                'slug' => 'black-sweaters',
            ],
            [
                'id' => 3,
                'parent_id' => 1,
                'name' => 'Yellow Sweaters',
                'slug' => 'yellow-sweaters',
            ],
            [
                'id' => 4,
                'parent_id' => 1,
                'name' => 'Blue Sweaters',
                'slug' => 'blue-sweaters',
            ],
            [
                'id' => 5,
                'parent_id' => 4,
                'name' => 'Light Blue Sweaters',
                'slug' => 'light-blue-sweaters',
            ],
            [
                'id' => 6,
                'parent_id' => 0,
                'name' => 'T-Shirts',
                'slug' => 't-shirts',
            ],
            [
                'id' => 7,
                'parent_id' => 6,
                'name' => 'Black T-Shirts',
                'slug' => 'black-t-shirts',
            ],
            [
                'id' => 8,
                'parent_id' => 6,
                'name' => 'Yellow T-Shirts',
                'slug' => 'yellow-t-shirts',
            ],
            [
                'id' => 9,
                'parent_id' => 6,
                'name' => 'Blue T-Shirts',
                'slug' => 'blue-t-shirts',
            ],
            [
                'id' => 10,
                'parent_id' => 6,
                'name' => 'Light Blue T-Shirts',
                'slug' => 'light-blue-t-shirts',
            ],
        ];
    }

    protected function _get_pattern($type)
    {
        switch ($type) {

            case 'html';
                return '\<ul.*\>\s+\<li\s+?\>\<a\s+?href\=\"https?:\/\/.*\"\>.*\<\/a\>(\<ul\>\s+?\<li\s+?\>.*\<\/li\>)?';
                break;

                case 'html-first-item';
                return '\<ul class="first-item"\>\s+\<li\s+?\>\<a\s+?href\=\"https?:\/\/.*\"\>.*\<\/a\>(\<ul\>\s+?\<li\s+?\>.*\<\/li\>)?';
                break;

            case 'multiple';
                return '\<select\s+?multiple\>(\<option\s+?value\=\".*?\"\>.*\<\/option\>)\<\/select\>';
                break;

            case 'dropdown';
                return '\<select\s+?\>(\<option\s+?value\=\".*?\"\>.*\<\/option\>)\<\/select\>';
                break;

            case 'dropdown_single_option';
                return "(\<option.*?\>.*?\<\/option\>){1}";
                break;

            case 'attribute_pattern_for_ul';
                return "(\<ul\s+[a-zA-Z]+\=\".*?\">)";
                break;
        }
    }
}
