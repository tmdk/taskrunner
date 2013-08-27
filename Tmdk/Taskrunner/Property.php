<?php
namespace Tmdk\Taskrunner;

/**
 * Nestable property structure
 *
 * Usage:
 *
 * <code>
 * $p = new Property();
 * $p->set('a.b', '123');
 * $p->set('a.b.c', 'foo');
 * echo $p->get('a.b'); // prints "123"
 * echo $p->a->b: // prints "123"
 * $x = $p->a; // $x === NULL
 * $x = $p->z; // throws PropertyDoesNotExistException for z
 * $p->z->c = 1; // throws PropertyDoesNotExistException for z
 * </code>
 */
class Property implements \IteratorAggregate {

    private $name;
    private $elements;
    private $value;

    public function __construct($name = '', $value = NULL) {
        $this->name = $name;
        $this->elements = array();
        $this->value = $value === NULL ? NULL : (string)$value;
    }

    public function elements() {
        return $this->elements;
    }

    public function set($name, $value) {
        $parts = \explode('.', $name, 2);
        if ($parts === FALSE)
            return;

        $elem_name = $parts[0];
        $path_remainder = \sizeof($parts) === 2 ? $parts[1] : NULL;

        if (isset($this->elements[$elem_name])) {
            $elem = $this->elements[$elem_name];
        } else {
            $elem = new Property($elem_name);
            $this->elements[$elem_name] = $elem;
        }

        if ($path_remainder === NULL) {
            $elem->value = $value;
        } else {
            $elem->set($path_remainder, $value);
        }
    }

    public function get($name, $default = NULL) {
        $parts = \explode('.', $name, 2);
        if ($parts === FALSE)
            return;

        $elem_name = $parts[0];
        $path_remainder = \sizeof($parts) === 2 ? $parts[1] : NULL;

        if (isset($this->elements[$elem_name])) {
            if ($path_remainder === NULL) {
                return $this->elements[$elem_name];
            } else {
                return $this->elements[$elem_name]->get($path_remainder);
            }
        } else {
            return $default;
        }
    }

    public function __get($name) {
        if (!isset($this->elements[$name]))
            throw new PropertyDoesNotExistException("Property '$name' does not exist.");
        return $this->elements[$name];
    } 

    public function __set($name, $value) {
        if (isset($this->elements[$name])) {
            $this->elements[$name]->value = $value;
        } else {
            $this->elements[$name] = new Property($name, $value);
        }
    }

    public function __isset($name) {
        return isset($this->elements[$name]);
    }

    public function __toString() {
        return $this->value;
    }

    public function getIterator() {
        return new \ArrayIterator($this->elements);
    }

    public static function expand($property, $expr) {
        if (!\is_string($expr))
            $expr = (string)$expr;
        if (\strlen($expr) === 0)
            return $expr;


        $var_expr_path = [0];
        $v = $var_expr_num = 0;
        $var_expr = array($v=>'');
        $sub_expr = $expr;

        for ( ; $sub_expr !== FALSE; $sub_expr = \substr($sub_expr, $toklen)) {
            if ($sub_expr[0] === '{') {
                $toklen = 1;
                $v = ++$var_expr_num;
                $var_expr_path[] = $v;
                $var_expr[$v] = '';
            } elseif ($sub_expr[0] === '}') {
                if ($v === 0) {
                    throw new PropertyExpressionException("Unmatched '}'" .
                       " in expression '$expr'.");
                }
                $toklen = 1;
                $expanded = $property->get($var_expr[$v]);
                if ($expanded === NULL) {
                    throw new PropertyExpressionException(
                        "Cannot expand '{$var_expr[$v]}':" .
                        " property not found in expression '$expr'.");
                }
                \array_pop($var_expr_path);
                $v = \end($var_expr_path);
                $var_expr[$v] .= $expanded;
            } else {
                $toklen = \strcspn($sub_expr, '{}');
                $var_expr[$v] .= \substr($sub_expr, 0, $toklen);
            }
        }

        if ($v === 0)
            return $var_expr[0];
        else
            throw new PropertyExpressionException("Unmatched '{' in expression '$expr'.");
    }
}

class PropertyException extends \Exception {}
class PropertyDoesNotExistException extends PropertyException {}
class PropertyExpressionException extends PropertyException {}
