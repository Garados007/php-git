<?php namespace PhpGit\Internals\Commands;

require_once __DIR__ . '/../../../vendor/autoload.php';

use PhpGit\Commands\PhpCommandBase;
use PhpGit\Internals\Filter;
use PhpGit\Internals\Objects\ObjectBase;
use PhpGit\Internals\Objects\BlobObject;
use PhpGit\Internals\Index\GitIndex;
use PhpGit\Internals\Index\IndexEntry;

class UpdateIndex extends PhpCommandBase {
    private $add = false; //new files are added to index
    private $remove = false; //missing files are removed from index
    private $refresh = false; //updates the stored information
    private $chmod = null; // set +/-x permission of updated files
    private $assumeUnchanged = null; //set assume unchanged flag
    private $reallyRefresh = false; //ignores the assume unchanged flag at update
    private $forceRemove = false; //always remove files from index
    private $stdin = false; //read file paths from stdin
    private $in = ''; //cached stdin
    private $indexVersion = null; //set the index version
    private $zeroDelimiter = false; //use \x00 as delimiter instead of \n
    private $files = []; //the files to work on in the current step

    public function doAdd(): self {
        $this->add = true;
        return $this;
    }

    public function doRemove(): self {
        $this->remove = true;
        return $this;
    }

    public function doRefresh(): self {
        $this->refresh = true;
        return $this;
    }

    public function setChmod(bool $executable): self {
        $this->chmod = $executable;
        return $this;
    }

    public function doAssumeUnchanged(bool $setFlag): self {
        $this->assumeUnchanged = $setFlag;
        return $this;
    }

    public function doReallyRefresh(): self {
        $this->reallyRefresh = true;
        return $this;
    }

    public function doForceRemove(): self {
        $this->forceRemove = true;
        return $this;
    }

    public function readFromStdin(): self {
        $this->stdin = true;
        return $this;
    }

    public function setIndexVersion(int $version): self {
        $this->indexVersion = $version;
        return $this;
    }

    public function useZeroDelimiter(): self {
        $this->zeroDelimiter = true;
        return $this;
    }

    public function addFile(string $file): self {
        if (!\in_array($file, $this->files))
            $this->files []= $file;
        return $this;
    }

    public function setIn(string $in): self {
        $this->in = $in;
        return $this;
    }

    private function getBlob(string $file): BlobObject {
        $blob = new BlobObject();
        $blob->setContent(Filter::checkinFilter(
            $file,
            \file_get_contents($file)
        ));
        $blob->save();
        return $blob;
    }

    private function execFile(GitIndex $index, string $file, ?IndexEntry $entry): bool {
        if ($entry === null) {
            if (!\is_file($file)) {
                if ($this->remove)
                    return true;
                echo "error: $file: does not exist and --remove not passed" . PHP_EOL;
                return false;
            }
            if ($this->add) {
                $entry = new IndexEntry();
                $entry->copyInfoFromFile($file)
                    ->setHash($this->getBlob($file)->getHash());
                $index->addEntry($entry);
            }
            else {
                echo "error: $file: cannot add to the index - missing --add option?" . PHP_EOL;
                return false;
            }
        }
        if ($this->remove) {
            if (!\is_file($file) || $this->forceRemove) {
                $index->removeEntry($entry);
                return true;
            }
        }
        if ($this->assumeUnchanged !== null) {
            $entry->setAssumeValid($this->assumeUnchanged);
        }
        if ($this->chmod !== null) {
            $entry->setUnixPermission(
                $this->chmod ?
                    $entry->getUnixPermission() | 0x040 :
                    $entry->getUnixPermission() & ~0x040
            );
        }

        if ($entry->isAssumeValid() && !$this->reallyRefresh)
            return true;
        if (\is_file($entry->getPathName())) {
            $new = (new IndexEntry())->copyInfoFromFile($entry->getPathName());
            if (!$entry->sameStat($new)) {
                $entry->copyInfoFromFile($entry->getPathName())
                    ->setHash($this->getBlob($file)->getHash());
                if ($this->refresh)
                    echo "$file: needs update" . PHP_EOL;
            }
        }
        else {
            echo "error: $file: does not exist and --remove not passed" . PHP_EOL;
            return false;
        }
        return true;
    }
    
    public function run(): bool {
        $index = GitIndex::load();
        if ($index === null)
            $index = new GitIndex();

        if ($this->stdin) {
            $this->files = \array_unique(\array_merge(
                $this->files,
                \explode($this->zeroDelimiter ? "\x00" : "\n", $this->in)
            ));
        }
        else $this->files = \array_unique($this->files);

        foreach ($this->files as $file) {
            $entry = $index->getEntryByPath($file);
            if (!$this->execFile($index, $file, $entry)) {
                echo "fatal: Unable to process path $file" . PHP_EOL;
            }
        }

        if ($this->indexVersion !== null)
            $index->setVersion($this->indexVersion);

        $index->save();
        return true;
    }
}
 