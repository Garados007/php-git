<?php namespace PhpGit\Internals\Objects;

require_once __DIR__ . '/../../../vendor/autoload.php';

use PhpGit\Internals\Objects\ObjectBase;
use PhpGit\Internals\Objects\TreeEntry;

class TreeObject extends ObjectBase {
    private $list = [];

    public function getType(): string {
        return 'tree';
    }

    public function pretty(): string {
        $content = '';
        foreach ($this->list as $entry) {
            $content .= $entry->__toString() . PHP_EOL;
        }
        return $content;
    }

    protected function writeContent() {
        $content = '';
        foreach ($this->list as $entry) {
            $content .= $entry->toBin();
        }
        $this->setRawContent($content);
    }

    protected function verify(): bool {
        $this->list = [];
        $i = 0;
        $content = $this->getRawContent();
        while ($i < \strlen($content)) {
            $j = $i;
            while (\substr($content, $j, 1) !== ' ')
                $j++;
            $oct = \substr($content, $i, $j - $i);
            $j = $i = $j + 1;
            while (\substr($content, $j, 1) !== "\x00")
                $j++;
            $name = \substr($content, $i, $j - $i);
            $j = $i = $j + 1;
            $hash = \bin2hex(\substr($content, $i, 20));
            $i = $i + 20;
            $this->list []= new TreeEntry(
                octdec($oct),
                $hash,
                $name
            );
        }
        return true;
    }

    public function getEntryCount(): int {
        return \count($this->list);
    }

    public function getEntry(int $index): ?TreeEntry {
        if ($index < 0 || $index >= \count($this->list))
            return null;
        return $this->list[$index];
    }

    public function getEntryFromName(string $name): ?TreeEntry {
        foreach ($this->list as $entry)
            if ($entry->getFile() === $name)
                return $entry;
        return null;
    }

    public function addEntry(TreeEntry $entry): self {
        $this->list []= $entry;
        $this->writeContent();
        return $this;
    }

    public function addEntries(array $entries): self {
        foreach ($entries as $entry) {
            if ($entry instanceof TreeEntry)
                $this->list []= $entry;
        }
        $this->writeContent();
        return $this;
    }

    public function removeAt(int $index): self {
        if ($index >= 0 && $index < \count($this->list)) {
            \array_splice($this->list, $index, 1);
            $this->writeContent();
        }
        return $this;
    }
}