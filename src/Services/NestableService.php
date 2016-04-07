<?php namespace Services;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Collection as Collect;
use InvalidArgumentException;

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
     * Multiple dropdown status
     * @var boolean
     */
    protected $multiple = false;

    /**
     * Collection data
     * @var object Illuminate\Support\Collection
     */
    protected $data;

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
    public function toArray($data = false, $parent = 0)
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
                    $currentData->put($child, $this->toArray($args['data'], $item_id));

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
    public function toJson($data = false, $parent = 0)
    {
        $args = func_get_args();

        if(count($args) < 1) {
            $data = $this->toArray();
        }else {
            $data = $this->toArray($data);
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
    public function toHtml($data = false, $parent = 0, $first = true)
    {
        $args = $this->setParameters(func_get_args());

        // open the ul tag if function is first run
        $tree = $first ? $this->ul() : '';

        $args['data']->each(function($child_item) use(&$tree, $args) {

            $childItems = '';

            if($child_item[$this->parent] == $args['parent']) {

                $currentData = [
                    'label' => $child_item[$this->config['html']['label']],
                    'href' => $child_item[$this->config['html']['href']]
                ];

                // open the li tag
                $childItems .= $this->openLi($currentData);

                // Get the primary key name
                $item_id = $child_item[$this->config['primary_key']];

                // check the child element
                if($this->hasChild($this->parent, $item_id, $args['data'])) {

                    // function call again for child elements
                    $childItems .= $this->ul($this->toHtml($args['data'], $item_id, false));
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
    public function toDropdown($data = false, $parent = 0, $first = true, $level = 0)
    {
        $args = $this->setParameters(func_get_args());

        // open the select tag
        if($first) {
            $tree = $first ? '<select '.$this->addAttributes().' ' : '';
        }

        // if pass array data to selected method procces will generate multiple dropdown menu.
        if($first && ($this->selected !== false || $this->multiple == true)) {
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
                $selected = $this->doSelect($value);

                // Generating dropdown item
                $childItems .= '<option '.$selected.' value="'.$value.'">'.$levels.$prefix.$label.'</option>';

                $item_id = $child_item[$this->config['primary_key']];

                // check the child element
                if($this->hasChild($this->parent, $item_id, $args['data'])) {
                    $level++; // nest level increasing

                    // function call again for child elements
                    $childItems .= $this->toDropdown($args['data'], $item_id, false, $level);
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
    protected function doSelect($value)
    {
        if($this->selected) {

            if(is_array($this->selected) && count($this->selected) > 0) {

                $result = array_search($value, $this->selected);

                if($result !== false) {
                    unset($this->selected[$result]);
                    return 'selected';
                }
            }else{

                if($this->selected == $value) {
                    $this->selected = null;
                    return 'selected="selected"';
                }

            }

        }
    }

    public function save(array $params)
    {
        foreach($params as $method => $param) {
            $this->{$method}($param);
        }
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
    public function openLi(array $li)
    {
        return "\n".'<li><a href="' . $li['href'] . '">' . $li['label'] . '</a>';
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
