<?php namespace PhpGit;

require_once __DIR__ . '/../vendor/autoload.php';

use PhpGit\Internals\Commands\HashObject;
use PhpGit\Internals\Commands\Init;
use PhpGit\Internals\Commands\CatFile;

class CommandExecutor {

    public function run(string $command, array $args, string $in = ''): void {
        $command = preg_replace('/[^a-zA-Z0-9]+/', '_', $command);

        if (\method_exists($this, $command) && $command != 'run')
            $this->$command($args, $in);
        else {
            echo "unknown command {$command}";
        }
    }

    private function init(array $args, string $in) {
        $cmd = new Init();
        if (!$cmd->run()) {
            echo "fail";
        }
    }

    private function hash_object(array $args, string $in) {
        $cmd = new HashObject();
        for ($i = 0; $i < count($args); ++$i) {
            if ($args[$i] == '-t' && $i < count($args) - 1) {
                $cmd->setType($args[++$i]);
            }
            elseif ($args[$i] == '-w') {
                $cmd->optWrite();
            }
            elseif ($args[$i] == '--stdin') {
                $cmd->fromStdIn($in);
            }
            elseif (substr($args[$i], 0, 1) !== '-') {
                $cmd->fromFile($args[$i]);
            }
            else {
                echo "unknown args {$args[$i]}";
                return;
            }
        }
        if (!$cmd->run()) {
            echo "fail";
        }
        else {
            echo $cmd->getOutputHash();
        }
    }

    private function cat_file(array $args, string $in) {
        $cmd = new CatFile();
        $hasPrint = false;
        for ($i = 0; $i < count($args); ++$i) {
            if ($args[$i] == '-t') {
                $cmd->doPrintType();
                $hasPrint = true;
            }
            elseif ($args[$i] == '-s') {
                $cmd->doPrintSize();
                $hasPrint = true;
            }
            elseif ($args[$i] == '-p') {
                $cmd->doPrintContent();
                $hasPrint = true;
            }
            elseif (substr($args[$i], 0, 1) !== '-') {
                if ($hasPrint)
                    $cmd->setHash($args[$i]);
                else $cmd->setExpected($args[$i]);
                $hasPrint = true;
            }
            else {
                echo "unknown args {$args[$i]}";
                return;
            }
        }
        $cmd->run();
    }
}