<?php namespace PhpGit\Internals\Commands;

require_once __DIR__ . '/../../../vendor/autoload.php';

use PhpGit\Commands\PhpCommandBase;
use PhpGit\Internals\Objects\ObjectBase;

class CatFile extends PhpCommandBase {
    private $printContent = false;
    private $printType = false;
    private $printSize = false;
    private $expectedType = 'blob';
    private $hash = null;

    public function doPrintContent(): self {
        $this->printContent = true;
        return $this;
    }

    public function doPrintType(): self {
        $this->printType = true;
        return $this;
    }

    public function doPrintSize(): self {
        $this->printSize = true;
        return $this;
    }

    public function setExpected(string $expected): self {
        $this->expectedType = $expected;
        return $this;
    }

    public function setHash(string $hash): self {
        $this->hash = $hash;
        return $this;
    }
    
    public function run(): bool {
        if ($this->hash === null) {
            echo 'no hash set';
            return false;
        }
        $obj = ObjectBase::loadObject($this->hash);
        if ($obj === null) {
            if (\is_file(ObjectBase::getPath($this->hash)))
                echo "fatal: Not a valid object name {$this->hash}";
            else 
                echo "fatal: git cat-file {$this->hash}: bad file";
            return false;
        }

        if ($this->printContent) {
            echo $obj->pretty();
            return true;
        }
        if ($this->printSize) {
            echo $obj->getSize();
            return true;
        }
        if ($this->printType) {
            echo $obj->getType();
            return true;
        }
        if ($obj->getType() != $this->expectedType) {
            echo "fatal: git cat-file {$this->hash}: bad file";
            return false;
        }
        echo $obj->getRawContent();
        return true;
    }
}