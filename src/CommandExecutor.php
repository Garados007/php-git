<?php namespace PhpGit;

require_once __DIR__ . '/../vendor/autoload.php';

use PhpGit\Internals\Commands\HashObject;
use PhpGit\Internals\Commands\Init;
use PhpGit\Internals\Commands\CatFile;
use PhpGit\Internals\Commands\UpdateIndex;
use PhpGit\Internals\Commands\WriteTree;
use function \substr;

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
                echo "error: unknown option `{$args[$i]}'" . PHP_EOL;
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
                echo "error: unknown option `{$args[$i]}'" . PHP_EOL;
                return;
            }
        }
        $cmd->run();
    }

    private function update_index(array $args, string $in) {
        $cmd = new UpdateIndex();
        $noArgs = false;
        $matches = [];
        for ($i = 0; $i < count($args); ++$i) {
            if ($noArgs) {
                $cmd->addFile($args[$i]);
            }
            elseif ($args[$i] == '--add') {
                $cmd->doAdd();
            }
            elseif ($args[$i] == '--remove') {
                $cmd->doRemove();
            }
            elseif ($args[$i] == '--refresh') {
                $cmd->doRefresh();
            }
            elseif ($args[$i] == '--cacheinfo' && $i < count($args) - 1) {
                $arg = $args[++$i];
                if (\preg_match('/^(\d+),([0-9a-fA-F]{40}),(.*)$/', $arg, $matches)) {
                    $cmd->addCacheInfo(octdec($matches[1]), $matches[2], $matches[3]);
                }
                elseif (\preg_match('/^\d+$/', $arg) && $i < count($args) - 1) {
                    $cmd->addCacheInfo(octdec($arg), $args[$i], $args[++$i]);
                }
                else {
                    echo 'invalid args for --cacheinfo' . PHP_EOL;
                    return false;
                }
            }
            elseif ($args[$i] == '--index-info') {
                if ($i + 1 !== count($args)) {
                    echo 'error: option \'index-info\' must be the last argument' . PHP_EOL;
                    return false;
                }
                $cmd->readFromStdin();
            }
            elseif (\preg_match('/^--chmod=(\+|\-)x$/', $args[$i], $matches)) {
                $cmd->setChmod($matches[1] == '+');
            }
            elseif ($args[$i] == '--assume-unchanged') {
                $cmd->doAssumeUnchanged(true);
            }
            elseif ($args[$i] == '--no-assume-unchanged') {
                $cmd->doAssumeUnchanged(false);
            }
            elseif ($args[$i] == '--really-refresh') {
                $cmd->doReallyRefresh();
            }
            elseif ($args[$i] == '--info-only') {
                $cmd->doInfoOnly();
            }
            elseif ($args[$i] == '--force-remove') {
                $cmd->doRemove()->forceRemove();
            }
            elseif ($args[$i] == '--stdin') {
                if ($i + 1 !== count($args)) {
                    echo 'error: option \'stdin\' must be the last argument' . PHP_EOL;
                    return false;
                }
                $cmd->readFromStdin();
            }
            elseif ($args[$i] == '--index-version' && $i < count($args) - 1) {
                $cmd->setIndexVersion(intval($args[++$i]));
            }
            elseif ($args[$i] == '-z') {
                $cmd->useZeroDelimiter();
            }
            elseif ($args[$i] == '--') {
                $noArgs = true;
            }
            elseif (\substr($args[$i], 0, 1) !== '-') {
                $cmd->addFile($args[$i]);
            }
            else {
                echo "error: unknown option `{$args[$i]}'" . PHP_EOL;
                return;
            }
        }
        $cmd->setIn($in)->run();
    }

    private function write_tree(array $args, string $in) {
        $cmd = new WriteTree();
        $matches = [];
        for ($i = 0; $i < count($args); ++$i) {
            if (\preg_match('/--prefix=(.*)/', $args[$i], $matches)) {
                $cmd->setPrefix($matches[1]);
            }
            else {
                echo "error: unknown option `{$args[$i]}'" . PHP_EOL;
                return;
            }
        }
        $cmd->run();
    }
}