Laravel Nestable
========

##Install
```ssh
composer require atayahmet/laravel-nestable
```

Then

Add to **app.php** the Service Provider file.
```ssh
Nestable\NestableServiceProvider::class
```

Then add **app.php** Facade file again.
```ssh
'Nestable' => Nestable\Facades\NestableService::class
```

Finally run the artisan command:
```ssh
php artisan vendor:publish
```
That's it!

##Basic Usage with Eloquent

Suppose that the data came from a database as follows.

Category table:

id | parent_id | name           | slug
---| --------- | -------------- | -------
1  | 0         | T-shirts       | t-shirts
2  | 1         | Red T-shirts   | red-t-shirts
3  | 1         | Black T-shirts | black-t-shirts
4  | 0         | Sweaters       | sweaters
5  | 4         | Red Sweaters   | red-sweaters
6  | 4         | Blue Sweaters  | blue-sweaters

Example 1:

```php
<?php

use Nestable\NestableTrait;

class Category extends \Eloquent {

    use NestableTrait;

    protected $parent = 'parent_id';

}

```

>**Note**: **$parent** variable refers to the parent  category (Default parent_id)


```php
<?php

    $category = new Category;
    $result = $category->nested()->get();
```
Query result:

```php
<?php
    dd($result);

    array:6 [
          0 => array:5 [
            "id" => 1
            "name" => "T-shirts"
            "slug" => "t-shirts"
            "child" => array:2 [
              0 => array:5 [
                "id" => 2
                "name" => "Red T-shirts"
                "slug" => "red-t-shirts"
                "child" => []
                "parent_id" => 1
              ]
              1 => array:5 [
                "id" => 3
                "name" => "Black T-shirts"
                "slug" => "black-t-shirts"
                "child" => []
                "parent_id" => 1
              ]
            ]
            "parent_id" => 0
          ]
          1 => array:5 [
            "id" => 4
            "name" => "Sweaters"
            "slug" => "sweaters"
            "child" => array:2 [
              0 => array:5 [
                "id" => 5
                "name" => "Red Sweaters"
                "slug" => "red-sweaters"
                "child" => []
                "parent_id" => 4
              ]
              1 => array:5 [
                "id" => 6
                "name" => "Blue Sweaters"
                "slug" => "blue-sweaters"
                "child" => []
                "parent_id" => 4
              ]
            ]
            "parent_id" => 0
        ]
    ]
```
For html tree output:

```php
<?php

$category->nested(Category::$toHtml)->get();
```

Output:

```html

<ul>
    <li><a href="">T-shirts
        <ul>
            <li><a href="red-t-shirt">Red T-shirt</a></li>
            <li><a href="black-t-shirts">Black T-shirts</a></li>
        </ul>
    </li>

    <li><a href="">Sweaters
        <ul>
            <li><a href="red-sweaters">Red Sweaters</a></li>
            <li><a href="blue-sweaters">Blue Sweaters</a></li>
        </ul>
    </li>
</ul>
```

For dropdown tree output:

```php
<?php

$category
    ->attr(['name' => 'categories'])
    ->selected(2)
    ->nested(Category::$toDropdown)
    ->get();
```

Output:

```html
<select name="categories">
    <option value="1">T-shirts</option>
    <option value="2" selected="selected">  Red T-shirts</option>
    <option value="3">  Black T-shirts</option>

    <option value="4">Sweaters</option>
    <option value="5">  Red Sweaters</option>
    <option value="6">  Blue Sweaters</option>
</select>
```

Selected for multiple list box:
```php
->selected([1,2,3])
```

##Output types
name                  | type    |
----------------------| ------- |
{model}::$toArray     | array   |
{model}::$toJson      | json    |
{model}::$toHtml      | html    |
{model}::toDropdown   | dropdown|

##Method References
name                  | paremeter| description                      |
----------------------| -------- | -------------------------------- |
nested()              | int      | Query result will return as nested|
parent()              | int      | Reference the category starts|
selected()            | int/array| Selected item(s) for dropdown/listbox                |
multiple()            | none     | Generate multiple listbox |
attr()                | array    | Dropdown/listbox attributes                  |


##Configuration
The above examples were performed with default settings.
Config variables in **config/nestable.php** file.

name                  | type    | description                      |
----------------------| ------- | -------------------------------- |
parent                | string  | Parent category column name      |
primary_key           | string  | Table primary key                |
generate_url          | boolean | Generate the url for html output |
childNode             | string  | Child node name                  |
[body](#body)         | array   | Array output (default)           |
[html](#html)         | array   | Html output columns              |
[dropdown](#dropdown) | array   | Dropdown/Listbox output          |

###body:

The body variable should be an array and absolutely customizable.

Example:
```php
<?php

'body' => [
    'id',
    'category_name',
    'category_slug'
]
```

###html
Configuration for html output.

name         | description       |
-------------| ----------------- |
label        | Label column name |
href         | Url column name   |

Example:
```php
<?php

'html' => [
    'label' => 'name',
    'href'  => 'slug',
]
```

###dropdown
Configuration for dropdown/listbox output.

name   | description         |
-------| ------------------- |
prefix | Label prefix        |
label  | Label column name   |
value  | Value column name   |

Example:

```php
<?php

'dropdown' => [
    'prefix' => '-',
    'label'  => 'name',
    'value'  => 'id'
]
```

##Standalone Usage From Model

Include the Nestable facade.

```php
<?php

use Nestable;

$result = Nestable::make([
    [
        'id' => 1,
        'parent_id' => 0,
        'name' => 'T-shirts',
        'slug' => 't-shirts'
    ],
    [
        'id' => 2,
        'parent_id' => 1,
        'name' => 'Red T-shirts',
        'slug' => 'red-t-shirts'
    ],
    [
        'id' => 3,
        'parent_id' => 1,
        'name' => 'Black T-shirts',
        'slug' => 'black-t-shirts'
    ]
    // and more...
]);
```

For array output:
```php
$result->toArray();
```

Methods for standalone:

name        | type      | description              |
------------| --------- |------------------------- |
toArray()   | function  | Array output             |
toJson()    | function  | Json string output       |
toHtml()    | function  | Html output              |
toDropdown()| function  | Dropdown/listbox output  |
parent()              | int      | Reference the category starts|
selected()            | int/array| Selected item(s) for dropdown/listbox                |
multiple()            | none     | Generate multiple listbox |
attr()                | array    | Dropdown/listbox attributes  
