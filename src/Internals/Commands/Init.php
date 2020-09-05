<?php namespace PhpGit\Internals\Commands;

require_once __DIR__ . '/../../../vendor/autoload.php';

use function \file_put_contents;
use function \is_dir;
use function \mkdir;
use PhpGit\Commands\PhpCommandBase;

// initialize the .git folder with the required contents
class Init extends PhpCommandBase {

    private function createRequiredFoldes(array $folders): void {
        foreach ($folders as $folder)
            if (!\is_dir($folder)) {
                mkdir($folder);
            }
    }

    private function writeContentIfNonExistend(string $name, string $content) {
        if (!is_file($name))
            \file_put_contents($name, $content);
    }

    public function run() : bool {
        $this->createRequiredFoldes([
            '.git',
            '.git/objects',
            '.git/objects/info',
            '.git/objects/pack',
            '.git/refs',
            '.git/refs/heads',
            '.git/refs/tags',
        ]);
        $this->writeContentIfNonExistend(
            '.git/HEAD',
            "ref: refs/heads/master\r\n"
        );
        $this->writeContentIfNonExistend(
            '.git/FETCH_HEAD',
            ''
        );
        return true;
    }
}
