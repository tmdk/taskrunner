<?php
return function($self) {
    extract($self->dsl);

    $property('bundle.directory', __DIR__);
    $property('build.directory', "{bundle.directory}/build");
    $property('foo.filename', 'test.txt');

    $default('dist');

    $target('dist', ['depends'=>'prepare'], function() use($task) {
        $task('Tmdk\Taskrunner\Tasks\OS::copy', [
            'from' => '{bundle.directory}/{foo.filename}', 'to' => '{build.directory}/{foo.filename}'
        ]);
    });

    $target('prepare', [], function() use($property) {
        mkdir($property('build.directory'));
    });
};
