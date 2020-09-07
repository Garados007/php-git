<?php namespace PhpGit\Internals\Objects;

require_once __DIR__ . '/../../../vendor/autoload.php';

use PhpGit\Internals\Objects\ObjectBase;

class BlobObject extends ObjectBase {
    public function getType(): string {
        return 'blob';
    }

    public function pretty(): string {
        return $this->getRawContent();
    }

    public function getContent(): string {
        return $this->getRawContent();
    }

    public function setContent(string $content): self {
        return $this->setRawContent($content);
    }

    protected function verify(): bool {
        return true;
    }
}