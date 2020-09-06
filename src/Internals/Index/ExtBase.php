<?php namespace PhpGit\Internals\Index;

require_once __DIR__ . '/../../../vendor/autoload.php';

abstract class ExtBase {
    public abstract function getType(): string;

    public abstract function getRawContent(): string;

    public abstract function setRawContent(string $content): bool;
}
