<?php

namespace Nestable;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Builder;
use Nestable\Services\NestableService;
use Closure;

trait NestableTrait
{
    /**
     * Start the nested process.
     *
     * @var mixed
     */
    protected static $nested = false;

    /**
     * Soruce data.
     *
     * @var object Illuminate\Database\Eloquent\Collection
     */
    protected $source;

    /**
     * Service parameters.
     *
     * @var array
     */
    protected static $parameters = [];

    /**
     * Array service.
     *
     * @var int
     */
    public static $toArray = 1;

    /**
     * Json string service.
     *
     * @var int
     */
    public static $toJson = 2;

    /**
     * Html service.
     *
     * @var int
     */
    public static $toHtml = 3;

    /**
     * Dropdown service.
     *
     * @var int
     */
    public static $toDropdown = 4;

    /**
     * Default service number (toArray).
     *
     * @var int
     */
    protected static $to = 1;

    /**
     * Query builder instance.
     *
     * @var object Illuminate\Database\Eloquent\Builder;
     */
    protected static $_instance;

    /**
     * Set the nest type.
     *
     * @param int $to
     *
     * @return object
     */
    public static function nested($to = 1)
    {
        static::$to = $to;
        static::$nested = is_numeric($to) ? $to : false;

        return new self();
    }

    /**
     * Get the data from db to collection or default return.
     *
     * @return mixed
     */
    public function get()
    {
        // if exists the where or similar things in the query
        // call the from instance
        if (static::$_instance instanceof Builder) {

            $this->source = static::$_instance->get();
        } else {
            // if not call the parent directly
            $this->source = parent::all();
        }

        if (!static::$nested) {
            return $this->source;
        }

        return $this->to(static::$to);
    }

    /**
     * Pass data to nest methods.
     *
     * @return mixed
     */
    protected function to()
    {
        if (static::$to === 1) {
            $method = 'renderAsArray';
        } elseif (static::$to === 2) {
            $method = 'renderAsJson';
        } elseif (static::$to === 3) {
            $method = 'renderAsHtml';
        } elseif (static::$to === 4) {
            $method = 'renderAsDropdown';
        } else {
            return $this->source;
        }

        $nest = new NestableService();
        $nest->save(static::$parameters);
        $nestable = $nest->make($this->source);

        static::$nested = false;

        return call_user_func([$nestable, $method]);
    }

    /**
     * Render as html.
     *
     * @return string
     */
    public static function renderAsHtml()
    {
        return self::nested(static::$toHtml)->get();
    }

    /**
     * Render as array tree.
     *
     * @return array
     */
    public static function renderAsArray()
    {
        return self::nested(static::$toArray)->get();
    }

    /**
     * Render as json string.
     *
     * @return array
     */
    public static function renderAsJson()
    {
        return self::nested(static::$toJson)->get();
    }

    /**
     * Render as multiple list box.
     *
     * @return string
     */
    public static function renderAsMultiple()
    {
        return self::multiple()->nested(static::$toDropdown)->get();
    }

    /**
     * Render as dropdown.
     *
     * @return string
     */
    public static function renderAsDropdown()
    {
        return self::nested(static::$toDropdown)->get();
    }

    /**
     * Return the parent key name.
     *
     * @return string
     */
    protected function getParentField()
    {
        return property_exists($this, 'parent') ? $this->parent : 'parent_id';
    }

    /**
     * Save the service parameters.
     *
     * @param string $method Method name in NestableService
     * @param mixed  $value
     *
     * @return object
     */
    protected function saveParameter($method, $value)
    {
        if (!isset(static::$parameters[$method])) {
            static::$parameters[$method] = [];
        }

        if ($value instanceof Closure) {
            static::$parameters[$method]['callback'] = $value;
        } elseif (is_array($value)) {
            foreach ($value as $key => $attr) {
                static::$parameters[$method][$key] = $attr;
            }
        }else{
            static::$parameters[$method][] = $value;
        }

        return $this;
    }

    /**
     * Get the all service parameters.
     *
     * @return array
     */
    protected function getParameters()
    {
        return $this->paremeters;
    }

    /**
     * Call the parent where method
     *
     * @return bool|null
     *
     * @throws \Exception
     */
    public static function where($column, $operator = null, $value = null, $boolean = 'and')
    {
        return parent::query()->where($column, $operator, $value, $boolean);
    }

    /**
     * Call the parent delete method
     *
     * @return bool|null
     *
     * @throws \Exception
     */
    public function delete()
    {
        if (static::$_instance instanceof Builder) {
            return static::$_instance->delete();
        }
        return parent::delete();
    }

    /**
     * if called method not exists in NestableService
     * pass to parent __call method.
     *
     * @param array $method
     * @param array $args
     *
     * @return mixed
     */
    public function __call($method, $args)
    {
        if (method_exists(NestableService::class, $method)) {
            $args = count($args) > 1 ? $args : current($args);
            static::saveParameter($method, $args);

            return $this;
        }

        $parentResult = parent::__call($method, $args);

        if ($parentResult instanceof Builder) {
            static::$_instance = $parentResult;

            return $this;
        }

        return $parentResult;
    }

    /**
     * Create new instance and call the method.
     *
     * @param array $method
     * @param array $args
     *
     * @return mixed
     */
    public static function __callStatic($method, $args)
    {
        return call_user_func_array([new static(), $method], $args);
    }
}
