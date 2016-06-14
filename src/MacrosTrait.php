<?php

namespace Nestable;

use Closure;

trait MacrosTrait
{
    protected $macros;

    /**
     * New macro.
     *
     * @param string  $name
     * @param Closure $macro
     */
    public function macro($name, Closure $macro)
    {
        $this->macros[$name] = $macro;
    }

    /**
     * Run the macros.
     *
     * @param string $name
     * @param mixed  $args
     *
     * @return mixed
     */
    public function runMacro($name, $args)
    {
        if (isset($this->macros[$name])) {
            if (is_callable($this->macros[$name])) {
                return call_user_func_array($this->macros[$name], array_merge([$this], $args));
            }
        }
    }

    /**
     * Remove a macro.
     *
     * @param string $name Macro name
     */
    public function removeMacro($name)
    {
        if ($this->hasMacro($name)) {
            unset($this->macros[$name]);
        }
    }

    /**
     * Macro checker.
     *
     * @param string $name
     *
     * @return bool
     */
    public function hasMacro($name)
    {
        return isset($this->macros[$name]);
    }
}
