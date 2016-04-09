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
    protected $parameters = [];

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
            $method = 'toArray';
        }
        elseif(static::$to === 2) {
            $method = 'toJson';
        }
        elseif(static::$to === 3) {
            $method = 'toHtml';
        }
        elseif(static::$to === 4) {
            $method = 'toDropdown';
        }else{
            return $this->source;
        }

        $nest = new NestableService;
        $nest->save($this->parameters);

        $nestable = $nest->make($this->source);

        return call_user_func([$nestable, $method]);

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

            $this->parameters[$method] = current($args);

            return $this;
        }

        return parent::__call($method, $args);
    }
}
