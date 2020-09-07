<?php namespace PhpGit\Internals\Objects;

require_once __DIR__ . '/../../../vendor/autoload.php';

abstract class ObjectBase {
    private $size;
    private $content;
    private $hash;
    
    public abstract function getType(): string;

    public function getSize(): int {
        return $this->size;
    }

    public function getRawContent(): string {
        return $this->content;
    }

    public function getHash(): string {
        return $this->hash;
    }

    public function setRawContent(string $content): self {
        $this->content = $content;
        $this->size = \mb_strlen($content, '8bit');
        $this->hash = \sha1($this->getFullFileContent());
        return $this;
    }

    private function getFullFileContent(): string {
        return $this->getType() . ' ' . 
            $this->getSize() . "\x00" . $this->content;
    }

    public static function getPathDir(string $hash): string {
        return '.git/objects/' . \substr($hash, 0, 2);
    }

    public static function getPath(string $hash): string {
        return self::getPathDir($hash) . '/' . \substr($hash, 2);
    }

    public function save() {
        if (!\is_dir(self::getPathDir($this->hash)))
            \mkdir(self::getPathDir($this->hash));
        $compressed = \gzcompress($this->getFullFileContent());
        $path = self::getPath($this->hash);
        if (\is_file($path))
            \chmod($path, 0644);
        \file_put_contents($path, $compressed);
        \chmod($path, 0444);
    }

    protected abstract function verify(): bool;

    public static function loadObject(string $hash): ?ObjectBase {
        $path = '.git/objects/' . \substr($hash, 0, 2) . '/' . \substr($hash, 2);
        if (!\is_file($path))
            return null;
        $compressed = \file_get_contents($path);
        $uncompressed = \gzuncompress($compressed);
        $matches = array();
        if (\preg_match('/^([^ ]+) ([0-9]+)\x{00}(.*)$/', $uncompressed, $matches) !== 1) {
            return null;
        }
        $obj = self::getEmptyObject($matches[1]);
        $obj->size = intval($matches[2]);
        $obj->content = $matches[3];
        $obj->hash = $hash;
        if (!$obj->verify())
            return null;
        return $obj;
    }

    public static function getEmptyObject(string $type): ?ObjectBase {
        switch ($type) {
            case 'blob':
                return new BlobObject();
            default: return null;
        }
    }
}