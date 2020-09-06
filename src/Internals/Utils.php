<?php namespace PhpGit\Internals;

require_once __DIR__ . '/../../vendor/autoload.php';

class Utils {
    public static function offsetEncode(int $value): string {
        $result = '';
        while ($value >= 0x80 || $value < 0) {
            $result .= \chr(0x80 | ($value & 0x7f));
            $value = (($value >> 1) & (~PHP_INT_MIN)) >> 6;
        }
        $result .= \chr($value & 0x7f);
        return $result;
    }

    public static function offsetDecode(string $value): int {
        $result = 0;
        for ($i = \strlen($value) - 1; $i >= 0; --$i) {
            $num = \ord(\substr($value, $i, 1));
            $result = ($result << 7) | ($num & 0x7f);
        }
        return $result;
    }

    public static function systemCommandExists(string $command): bool {
        if (\strtoupper(\substr(PHP_OS, 0, 3)) === 'WIN')  {
            $return = \shell_exec(\sprintf("where %s", \escapeshellarg($command)));
            return !empty($return) && \substr($return, 0, 4) != 'INFO';
        }
        else {
            $return = \shell_exec(\sprintf("which %s", \escapeshellarg($command)));
            return !empty($return);
        }
    }

    public static function getFileMtime(string $path): ?array {
        if (!\is_file($path))
            return null;
        $result = [];
        $result []= \filemtime($path);
        if (self::systemCommandExists('stat')) {
            $stat = `stat --format=%y $path`;
            $patt = '/\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}.(\d{9}) .*/';
            $matches = [];
            if (preg_match($patt, $stat, $matches)) {
                $result []= intval($matches[1]);
            }
            else $result []= 0;
        }
        else $result []= 0;
        return $result;
    }

    public static function getFileCtime(string $path): ?array {
        if (!\is_file($path))
            return null;
        $result = [];
        $result []= \filemtime($path);
        if (self::systemCommandExists('stat')) {
            $stat = `stat --format=%z $path`;
            $patt = '/\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}.(\d{9}) .*/';
            $matches = [];
            if (preg_match($patt, $stat, $matches)) {
                $result []= intval($matches[1]);
            }
            else $result []= 0;
        }
        else $result []= 0;
        return $result;
    }
}