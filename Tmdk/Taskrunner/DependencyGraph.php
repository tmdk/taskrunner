<?php
namespace Tmdk\Taskrunner;

class DependencyGraph {
    private $deps = array();

    public function depends($dependent, $deps) {
        $this->deps[$dependent] = $deps;
    }

    public function chain($sym) {
        $chain = array();
        $to_visit = array($sym=>$sym);
        $visited = array();
        $search_next = $sym;

        while ($to_visit !== array()) {

            // fill $to_visit depth-first
            $search_next = end($to_visit);
            while ($search_next !== FALSE) {
                $deps = isset($this->deps[$search_next])
                    ? $this->deps[$search_next]
                    : array();
                foreach ($deps as $dep) {
                    if (isset($to_visit[$dep]))
                        $this->cyclic($to_visit, $dep);
                    if (!isset($visited[$dep]))
                        $to_visit[$dep] = $dep;
                }
                $search_next = reset($deps);
            }

            // visit deepest dependency
            $visit = array_pop($to_visit);
            $visited[$visit] = true;
            $chain[] = $visit;
        }

        return $chain;
    }

    private function cyclic($path, $sym) {
        $in_cycle = false;
        $cycle_description = '';
        foreach ($path as $p) {
            if ($in_cycle)
                $cycle_description .= " => $p";
            if ($p === $sym) {
                $in_cycle = true;
                $cycle_description = $sym;
            }
        }
        $cycle_close = end($path);
        $message = "Cyclic dependency detected: $cycle_description, and $cycle_close => $sym";
        throw new CyclicDependencyException($message);
    }

}

class CyclicDependencyException extends \Exception {}
