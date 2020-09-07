<?php namespace PhpGit\Internals\Index;

require_once __DIR__ . '/../../../vendor/autoload.php';

use PhpGit\Internals\Utils;
use PhpGit\Internals\Filter;

class IndexEntry {
    private $ctimeSeconds = 0;
    private $ctimeNano = 0;
    private $mtimeSeconds = 0;
    private $mtimeNano = 0;
    private $dev = 0;
    private $ino = 0;
    private $mode = 0;
    private $uid = 0;
    private $gid = 0;
    private $fileSize = 0;
    private $hash = '';
    private $flags = 0;
    private $exFlags = 0;
    private $pathName = '';

    public function getCTimeSeconds(): int {
        return $this->ctimeSeconds;
    }

    public function getCTimeNano(): int {
        return $this->ctimeNano;
    }

    public function getMTimeSeconds(): int {
        return $this->mtimeSeconds;
    }

    public function getMTimeNano(): int {
        return $this->mtimeNano;
    }

    public function getDev(): int {
        return $this->dev;
    }

    public function getIno(): int {
        return $this->ino;
    }

    public function getObjectType(): int {
        return ($this->mode >> 12) & 0x0f;
    }

    public function getUnixPermission(): int {
        return $this->mode & 0x1ff;
    }

    public function setUnixPermission(int $mode): self {
        $this->mode = ($this->mode & ~0x1ff) | ($mode & 0x1ff);
        return $this;
    }

    public function getUid(): int {
        return $this->uid;
    }

    public function getGid(): int {
        return $this->gid;
    }

    public function getFileSize(): int {
        return $this->fileSize;
    }

    public function getHash(): string {
        return $this->hash;
    }

    public function setHash(string $hash): self {
        $this->hash = $hash;
        return $this;
    }

    public function isAssumeValid(): bool {
        return ($this->flags & 0x8000) != 0;
    }

    public function setAssumeValid(bool $set): self {
        $this->flags = $set ?
            $this->flags | 0x8000 :
            $this->flags & 0x7fff;
    }

    public function isExtended(): bool {
        return ($this->flags & 0x4000) != 0;
    }

    public function getStage(): int {
        return ($this->flags >> 12) & 0x03;
    }

    public function getNameLength(): int {
        return $this->flags & 0x0fff;
    }

    public function isSkipWorktree(): bool {
        return ($this->exFlags & 0x40) != 0;
    }

    public function isIntentToAdd(): bool {
        return ($this->exFlags & 0x20) != 0;
    }

    public function getPathName(): string {
        return $this->pathName;
    }

    public function copyInfoFromFile(string $filePath): self {
        \clearstatcache();
        $stat = \stat($filePath);
        if ($stat === false)
            return $this;
        $time = Utils::getFileCtime($filePath);
        if ($time !== null) {
            $this->ctimeSeconds = $time[0];
            $this->ctimeNano = $time[1];
        }
        $time = Utils::getFileMtime($filePath);
        if ($time !== null) {
            $this->mtimeSeconds = $time[0];
            $this->mtimeNano = $time[1];
        }
        $this->dev = $stat['dev'];
        $this->ino = $stat['ino'];
        $this->mode = $stat['mode'] & ~0x0012;
        $this->uid = $stat['uid'];
        $this->gid = $stat['gid'];
        $this->fileSize = $stat['size'];
        $this->flags = \min(\strlen($filePath), 0x0fff) & 0x0fff;
        $this->exFlags = 0;
        $this->pathName = $filePath;
        return $this;
    }

    public function sameStat(IndexEntry $entry): bool {
        return
            $this->ctimeSeconds === $entry->ctimeSeconds &&
            $this->ctimeNano === $entry->ctimeNano &&
            $this->mtimeSeconds === $entry->mtimeSeconds &&
            $this->mtimeNano === $entry->mtimeNano &&
            $this->dev === $entry->dev &&
            $this->ino === $entry->ino &&
            $this->mode === $entry->mode &&
            $this->uid === $entry->uid &&
            $this->gid === $entry->gid &&
            $this->fileSize === $entry->fileSize &&
            $this->pathName === $entry->pathName;
    }

    private static function getData(string $data, string $format, int $index) {
        $result = \unpack($format . 'data', $data, $index);
        return $result['data'];
    }

    public function load(string $data, int $offset, int $version, string $prevName = ''): ?int {
        $this->ctimeSeconds = self::getData($data, 'N', $offset +  0);
        $this->ctimeNano    = self::getData($data, 'N', $offset +  4);
        $this->mtimeSeconds = self::getData($data, 'N', $offset +  8);
        $this->mtimeNano    = self::getData($data, 'N', $offset + 12);
        $this->dev          = self::getData($data, 'N', $offset + 16);
        $this->ino          = self::getData($data, 'N', $offset + 20);
        $this->mode         = self::getData($data, 'N', $offset + 24);
        $this->uid          = self::getData($data, 'N', $offset + 28);
        $this->gid          = self::getData($data, 'N', $offset + 32);
        $this->fileSize     = self::getData($data, 'N', $offset + 36);
        $this->hash = \bin2hex(self::getData($data, 'a20', $offset + 40));
        $this->flags        = self::getData($data, 'n', $offset + 60);
        $index = $offset + 62;
        if ($version >= 3 && ($this->flags & 0x02) != 0) {
            $this->exFlags  = self::getData($data, 'n', $index);
            $index += 2;
        }
        $pathTrim = 0;
        if ($version >= 4) {
            $encNum = '';
            do {
                $single = \substr($data, $index++, 1);
                $encNum .= $single;
            }
            while ((\ord($single) & 0x80) > 0);
            $pathTrim = Utils::offsetDecode($encNum);
        }
        $this->pathName = '';
        while ($data[$index] != "\x00")
            $this->pathName .= $data[$index++];
        $index++; // this is the 0x00 byte that needs to be skipped.
        if ($version >= 4) {
            $this->pathName = \substr($prevName, 0, \strlen($prevName) - $pathTrim)
                . $this->pathName;
        }
        if ($version < 4) {
            while ((($index - $offset) % 8) != 0)
                $index++;
        }
        return $index;
    }

    public function save(int $version, string $prevName = ''): string {
        $data = '';
        $data .= \pack('N', $this->ctimeSeconds);
        $data .= \pack('N', $this->ctimeNano);
        $data .= \pack('N', $this->mtimeSeconds);
        $data .= \pack('N', $this->mtimeNano);
        $data .= \pack('N', $this->dev);
        $data .= \pack('N', $this->ino);
        $data .= \pack('N', $this->mode);
        $data .= \pack('N', $this->uid);
        $data .= \pack('N', $this->gid);
        $data .= \pack('N', $this->fileSize);
        $data .= \pack('a20', \hex2bin($this->hash));
        $data .= \pack('n', $this->flags);
        if ($version >= 3 && ($this->flags & 0x02) != 0)
            $data .= \pack('n', $this->exFlags);
        if ($version < 4)
            $data .= $this->pathName . "\x00";
        else {
            $i = 0;
            for (; $i < \strlen($prevName) && $i < \strlen($this->pathName); ++$i) {
                if (\substr($prevName, $i, 1) != \substr($this->pathName, $i, 1))
                    break;
            }
            $data .= Utils::offsetEncode(\strlen($prevName) - $i);
            $data .= \substr($this->pathName, $i) . "\x00";
        }
        if ($version < 4) {
            $length = \mb_strlen($data, '8bit');
            while (($length % 8) != 0) {
                $data .= "\x00";
                $length++;
            }
        }
        return $data;
    }
}