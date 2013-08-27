taskrunner
==========

Something like Ant, but with more PHP and less XML.

Requirements
------------
* PHP >= 5.4

Features
--------
* Write targets, tasks in (almost) plain PHP.
* Use Java-style properties like `{build.directory}`. Nested properties are supported.
* Targets can have dependencies.

Usage
-----

1. `php composer.phar create-project tmdk/taskrunner taskrunner`
2. Run a *bundle* (build file) with `taskrunner [target]`. Taskrunner will look for a file `build.php` in the current directory.
   Use the `-f` flag to specify the path to a bundle.
3. For pointers on writing bundles, take a look at the examples under
   `examples`.

### Tasks

A *task* can be any function (any `callable` really). The only requirement is
that the function returns an array of two elements, the first element being
a `boolean` denoting whether the task was executed succesfully, and the second
one a `string` containing any messages about the result of the task.

TODO
----

* Write tasks.
