<?php namespace PhpGit\Internals\Commands;

require_once __DIR__ . '/../../../vendor/autoload.php';

use PhpGit\Commands\PhpCommandBase;
use PhpGit\Internals\Objects\ObjectBase;

class HashObject extends PhpCommandBase {
    private $content = null;
    private $write = false;
    private $hash = null;
    private $type = 'blob';

    public function fromStdIn(string $content): self {
        $this->content = $content;
        return $this;
    }

    public function fromFile(string $path): self {
        $this->content = file_get_contents($path);
        return $this;
    }

    public function optWrite(): self {
        $this->write = true;
        return $this;
    }

    public function setType(string $type): self {
        $this->type = $type;
        return $this;
    }

    public function run(): bool {
        if ($this->content === null) {
            echo "no content set";
            return false;
        }

        $obj = ObjectBase::getEmptyObject($this->type);
        if ($obj === null) {
            return false;
        }

        $obj->setRawContent($this->content);
        $obj->save();

        $this->hash = $obj->getHash();
        return true;
    }

    public function getOutputHash(): ?string {
        return $this->hash;
    }
}