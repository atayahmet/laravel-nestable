<?php namespace Nestable\Model;

use Illuminate\Database\Eloquent\Model;

class Menu extends Model {

    protected $table = 'menus';

    protected $fillable = [
        'name',
        'parent_id',
        'slug'
    ];

}
