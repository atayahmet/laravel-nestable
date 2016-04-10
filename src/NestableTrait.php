<?php namespace Nestable;

use Illuminate\Database\Eloquent\Collection;
use Nestable\Services\NestableService;

trait NestableTrait {

    /**
     * Start the nested process
     * @var mixed
     */
    protected static $nested = false;

    /**
     * Soruce data
     * @var object Illuminate\Database\Eloquent\Collection
     */
    protected $source;

    /**
     * Service parameters
     * @var array
     */
    protected static $parameters = [];

    /**
     * Array service
     * @var integer
     */
    public static $toArray = 1;

    /**
     * Json string service
     * @var integer
     */
    public static $toJson = 2;

    /**
     * Html service
     * @var integer
     */
    public static $toHtml = 3;

    /**
     * Dropdown service
     * @var integer
     */
    public static $toDropdown = 4;

    /**
     * Default service number (toArray)
     * @var integer
     */
    protected static $to = 1;

    /**
     * Set the nest type
     *
     * @param  integer $to
     * @return object
     */
    public static function nested($to = 1)
    {
        static::$to = $to;
        static::$nested = is_numeric($to) ? $to : false;

        return new self;
    }

    /**
     * Get the data from db to collection or default return
     *
     * @return mixed
     */
    public function get()
    {
        $this->source = parent::get();

        if(! static::$nested) {
            return $this->source;
        }

        return $this->to(static::$to);
    }

    /**
     * Pass data to nest methods
     *
     * @return mixed
     */
    protected function to()
    {
        if(static::$to === 1) {
            $method = 'renderAsArray';
        }
        elseif(static::$to === 2) {
            $method = 'renderAsJson';
        }
        elseif(static::$to === 3) {
            $method = 'renderAsHtml';
        }
        elseif(static::$to === 4) {
            $method = 'renderAsDropdown';
        }else{
            return $this->source;
        }

        $nest = new NestableService;
        $nest->save(static::$parameters);

        $nestable = $nest->make($this->source);

        return call_user_func([$nestable, $method]);

    }

    /**
     * Render as html
     *
     * @return string
     */
    public static function renderAsHtml()
    {
        return self::nested(static::$toHtml);
    }

    /**
     * Render as array tree
     *
     * @return array
     */
    public static function renderAsArray()
    {
        return self::nested(static::$toArray);
    }

    /**
     * Render as json string
     *
     * @return array
     */
    public static function renderAsJson()
    {
        return self::nested(static::$toJson);
    }

    /**
     * Render as multiple list box
     *
     * @return string
     */
    public static function renderAsMultiple()
    {
        return self::multiple()->nested(static::$toDropdown);
    }

    /**
     * Render as dropdown
     *
     * @return string
     */
    public static function renderAsDropdown()
    {
        return self::nested(static::$toDropdown);
    }

    /**
     * Return the parent key name
     *
     * @return string
     */
    protected function getParentField()
    {
        return property_exists($this, 'parent') ? $this->parent : 'parent_id';
    }

    /**
     * Save the service parameters
     *
     * @param  string $method Method name in NestableService
     * @param  mixed $value
     * @return object
     */
    protected function saveParameter($method, $value)
    {
        static::$parameters[$method] = $value;

        return $this;
    }

    /**
     * Get the all service parameters
     *
     * @return array
     */
    protected function getParameters()
    {
        return $this->paremeters;
    }

    /**
     * if called method not exists in NestableService
     * pass to parent __call method
     *
     * @param  array $method
     * @param  array $args
     * @return mixed
     */
    public function __call($method, $args)
    {
        if(method_exists(NestableService::class, $method)) {

            $args = count($args) > 1 ? $args : current($args);
            static::saveParameter($method, $args);

            return $this;
        }

        return parent::__call($method, $args);
    }
}
