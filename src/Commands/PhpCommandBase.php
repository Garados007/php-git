<?php namespace PhpGit\Commands;

require_once __DIR__ . '/../../vendor/autoload.php';

abstract class PhpCommandBase {
    public abstract function run(): bool;
}