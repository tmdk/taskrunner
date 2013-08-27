<?php
namespace Tmdk\Taskrunner;

class Bundle {

    public $properties;
    public $targets = array();
    public $dsl = array();
    public $logger;
    public $default_target;

    private $deps;

    public function __construct() {
        $this->properties = new Property();
        $this->deps = new DependencyGraph;
        $this->logger = new \Psr\Log\NullLogger;

        $this->_init_default_properties();
        $this->_init_dsl();
    }

    private function _init_default_properties() {
    }

    private function _init_dsl() {
        $bundle_ops = array(
            'property' => 'property', 
            'target' => 'target',
            'task' => 'task',
            'default' => 'default_target'
        );

        foreach ($bundle_ops as $op => $fn) 
            $this->dsl[$op] = Reflection::method_closure($this, $fn);

        $log_ops = array('emergency','alert','critical','error','warning',
            'notice','info','debug','log');
        foreach ($log_ops as $op) 
            $this->dsl[$op] = Reflection::method_closure($this->logger, $op);
    }

    public function load_properties($property_file) {
        $property_file_contents = \file_get_contents($property_file);

        $properties = \json_decode($property_file_contents, TRUE);
        foreach ($properties as $k => $v)
            $this->properties->set($k, $v);
    }

    public function default_target($target) {
        $this->default_target = $target;
    }

    public function property($property, $new_value = NULL) {
        if ($new_value === NULL) {
            return $this->properties->get($property);
        } else {
            return $this->properties->set($property, 
                Property::expand($this->properties, $new_value));
        }
    }

    public function property_dict() {
        return (array)$this->properties->elements();
    }

    public function run($target) {
        if (!isset($this->targets[$target]))
            throw new UnknownTargetException("No target '$target'.");

        $dependencies = $this->deps->chain($target);

        foreach ($dependencies as $target) {
            if (!isset($this->targets[$target]))
                throw new UnknownTargetException("No target '$target'.");

            $this->logger->info("Running target $target.");
            $this->targets[$target]['fn']();
        }

        return true;
    }

    public function target($name, $options, $callable) {
        if (isset($this->targets[$name]))
            throw new BundleConfigurationException("Target '$name' already exists.");

        if (!\is_callable($callable))
            throw new BundleConfigurationException("No callable value specified for target '$name'.");

        $this->targets[$name] = $options;
        $this->targets[$name]['fn'] = $callable;

        if (isset($options['depends'])) {
            $depends = array_map('trim', explode(',', $options['depends']));
            $this->deps->depends($name, $depends);
        }

        return $this;
    }

    public function task($callable, $args) {
        $args = array_map(function($arg) {
            return Property::expand($this->properties, $arg);
        }, $args);

        $arg_list = Reflection::arg_list($callable, $args);

        list($status, $output) = \call_user_func_array($callable, $arg_list);

        if ($status === FALSE) {
            throw new TaskErrorException($output);
        }

        return $this;
    }

    public static function load($file, $bundle = NULL) {
        if ($bundle === NULL)
            $bundle = new Bundle;

        $fn = include $file;        
        $fn($bundle);
        return $bundle;
    }
}

class BundleException extends \Exception {}
class BundleConfigurationException extends BundleException {}
class TaskErrorException extends BundleException {}
class UnknownTargetException extends BundleException {}
