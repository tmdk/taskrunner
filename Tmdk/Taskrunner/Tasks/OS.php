<?php
namespace Tmdk\Taskrunner\Tasks;

class OS {

    public static function move($from, $to) {
        $ret = \rename($from, $to);
        return array($ret, $ret
            ? "Renamed to '$to'."
            : "Rename '$from' failed.");
    }

    public static function copy($from, $to) {
        $ret = \copy($from, $to);
        return array($ret, $ret
            ? "Copied '$from' to '$to'."
            : "Copy of '$from' failed.");
    }
}
