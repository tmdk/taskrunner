<?php
namespace Tmdk\Taskrunner;

use Psr\Log\LogLevel;

class Cli extends \Psr\Log\AbstractLogger {
    private $STDERR;
    private $bundle;

    public function __construct() {
        $this->STDERR = fopen('php://stderr', 'w+');
    }

    public function main($argc, $argv) {
        $options = getopt("p::f:");
        $properties = array();

        if (isset($options['f'])) {
            $bundle = is_array($options['f'])
                ? $options['f'][0]
                : $options['f'];
        } elseif (file_exists('build.php')) {
            $bundle = 'build.php';
        } else {
            $this->stop('No bundle file specfied or found in current directory.');
        }

        try {
            $bundle = Bundle::load($bundle);
        } catch (BundleException $ex) {
            $this->exception($ex);
        } catch (PropertyException $ex) {
            $this->exception($ex);
        }

        $bundle->logger = $this;

        $targets = array_keys($bundle->targets);
        $target = NULL;
        foreach ($argv as $arg) {
            if (in_array($arg, $targets)) {
                $target = $arg;
                break;
            }
        }

        if ($target === NULL && isset($bundle->default_target)) {
            $target = $bundle->default_target;
        }

        if ($target === NULL) {
            $this->stop('No target specified.');
        }

        if (isset($options['p'])) {
            $pairs = is_array($options['p'])
                ? $options['p']
                : array($options['p']);
            $properties = $this->_parse_properties($pairs);
            foreach ($properties as $name => $value) {
                $bundle->property($name, $value);
            }
        }

        try {
            $bundle->run($target);
        } catch (BundleException $ex) {
            $this->exception($ex);
        } catch (PropertyException $ex) {
            $this->exception($ex);
        }
    }

    private function exception($ex) {
        $this->stop($ex->getMessage());
    }

    public function stop($message) {
        $this->critical("$message Stopping.");
        die();
    }

    private function _parse_properties($pairs) {
        $properties = array();
        foreach ($pairs as $pair) {
            $parts = explode('=', $pair, 2);
            if ($parts !== FALSE && sizeof($parts) === 2) {
                $properties[$parts[0]] = $parts[1];
            }
        }
        return $properties;
    }

    public function log($level, $message, array $context = array()) {
        $message .= PHP_EOL;
        if ($level === LogLevel::INFO || $level === LogLevel::DEBUG) {
            echo $message;
        } else {
            $this->_write_stderr($message);
        }
    }

    private function _write_stderr($str) {
        fwrite($this->STDERR, $str);
    }
}
