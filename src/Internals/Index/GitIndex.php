<?php namespace PhpGit\Internals\Index;

require_once __DIR__ . '/../../../vendor/autoload.php';

use PhpGit\Internals\Index\IndexEntry;
use PhpGit\Internals\Index\UnknownExt;
use PhpGit\Internals\Index\ExtBase;

class GitIndex {
    private $version = 2;
    private $entries = array();
    private $extensions = array();

    public function getVersion(): int {
        return $version;
    }

    public function setVersion(int $version): self {
        if ($version >= 2 && $version <= 4)
            $this->version = $version;
        return $this;
    }

    public function getEntryCount(): int {
        return count($this->entries);
    }

    public function getEntry(int $index): ?IndexEntry {
        if ($index < 0 || $index >= count($this->entries))
            return null;
        return $this->entries[$index];
    }

    public function addEntry(IndexEntry $entry): self {
        $this->entries[] = $entry;
        return $this;
    }

    public function removeEntry(int $index): self {
        if ($index >= 0 && $index < count($this->entries))
            \array_splice($this->entries, $index, 1);
        return $this;
    }

    public function getExtensionCount(): int {
        return count($this->extensions);
    }

    public function getExtension(int $index): ?ExtBase {
        if ($index < 0 || $index >= count($this->extensions))
            return null;
        return $this->extensions[$index];
    }

    public function addExtension(ExtBase $extension): self {
        $this->extensions []= $extension;
        return $this;
    }

    public function removeExtension(int $index): self {
        if ($index >= 0 || $index < count($this->extensions))
            \array_splice($this->extensions, $index, 1);
        return $this;
    }

    private static function getData(string $data, string $format, int $index) {
        $result = unpack($format . 'data', $data, $index);
        return $result['data'];
    }

    public static function load(): ?GitIndex {
        if (!\is_file('.git/index'))
            return null;

        $index = new GitIndex();
        
        $data = \file_get_contents('.git/index');
        $length = \mb_strlen($data, '8bit');
        if (self::getData($data, 'a4', 0) != 'DIRC')
            return null;
        
        $index->version = self::getData($data, 'N', 4);
        $entries = self::getData($data, 'N', 8);

        $offset = 12;
        $prevName = '';
        for ($i = 0; $i<$entries; $i++) {
            $entry = new IndexEntry();
            $offset = $entry->load($data, $offset, $index->version, $prevName);
            if ($offset === null)
                return null;
            $index->entries []= $entry;
            $prevName = $entry->getPathName();
        }

        while ($offset <= $length - 28) {
            $extName = self::getData($data, 'a4', $offset);
            $extLength = self::getData($data, 'N', $offset + 4);
            $extData = \substr($data, $offset + 8, $extLength);
            $offset += 8 + $extLength;

            switch ($extName) {
                default:
                    $ext = new UnknownExt($extName);
                    break;
            }
            if (!$ext->setRawContent($extData))
                return null;
            $index->extensions []= $ext;
        }

        $hash = \bin2hex(self::getData($data, 'a20', $offset));
        $fileHash = \sha1(\substr($data, 0, $offset));
        if ($hash != $fileHash)
            return null;
        
        return $index;
    }

    public function save(bool $excludeUnknownExtensions = true) {
        $data = 'DIRC';
        $data .= \pack('N', $this->version);
        $data .= \pack('N', \count($this->entries));
        \usort($this->entries, function (IndexEntry $a, IndexEntry $b): int {
            return \strcmp($a->getPathName(), $b->getPathName());
        });
        $prevName = '';
        foreach ($this->entries as $entry) {
            $data .= $entry->save($this->version, $prevName);
            $prevName = $entry->getPathName();
        }
        foreach ($this->extensions as $ext) {
            if ($excludeUnknownExtensions && $ext instanceof UnknownExt)
                continue;
            $data .= $ext->getType();
            $raw = $ext->getRawContent();
            $data .= \pack('N', \mb_strlen($raw, '8bit'));
            $data .= $raw;
        }
        $hash = \sha1($data);
        $data .= \hex2bin($hash);
        \file_put_contents('.git/index', $data);
    }
}