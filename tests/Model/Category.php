<?php

namespace Nestable\Tests\Model;

use Illuminate\Database\Eloquent\Model;
use Nestable\NestableTrait;

class Category extends Model
{
    use NestableTrait;

    protected $table = 'categories';

    protected $parent = 'parent_id';

    protected $fillable = [
        'name',
        'parent_id',
        'slug',
    ];
}
