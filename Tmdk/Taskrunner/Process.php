<?php
namespace Tmdk\Taskrunner;

class Process {

    public static function command($program) {
        $args = \func_get_args();
        \array_shift($args);
        $cmd = array($program);

        foreach ($args as $arg) {
            $arg = \trim($arg);
            if (\strpos($arg, ' ') !== false && \strspn($arg, "\"'") < 1)
                $arg = "'$arg'";
            $cmd[] = $arg;
        }

        return \implode(' ', $cmd);
    }

    public static function exec($cmd, $cwd = NULL, $env = NULL) {
        $descriptorspec = array(
            0 => array("pipe", "r"),
            1 => array("pipe", "w"),
            2 => array("pipe", "w")
        );

        $pipes = array();

        $process = \proc_open($cmd, $descriptorspec, $pipes, $cwd, $env); 

        $result = array();

        if ($process !== false) {
            list($out_write, $out_read, $err_read) = $pipes;

            \fclose($out_write);

            $result['out'] = \stream_get_contents($out_read);
            $result['err'] = \stream_get_contents($err_read);

            \fclose($out_read);
            \fclose($err_read);

            $result['ret'] = \proc_close($process);
        }

        return $result === array() ? FALSE : $result;
    }
}
