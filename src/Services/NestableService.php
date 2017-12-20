<?php

namespace Nestable\Services;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Collection as Collect;
use InvalidArgumentException;
use Nestable\MacrosTrait;
use Closure;
use URL;
use Config;

class NestableService
{
    use MacrosTrait;

    /**
     * configuration vars.
     *
     * @var array
     */
    protected $config;

    /**
     * Parent key name.
     *
     * @var string
     */
    protected $parent;

    /**
     * Parent idset of current process.
     *
     * @var array
     */
    protected $parents;

    /**
     * Dropdown attributes.
     *
     * @var array
     */
    protected $dropdownAttr = [];

    /**
     * Selectable values.
     *
     * @var mixed
     */
    protected $selected = [];

    /**
     * Dropdown placeholder
     *
     * @var array
     */
    protected $placeholder = [];

    /**
     * Dropdown or Listbox item attributes.
     *
     * @var array
     */
    protected $optionAttr = null;

    /**
     * Dropdown option attributes
     *
     * @var array
     */
    protected $optionUlAttr = [];

    /**
     * First ul element attributes
     *
     * @var array
     */
    protected $firstUlAttrs = [];

    /**
     * Selectable values for html output.
     *
     * @var mixed
     */
    protected $active = false;

    /**
     * Multiple dropdown status.
     *
     * @var bool
     */
    protected $multiple = false;

    /**
     * Collection data.
     *
     * @var object Illuminate\Support\Collection
     */
    public $data = [];

    /**
     * Route parameters.
     *
     * @var array
     */
    protected $route = false;

    /**
     * Custom url.
     *
     * @var string
     */
    protected $customUrl;

    /**
     * Set the data to wrap class.
     *
     * @param mixed $data
     *
     * @return object (instance)
     */
    public function make($data)
    {
        if ($data instanceof Collection) {
            $this->data = collect($data->toArray());
        } elseif (is_array($data)) {
            $this->data = collect($data);
        } else {
            throw new InvalidArgumentException('Invalid data type.');
        }

        $this->config = Config::get('nestable');

        $this->parent = $this->config['parent'];

        $this->primary_key = $this->config['primary_key'];

        return $this;
    }

    /**
     * initialize parameters (toArray, toHtml, toDropdown).
     *
     * @param array $args
     */
    protected function setParameters($args)
    {
        if (count($args) < 1) {
            return [
                'parent' => $this->parents ? current($this->parents) : 0,
                'data' => $this->data,
            ];
        } elseif (count($args) == 1) {
            return [
                'parent' => reset($args),
                'data' => $this->data,
            ];
        } else {
            return [
                'data' => reset($args),
                'parent' => next($args),
            ];
        }
    }

    /**
     * Pass to array of all data as nesting.
     *
     * @param object $data   Illuminate\Support\Collection
     * @param int    $parent
     *
     * @return Recursion|array
     */
    public function renderAsArray($data = false, $parent = 0)
    {
        $args = $this->setParameters(func_get_args());
        $tree = collect([]);

        $args['data']->each(function ($item) use (&$tree, $args) {

            $currentData = collect([]);

            if (intval($item[$this->parent]) == intval($args['parent'])) {
                // fill the array with the body fields
                foreach ($this->config['body'] as $field) {
                    $currentData->put($field, isset($item[$field]) ? $item[$field] : null);
                }

                // Get the child node name
                $child = $this->config['childNode'];

                $currentData->put($child, []);
                $currentData->put($this->parent, $item[$this->parent]);

                // Get the primary key name
                $item_id = $item[$this->config['primary_key']];
                // check the child element
                if ($this->hasChild($this->parent, $item_id, $args['data'])) {
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
     * Pass to json string of all data as nesting.
     *
     * @param object $data   Illuminate\Support\Collection
     * @param int    $parent
     *
     * @return Recursion|array
     */
    public function renderAsJson($data = false, $parent = 0)
    {
        $args = func_get_args();

        if (count($args) < 1) {
            $data = $this->renderAsArray();
        } else {
            $data = $this->renderAsArray($data);
        }

        return json_encode($data);
    }

    /**
     * Pass to html (ul:li) as nesting.
     *
     * @param object $data   Illuminate\Support\Collection
     * @param int    $parent Current parent id
     * @param bool   $first  First run
     *
     * @return string
     */
    public function renderAsHtml($data = false, $parent = 0, $first = true)
    {
        $args = $this->setParameters(func_get_args());

        // open the ul tag if function is first run
        $tree = $first ? $this->ul(null, $parent, true) : '';

        $args['data']->each(function ($child_item) use (&$tree, $args) {

            $childItems = '';

            if (intval($child_item[$this->parent]) == intval($args['parent'])) {
                $path = $child_item[$this->config['html']['href']];
                $label = $child_item[$this->config['html']['label']];

                // find parent element
                $parentNode = $args['data']->where('id', (int)$child_item[$this->config['parent']])->first();

                $currentData = [
                    'label' => $label,
                    'href' => $this->customUrl ? $this->makeUrl($path) : $this->url($path, $label, $parentNode)
                ];

                // Check the active item
                $activeItem = $this->doActive($path, $label);

                // open the li tag
                $childItems .= $this->openLi($currentData, $activeItem);
                // Get the primary key name
                $item_id = $child_item[$this->config['primary_key']];

                // check the child element
                if ($this->hasChild($this->parent, $item_id, $args['data'])) {

                    // function call again for child elements
                    $html = $this->renderAsHtml($args['data'], $item_id, false);

                    if (!empty($html)) {
                        $childItems .= $this->ul($html, $item_id);
                    }
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
     * Convert to dropdown.
     *
     * @param object $data   Illuminate\Support\Collection
     * @param int    $parent Current parent id
     * @param bool   $first  first run
     * @param int    $level  nest counter
     *
     * @return string
     */
    public function renderAsDropdown($data = false, $parent = 0, $first = true, $level = 0)
    {
        $args = $this->setParameters(func_get_args());

        $tree = '';

        // open the select tag
        if ($first) {
            $tree = $first ? '<select '.$this->addAttributes().' ' : '';
        }
        // if pass array data to selected method procces will generate multiple dropdown menu.
        if ($first && $this->multiple == true) {
            $tree .= ' multiple';
        }

        if ($first) {
            $tree .= '>';

            if(current($this->placeholder)) {
                $tree .= '<option value="'.key($this->placeholder).'">' . current($this->placeholder) . '</option>';
            }
        }

        $args['data']->each(function ($child_item) use (&$tree, $args, $level) {

            $childItems = '';

            if (intval($child_item[$this->parent]) == intval($args['parent'])) {

                // Get the value
                $value = $child_item[$this->config['dropdown']['value']];

                // Get the label text
                $label = $child_item[$this->config['dropdown']['label']];

                $prefix = $this->config['dropdown']['prefix'];

                // Generating nest level
                $levels = str_repeat('&nbsp;&nbsp;', $level);

                // check the does want select value
                $selected = $this->doSelect($value, $label);

                // Generating dropdown item
                $childItems .= '<option '.$selected.' value="'.$value.'">'.$levels.$prefix.$label.'</option>';

                $item_id = $child_item[$this->config['primary_key']];

                // check the child element
                if ($this->hasChild($this->parent, $item_id, $args['data'])) {
                    ++$level; // nest level increasing

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

    public function renderAsMultiple()
    {
        return $this->multiple()->renderAsDropdown();
    }

    /**
     * Set the attributes of generated dropdown.
     *
     * @param array $attributes
     *
     * @return object (instance)
     */
    public function attr(array $attributes)
    {
        $this->dropdownAttr = $attributes;

        return $this;
    }

    /**
     * Contact th attributes to dropdown.
     *
     * @return string
     */
    protected function addAttributes()
    {
        $attrs = '';

        foreach ($this->dropdownAttr as $attr => $value) {
            $attrs .= $attr.'='.'"'.$value.'" ';
        }

        return $attrs;
    }

    /**
     * Child menu checker.
     *
     * @param string $key
     * @param mixed  $value
     * @param object $data  Illuminate\Support\Collection as Collect
     *
     * @return bool
     */
    public function hasChild($key = null, $value = null, Collect $data = null)
    {
        if (func_num_args() < 3) {
            $data = $this->data;
            $key = $this->parent;
            $value = current(func_get_args());
        }

        $child = false;

        $data->each(function ($item) use (&$child, $key, $value) {

            if (intval($item[$key]) == intval($value) && !$child) {
                $child = true;
            }

        });

        return $child;
    }

    /**
     * Save the will select values.
     *
     * @param int|array $values
     *
     * @return object (instance)
     */
    public function selected($values)
    {
        if (is_array($values) || $values instanceof Closure) {
            $this->selected = $values;
        }
        else if (func_num_args() > 1) {
            $this->selected = func_get_args();
        } else {
            $this->selected = [$values];
        }

        return $this;
    }

    /**
     * Set dropdown placeholder
     *
     * @param string $value
     * @param string|int $label
     *
     * @return object (instance)
     */
    public function placeholder($value = '', $label = '')
    {
        $this->placeholder[$value] = $label;

        return $this;
    }

    /**
     * Attribute insert helper for html render.
     *
     * @return object (instance)
     */
    public function active()
    {
        $args = func_get_args();
        $this->active = current($args);

        if (func_num_args() > 1) {
            $this->active = $args;
        }

        return $this;
    }

    /**
     * Insert all saved attributes to <li> element.
     *
     * @param array  $href
     * @param string $label
     *
     * @return string
     */
    protected function doActive($href, $label)
    {
        if (is_array($this->active) && array_key_exists('callback', $this->active)) {
            $this->active = $this->active['callback'];
        }

        if ($this->active) {
            // Find active path in array
            if (is_array($this->active) && count($this->active) > 0) {
                $result = array_search($href, $this->active);

                if ($result !== false) {
                    unset($this->active[$result]);

                    return 'class="active"';
                }
            }

            // Run the closure for user customizable
            elseif ($this->active instanceof Closure) {
                call_user_func_array($this->active, [$this, $href, $label]);
                $attrs = $this->renderAttr($this->optionAttr);
                $this->optionAttr = null;

                return $attrs;
            } else {
                if ($this->active == $href) {
                    $this->active = null;

                    return 'class="active"';
                }
            }
        }
    }

    /**
     * Multiple dropdown menu.
     *
     * @return object (instance)
     */
    public function multiple()
    {
        $this->multiple = true;

        return $this;
    }

    /**
     * Set the parent id for child elements.
     *
     * @param int $parent
     *
     * @return object (instance)
     */
    public function parent($parent = false)
    {
        if ($parent) {
            $this->parents = !is_array($parent) ? [$parent] : $parent;

            if (func_num_args() > 1) {
                $this->parents = func_get_args();
            }
        }

        return $this;
    }

    /**
     * Set the as selected of items.
     *
     * @param mixed $value
     *
     * @return string
     */
    protected function doSelect($value, $label)
    {
        if ($this->selected) {
            if (is_array($this->selected) && count($this->selected) > 0) {
                $result = array_search($value, $this->selected);

                if ($result !== false) {
                    unset($this->selected[$result]);

                    return 'selected="selected"';
                }
            } elseif ($this->selected instanceof Closure) {
                call_user_func_array($this->selected, [$this, $value, $label]);
                $attrs = $this->renderAttr($this->optionAttr);
                $this->optionAttr = null;
                return $attrs;
            } else {
                if ($this->selected == $value) {
                    $this->selected = null;

                    return 'selected="selected"';
                }
            }
        }
    }

    /**
     * Add attribute to <li> element.
     *
     * @param mixed $attr
     * @param mixed $value
     *
     * @return object (instance)
     */
    public function addAttr($attr, $value = '')
    {
        if (func_num_args() > 1) {
            $this->optionAttr[$attr] = $value;
        } elseif (is_array($attr)) {
            $this->optionAttr = $attr;
        }

        return $this;
    }

    /**
     * Add attribute to <ul> element.
     *
     * @param mixed $attr
     * @param mixed $value
     *
     * @return object (instance)
     */
    public function ulAttr($attr, $value = '')
    {
        if (func_num_args() > 1) {
            $this->optionUlAttr[$attr] = $value;
        } elseif (is_array($attr)) {
            $this->optionUlAttr = $attr;
        }else if (is_callable($attr)) {
            $this->optionUlAttr['callback'] = $attr;
        }

        return $this;
    }

    /**
     * Add attribute to first <ul> element.
     *
     * @param mixed $attr
     * @param mixed $value
     *
     * @return object (instance)
     */
    public function firstUlAttr($attr, $value = '') {
        if (func_num_args() > 1) {
            $this->firstUlAttrs[$attr] = $value;
        } elseif (is_array($attr)) {
            $this->firstUlAttrs = $attr;
        }

        return $this;
    }

    /**
     * Render the attritues of html elements.
     *
     * @param mixed $attributes
     * @param mixed $params
     *
     * @return string
     */
    protected function renderAttr($attributes = false, $params = false)
    {
        $attrStr = '';

        if (isset($attributes['callback'])) {
            $callbackParams = [$this];

            if ($params !== false) {
                array_push($callbackParams, $params);
            }

            call_user_func_array($attributes['callback'], $callbackParams);
        }

        if (is_array($attributes)) {
            foreach ($attributes as $attr => $value) {
                if (is_string($value)) {
                    $attrStr .= ' '.$attr.'="'.$value.'"';
                }
            }
        }

        return $attrStr;
    }

    /**
     * Save the parameters.
     *
     * @param array $params
     */
    public function save(array $params)
    {
        foreach ($params as $method => $param) {
            if (is_array($param) && isset($param[0])) {
                call_user_func_array([$this, $method], $param);
            } else {
                $this->{$method}($param);
            }
        }
    }

    /**
     * URL Generator.
     *
     * @param string $path
     *
     * @return string
     */
    protected function url($path, $label, $parent = null)
    {
        if ($this->config['generate_url']) {
            if ($this->route) {
                if (array_has($this->route, 'callback')) {
                    if(is_array($parent)) $parent = (object)$parent;
                    return call_user_func_array($this->route['callback'], [$path, $label, $parent]);
                } else {
                    end($this->route);
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
     * Route generator.
     *
     * @param array $route
     */
    public function route($route)
    {
        $this->route = $route;

        return $this;
    }

    /**
     * Make custom url
     * 
     * @param string $url 
     * @return type
     */
    public function customUrl($url)
    {
        $this->customUrl = $url;

        return $this;        
    }

    /**
     * Generate custom url
     * 
     * @param string $path 
     * @return type
     */
    protected function makeUrl($path)
    {
        return URL::to(str_replace('{'.$this->config['html']['href'].'}', $path, $this->customUrl));
    }

    /**
     * Generate open ul tag.
     *
     * @param string $items
     *
     * @return string
     */
    public function ul($items = false, $parent_id = 0, $first = false)
    {
        $attrs = '';

        if (! $first && is_array($this->optionUlAttr) && count($this->optionUlAttr) > 0) {
            $attrs = $this->renderAttr($this->optionUlAttr, $parent_id);
        }
        else if($first && count($this->firstUlAttrs) > 0) {
            $attrs = $this->renderAttr($this->firstUlAttrs, $parent_id);
        }

        if (!$items) {
            return "\n".'<ul'.$attrs.'>'."\n";
        }

        return '<ul'.$attrs.'>'."\n".$items."\n".'</ul>';
    }

    /**
     * Generate close ul tag.
     *
     * @param string $ul
     *
     * @return string
     */
    public function closeUl($ul)
    {
        return $ul.'</ul>'."\n";
    }

    /**
     * Generate open li tag.
     *
     * @param array $li
     *
     * @return string
     */
    public function openLi(array $li, $extra = '')
    {
        return "\n".'<li '.$extra.'><a href="'.$li['href'].'">'.$li['label'].'</a>';
    }

    /**
     * Generate close li tag.
     *
     * @param string $li
     *
     * @return string
     */
    public function closeLi($li)
    {
        return $li."</li>\n";
    }

    /**
     * Array validator.
     *
     * @param string $type
     * @param bool   $return
     *
     * @return mixed
     */
    public function isValid($type, $render = false)
    {
        $original = $type;

        if (in_array($type, ['json', 'array'])) {
            $type = 'body';
        }

        $type = $type == 'multiple' ? 'dropdown' : $type;
        $fields = $this->config[$type];
        $valid = true;

        // mapping all data
        $this->data->map(function ($item) use ($fields, &$valid, $type) {

            foreach ($fields as $field) {
                if ($valid && !empty($field)) {
                    $valid = isset($item[$field]);
                }
            }

        });

        // render data
        if ($valid && $render) {
            return call_user_func([$this, 'renderAs'.ucfirst($original)]);
        }

        return $valid;
    }

    public function __call($method, $args)
    {
        if ($this->hasMacro($method)) {
            return $this->runMacro($method, $args);
        } elseif (preg_match('/^isValid/', $method)) {
            preg_match('/For(.*?)$/', $method, $matches);

            if (count($matches) > 1) {
                return $this->isValid(strtolower($matches[1]), current($args));
            }
        }
    }
}
