<?php


$factory->define(Nestable\Tests\Model\Category::class, function (Faker\Generator $faker) {
    return [
        'name' => $faker->sentence(2),
        'parent_id' => rand(0, 10),
        'slug' => $faker->slug,
    ];
});
