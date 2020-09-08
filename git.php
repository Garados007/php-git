#!/usr/bin/php
<?php namespace PhpGit;

require_once __DIR__ . '/vendor/autoload.php';

use PhpGit\CommandExecutor;

(function (array $argv) {
    $args = \array_values($argv);
    $stdin = (function() {
        $stdin = '';
        $fh = fopen('php://stdin', 'r');
        stream_set_blocking($fh, false);
        $meta = stream_get_meta_data($fh);
        if (!$meta['seekable']) {
            $read  = array($fh);
            $write = NULL;
            $except = NULL;
            if ( stream_select( $read, $write, $except, 0 ) === 1 ) {
                while ($line = fgets( $fh )) {
                        $stdin .= $line;
                }
            }
        }
        fclose($fh);
        return $stdin;
    })();

    if (count($args) < 2) {
        echo 'no args'; //todo: usage
        return;
    }

    $command = $args[1];
    \array_splice($args, 0, 2);
    // $in = file_get_contents("php://stdin");

    $exec = new CommandExecutor();
    $exec->run($command, $args, $stdin);
    
})($argv);
