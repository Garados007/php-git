<?php namespace PhpGit\Internals\Objects;

require_once __DIR__ . '/../../../vendor/autoload.php';

class TreeEntry {
    private $mode;
    private $type;
    private $hash;
    private $file;

    public function getMode(): int {
        return $this->mode;
    }

    public function getType(): string {
        return $this->type;
    }

    public function getHash(): string {
        return $this->hash;
    }

    public function getFile(): string {
        return $this->file;
    }

    public function __construct(int $mode, string $hash, string $file) {
        $this->mode = $mode;
        $this->type = $mode === 0x4000 ? 'tree' : 'blob';
        $this->hash = $hash;
        $this->file = $file;
    }

    public function __toString(): string {
        return \str_pad(\decoct($this->mode), 6, '0', STR_PAD_LEFT) . 
            " {$this->type} {$this->hash}\t{$this->file}";
    }

    public function toBin(): string {
        return \str_pad(\decoct($this->mode), 6, '0', STR_PAD_LEFT) .
            ' ' . $this->file . "\x00" .
            \hex2bin($this->hash);
    }
}
