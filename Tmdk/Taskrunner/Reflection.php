<?php
namespace Tmdk\Taskrunner;

/**
 * Reflection utilities
 */
class Reflection {

    /**
     * Returns a the method $method on object $instance as a closure.
     *
     * @param $instance object
     * @param $method string
     * @return Closure|null
     */
    public static function method_closure($instance, $method) {
        $rfm = new \ReflectionMethod($instance, $method);
        return $rfm->getClosure($instance);
    }

    /**
     * Returns an indexed array of arguments matching the parameter signature
     * of $callable using the named arguments in $args.
     *
     * Will return FALSE if a required argument is missing. For missing
     * optional arguments, the default value is returned.
     *
     * @param $callable callable
     * @param $args array
     * @return $array|false
     */
    public static function arg_list($callable, $args) {
        $rfn = !\is_string($callable) || \strpos($callable, '::') === false
            ? new \ReflectionFunction($callable)
            : new \ReflectionMethod($callable);

        $params = $rfn->getParameters();

        $arg_list = array();

        foreach ($params as $param) {
            if (isset($args[$param->name]))
                $arg_list[] = $args[$param->name];
            else {
                if ($param->isOptional())
                    $arg_list[] = $param->getDefaultValue();
                else
                    return FALSE;
            }
        }

        return $arg_list;
    }

    /**
     * Returns the names of all the required parameters of $callable
     *
     * @param $callable callable
     * @return $array
     */
    public static function required_parameters($callable) {
        $rfn = !\is_string($callable) || \strpos($callable, '::') === false
            ? new \ReflectionFunction($callable)
            : new \ReflectionMethod($callable);

        $params = $rfn->getParameters();

        $required = array();

        foreach ($params as $param) {
            if (!$param->isOptional())
                $required[] = $param->name;
        }

        return $required;
    }
}
