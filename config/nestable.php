<?php

return [
    'parent'=> 'parent_id',
    'primary_key' => 'id',
    'cache' => false,
    'generate_url'   => false,
    'childNode' => 'child',
    'body' => [
        'id',
        'name',
        'slug',
    ],
    'html' => [
        'label' => 'name',
        'href'  => 'slug'
    ],
    'dropdown' => [
        'prefix' => '',
        'label' => 'name',
        'value' => 'id'
    ]
];
