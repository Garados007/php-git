<?php namespace PhpGit\Internals\Commands;

require_once __DIR__ . '/../../../vendor/autoload.php';

use PhpGit\Commands\PhpCommandBase;
use PhpGit\Internals\Objects\ObjectBase;
use PhpGit\Internals\Objects\TreeObject;
use PhpGit\Internals\Objects\TreeEntry;
use PhpGit\Internals\Index\GitIndex;
use PhpGit\Internals\Index\IndexEntry;

class WriteTree extends PhpCommandBase {
    private $prefix=''; // the folder prefix

    private $bucket = [];

    public function setPrefix(string $prefix): self {
        $prefix = \preg_replace('/\\/', '/', $prefix);
        $prefix = \preg_replace('/^\//', '', $prefix);
        $prefix = \preg_replace('/([^\/])$/', '$1/', $prefix);
        $prefix = \preg_replace('/\/+/', '/', $prefix);
        $this->prefix = $prefix == '/' ? '' : $prefix;
        return $this;
    }

    private function insertToBucket(array &$bucket, IndexEntry $entry, array $elements, int $index) {
        if ($index + 1 < count($elements)) {
            if (!isset($bucket['bucket']))
                $bucket['bucket'] = [];
            $bucket['bucket'][$elements[$index]] = [];
            $this->insertToBucket($bucket['bucket'][$elements[$index]], $entry, $elements, $index + 1);
        }
        else {
            if (!isset($bucket['list']))
                $bucket['list'] = [];
            $bucket['list'][$elements[$index]] = $entry;
        }
    }

    private function execBucket(array &$bucket): string {
        $tree = new TreeObject();
        $list = [];
        if (isset($bucket['bucket']))
            foreach ($bucket['bucket'] as $key => &$b) {
                $hash = $this->execBucket($b);
                $list []= new TreeEntry(
                    0x4000,
                    $hash,
                    $key
                );
            }
        if (isset($bucket['list']))
            foreach ($bucket['list'] as $key => $entry) {
                $list []= new TreeEntry(
                    $entry->getMode(),
                    $entry->getHash(),
                    $key
                );
            }
        $tree->addEntries($list);
        $tree->save();
        return $tree->getHash();
    }

    public function run(): bool {
        $index = GitIndex::load();
        if ($index === null)
            $index = new GitIndex();

        $containing = [];
        
        for ($i = 0; $i < $index->getEntryCount(); ++$i) {
            $entry = $index->getEntry($i);
            $matches = [];
            if (!\preg_match_all('/([^\/]+)/', $this->prefix . $entry->getPathName(), $matches, PREG_PATTERN_ORDER))
                continue;
            $this->insertToBucket(
                $this->bucket,
                $entry,
                $matches[0],
                0
            );
            $containing []= $entry;
        }

        foreach ($containing as $entry)
            $index->removeEntry($entry);

        $hash = $this->execBucket($this->bucket);
        echo $hash . PHP_EOL;

        $index->save();

        return true;
    }
}