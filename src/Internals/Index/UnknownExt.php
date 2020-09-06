<?php namespace PhpGit\Internals\Index;

require_once __DIR__ . '/../../../vendor/autoload.php';

use PhpGit\Internals\Index\ExtBase;

class UnknownExt extends ExtBase {
    private $type;
    private $content = '';

    public function __construct(string $type) {
        $this->type = \substr($type, 0, 4);
    }

    public function getType(): string {
        return $this->type;
    }

    public function getRawContent(): string {
        return $this->content;
    }

    public function setRawContent(string $content): bool {
        $this->content = $content;
        return true;
    }
}
