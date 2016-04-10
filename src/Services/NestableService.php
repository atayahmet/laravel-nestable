<?php namespace Nestable\Services;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Collection as Collect;
use URL;
use InvalidArgumentException;
use Closure;

class NestableService {

    /**
     * configuration vars
     * @var array
     */
    protected $config;

    /**
     * Parent key name
     * @var string
     */
    protected $parent;

    /**
     * Parent idset of current process
     * @var array
     */
    protected $parents;

    /**
     * Dropdown attributes
     * @var array
     */
    protected $dropdownAttr = [];

    /**
     * Selectable values
     * @var mixed
     */
    protected $selected = false;

    /**
     * Dropdown or Listbox item attributes
     * @var array
     */
    protected $optionAttr = null;

    /**
     * Selectable values for html output
     * @var mixed
     */
    protected $active = false;

    /**
     * Multiple dropdown status
     * @var boolean
     */
    protected $multiple = false;

    /**
     * Collection data
     * @var object Illuminate\Support\Collection
     */
    protected $data;

    protected $route = false;

    /**
     * Set the data to wrap class
     *
     * @param  mixed $data
     * @return object
     */
    public function make($data)
    {
        if($data instanceof Collection) {
            $this->data = collect($data->toArray());
        }
        elseif(is_array($data)) {
            $this->data = collect($data);
        }else{
            throw new InvalidArgumentException("Invalid data type. ");
        }

        $this->config = config('nestable');

        $this->parent = $this->config['parent'];

        $this->primary_key = $this->config['primary_key'];

        return $this;
    }

    /**
     * Pass to array of all data as nesting
     *
     * @param  object  $data   Illuminate\Support\Collection
     * @param  integer $parent
     * @return Recursion|Array
     */
    public function renderAsArray($data = false, $parent = 0)
    {
        $args = $this->setParameters(func_get_args());
        $tree = collect([]);

        $args['data']->each(function($item) use(&$tree, $args) {

            $currentData = collect([]);

            if($item[$this->parent] == $args['parent']) {

                // fill the array with the body fields
                foreach($this->config['body'] as $field) {
                    $currentData->put($field, isset($item[$field]) ? $item[$field] : null);
                }

                // Get the child node name
                $child = $this->config['childNode'];

                $currentData->put($child, []);
                $currentData->put($this->parent, $item[$this->parent]);

                // Get the primary key name
                $item_id = $item[$this->config['primary_key']];

                // check the child element
                if($this->hasChild($this->parent, $item_id, $args['data'])) {

                    // function call again for child elements
                    $currentData->put($child, $this->renderAsArray($args['data'], $item_id));

                }

                // current data push to global array
                $tree->push($currentData->toArray());

            }

        });

        return $tree->toArray();
    }

    /**
     * Pass to json string of all data as nesting
     *
     * @param  object  $data   Illuminate\Support\Collection
     * @param  integer $parent
     * @return Recursion|Array
     */
    public function renderAsJson($data = false, $parent = 0)
    {
        $args = func_get_args();

        if(count($args) < 1) {
            $data = $this->renderAsArray();
        }else {
            $data = $this->renderAsArray($data);
        }

        return json_encode($data);
    }

    /**
     * Pass to html (ul:li) as nesting
     *
     * @param  object  $data   Illuminate\Support\Collection
     * @param  integer $parent Current parent id
     * @param  bool  $first First run
     * @return string
     */
    public function renderAsHtml($data = false, $parent = 0, $first = true)
    {
        $args = $this->setParameters(func_get_args());

        // open the ul tag if function is first run
        $tree = $first ? $this->ul() : '';

        $args['data']->each(function($child_item) use(&$tree, $args) {

            $childItems = '';

            if($child_item[$this->parent] == $args['parent']) {

                $path = $child_item[$this->config['html']['href']];
                $label = $child_item[$this->config['html']['label']];

                $currentData = [
                    'label' => $label,
                    'href' => $this->url($path, $label)
                ];

                // Check the active item
                $activeItem = $this->doActive($path, $label);

                // open the li tag
                $childItems .= $this->openLi($currentData, $activeItem);

                // Get the primary key name
                $item_id = $child_item[$this->config['primary_key']];

                // check the child element
                if($this->hasChild($this->parent, $item_id, $args['data'])) {

                    // function call again for child elements
                    $childItems .= $this->ul($this->renderAsHtml($args['data'], $item_id, false));
                }

                // close the li tag
                $childItems = $this->closeLi($childItems);

            }

            // current data contact to the parent variable
            $tree = $tree.$childItems;

        });

        // close the ul tag
        $tree = $first ? $this->closeUl($tree) : $tree;
        return $tree;
    }

    /**
     * Convert to dropdown
     *
     * @param  object  $data   Illuminate\Support\Collection
     * @param  integer $parent Current parent id
     * @param  bool  $first  first run
     * @param  integer $level nest counter
     * @return string
     */
    public function renderAsDropdown($data = false, $parent = 0, $first = true, $level = 0)
    {
        $args = $this->setParameters(func_get_args());

        // open the select tag
        if($first) {
            $tree = $first ? '<select '.$this->addAttributes().' ' : '';
        }

        // if pass array data to selected method procces will generate multiple dropdown menu.
        if($first && (is_array($this->selected) || $this->multiple == true)) {
            $tree .= ' multiple';
        }

        if($first) {
            $tree .= $first ? '>' : '';
        }

        $args['data']->each(function($child_item) use(&$tree, $args, $level) {

            $childItems = '';

            if($child_item[$this->parent] == $args['parent']) {

                // Get the value
                $value = $child_item[$this->config['dropdown']['value']];

                // Get the label text
                $label = $child_item[$this->config['dropdown']['label']];

                $prefix = $this->config['dropdown']['prefix'];

                // Generating nest level
                $levels = str_repeat("&nbsp;&nbsp;", $level);

                // check the does want select value
                $selected = $this->doSelect($value, $label);

                // Generating dropdown item
                $childItems .= '<option '.$selected.' value="'.$value.'">'.$levels.$prefix.$label.'</option>';

                $item_id = $child_item[$this->config['primary_key']];

                // check the child element
                if($this->hasChild($this->parent, $item_id, $args['data'])) {
                    $level++; // nest level increasing

                    // function call again for child elements
                    $childItems .= $this->renderAsDropdown($args['data'], $item_id, false, $level);
                }
            }

            // current data contact to the parent variable
            $tree = $tree.$childItems;

        });

        // close the select tag
        $tree = $first ? $tree.'</select>' : $tree;
        return $tree;
    }

    /**
     * Set the attributes of generated dropdown
     *
     * @param  array  $attributes
     * @return object
     */
    public function attr(array $attributes)
    {
        $this->dropdownAttr = $attributes;

        return $this;
    }

    /**
     * Contact th attributes to dropdown
     *
     * @return string
     */
    protected function addAttributes()
    {
        $attrs = '';

        foreach($this->dropdownAttr as $attr => $value) {

            $attrs .= $attr.'='.'"'.$value.'" ';
        }

        return $attrs;
    }

    /**
     * Child menu checker
     *
     * @param  string  $key
     * @param  mixed  $value
     * @param  object  $data  Illuminate\Support\Collection as Collect
     * @return boolean
     */
    public function hasChild($key, $value, Collect $data)
    {
        $child = false;

        $data->each(function($item) use(&$child, $key, $value){

            if($item[$key] == $value && ! $child) {
                $child = true;
            }

        });

        return $child;
    }

    /**
     * Save the will select values
     *
     * @param  int|array $values
     * @return object
     */
    public function selected($values)
    {
        $this->selected = $values;

        if(func_num_args() > 1) {
            $this->selected = func_get_args();
        }

        return $this;
    }

    public function active()
    {
        $args = func_get_args();
        $this->active = current($args);

        if(func_num_args() > 1) {
            $this->active = $args;
        }

        return $this;
    }

    protected function doActive($href, $label)
    {
        if($this->active) {

            // Find active path in array
            if(is_array($this->active) && count($this->active) > 0) {
                $result = array_search($href, $this->active);

                if($result !== false) {
                    unset($this->active[$result]);
                    return 'class="active"';
                }
            }

            // Run the closure for user customizable
            elseif($this->active instanceof Closure) {
                call_user_func_array($this->active, [$this, $href, $label]);
                return $this->renderAttr($this->optionAttr);
            }else{
                if($this->active == $href) {
                    $this->active = null;
                    return 'class="active"';
                }
            }
        }
    }

    /**
     * Multiple dropdown menu
     *
     * @return object
     */
    public function multiple()
    {
        $this->multiple = true;

        return $this;
    }

    public function parent($parent = false)
    {
        if($parent) {

            $this->parents = !is_array($parent) ? [$parent] : $parent;

            if(func_num_args() > 1) {
                $this->parents = func_get_args();
            }
        }

        return $this;
    }

    /**
     * initialize parameters (toArray, toHtml, toDropdown)
     *
     * @param array $args
     * @return void
     */
    protected function setParameters($args)
    {
        if(count($args) < 1) {

            return [
                'parent' => $this->parents ? current($this->parents) : 0,
                'data'   => $this->data
            ];
        }

        elseif(count($args) == 1) {
            return [
                'parent' => reset($args),
                'data'   => $this->data
            ];
        }else{
            return [
                'data'   => reset($args),
                'parent' => next($args)
            ];
        }
    }

    /**
     * Set the as selected of items
     *
     * @param  mixed $value
     * @return string
     */
    protected function doSelect($value, $label)
    {
        if($this->selected) {

            if(is_array($this->selected) && count($this->selected) > 0) {

                $result = array_search($value, $this->selected);

                if($result !== false) {
                    unset($this->selected[$result]);
                    return 'selected';
                }
            }

            elseif($this->selected instanceof Closure) {
                call_user_func_array($this->selected, [$this, $value, $label]);
                return $this->renderAttr($this->optionAttr);
            }else{

                if($this->selected == $value) {
                    $this->selected = null;
                    return 'selected="selected"';
                }
            }
        }
    }

    public function addAttr($attr, $value = '')
    {
        if(func_num_args() > 1) {
            $this->optionAttr[$attr] = $value;
        }

        elseif(is_array($attr)) {
            $this->optionAttr = $attr;
        }

        return $this;
    }

    protected function renderAttr()
    {
        $attributes = '';

        if(is_array($this->optionAttr)) {
            foreach($this->optionAttr as $attr => $value) {
                $attributes .= ' '.$attr.'="'.$value.'"';
            }
        }

        $this->optionAttr = null;

        return $attributes;
    }

    /**
     * Save the parameters
     *
     * @param  array $params
     * @return void
     */
    public function save(array $params)
    {
        foreach($params as $method => $param) {
            $this->{$method}($param);
        }
    }

    /**
     * URL Generator
     *
     * @param  string $path
     * @return string
     */
    protected function url($path, $label)
    {
        if($this->config['generate_url']) {

            if($this->route) {

                if($this->route instanceof Closure){
                    return call_user_func_array($this->route, [$path, $label]);
                }else{
                    $param = current($this->route);
                    $name = key($this->route);
                    return URL::route($name, [$param => $path]);
                }

            }

            return URL::to($path);
        }

        return '/'.$path;
    }

    /**
     * Route generator
     *
     * @param  array $route
     * @return void
     */
    public function route($route)
    {
        $this->route = $route;
    }

    /**
     * Generate open ul tag
     *
     * @param  string $items
     * @return string
     */
    public function ul($items = false)
    {
        if(! $items) return "\n".'<ul>'."\n";

        return '<ul>'."\n".$items."\n".'</ul>';
    }

    /**
     * Generate close ul tag
     *
     * @param  string $ul
     * @return string
     */
    public function closeUl($ul)
    {
        return $ul.'</ul>'."\n";
    }

    /**
     * Generate open li tag
     *
     * @param  array  $li
     * @return string
     */
    public function openLi(array $li, $extra = '')
    {
        return "\n".'<li '.$extra.'><a href="' . $li['href'] . '">' . $li['label'] . '</a>';
    }

    /**
     * Generate close li tag
     *
     * @param  string $li
     * @return string
     */
    public function closeLi($li)
    {
        return $li."</li>\n";
    }

}
