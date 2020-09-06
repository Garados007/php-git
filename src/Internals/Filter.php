<?php namespace PhpGit\Internals;

require_once __DIR__ . '/../../vendor/autoload.php';

class Filter {
    /** filter the local content to be suitable for storage inside git
     */ 
    public static function checkinFilter(string $path, string $source): string {
        return preg_replace('/\r\n/', "\n", $source);
    }

    /** filter the git content to be suitable for storage on local disk
     * 
     */
    public static function checkoutFilter(string $path, string $source): string {
        if (\strtoupper(\substr(PHP_OS, 0, 3)) === 'WIN') {
            return \preg_replace('/(?<!\r)\n/', "\r\n", $source);
        }
        else return $source;
    }
}